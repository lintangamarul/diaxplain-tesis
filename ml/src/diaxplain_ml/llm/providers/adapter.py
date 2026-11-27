"""Multi-provider 3rd-party LLM API adapter with retry + fact validation + fallback."""

from __future__ import annotations

import json
import os
import re
import time
from typing import Any

import httpx

from diaxplain_ml.llm.fallback import generate_fallback_narrative
from diaxplain_ml.llm.prompts.templates import build_prompts
from diaxplain_ml.llm.schema import NarrativeOutputSchema, StructuredInterpretationInput
from diaxplain_ml.llm.validator import validate_narrative


def _extract_json_object(raw_text: str) -> dict[str, Any]:
    """Extract JSON object from raw LLM response (stripping markdown fences if present)."""
    cleaned = raw_text.strip()
    fence_match = re.search(r"```(?:json)?\s*(\{.*?\})\s*```", cleaned, flags=re.DOTALL)
    if fence_match:
        cleaned = fence_match.group(1)
    else:
        start = cleaned.find("{")
        end = cleaned.rfind("}")
        if start != -1 and end != -1 and end > start:
            cleaned = cleaned[start : end + 1]
    return json.loads(cleaned)


class ThirdPartyLLMAdapter:
    """
    Unified LLM Adapter for 3rd-party API Key services:
    - `openai_compatible` (OpenAI, Groq, OpenRouter, DeepSeek, Fireworks, Together)
    - `gemini` (Google Gemini API via OpenAI-compatible or native endpoint)
    - `anthropic` (Anthropic Messages API)
    - `template_fallback` (Deterministic template without external API call)
    """

    def __init__(
        self,
        provider: str | None = None,
        api_key: str | None = None,
        base_url: str | None = None,
        model: str | None = None,
        temperature: float | None = None,
        timeout: float | None = None,
        prompt_version: str | None = None,
    ) -> None:
        self.provider = (provider or os.getenv("LLM_PROVIDER", "openai_compatible")).strip().lower()
        self.api_key = (api_key if api_key is not None else os.getenv("LLM_API_KEY", "")).strip()
        self.base_url = (base_url or os.getenv("LLM_BASE_URL", "https://api.openai.com/v1")).strip().rstrip("/")
        self.model = (model or os.getenv("LLM_MODEL", "gpt-4o-mini")).strip()
        self.temperature = float(temperature if temperature is not None else os.getenv("LLM_TEMPERATURE", "0.2"))
        self.timeout = float(timeout if timeout is not None else os.getenv("LLM_TIMEOUT", "45"))
        self.prompt_version = prompt_version or os.getenv("PROMPT_VERSION", "p1.0")

        if self.provider == "gemini" and ("openai.com" in self.base_url or not self.base_url):
            self.base_url = "https://generativelanguage.googleapis.com/v1beta/openai"
            if not model:
                self.model = "gemini-2.5-flash"
        elif self.provider == "anthropic" and ("openai.com" in self.base_url or not self.base_url):
            self.base_url = "https://api.anthropic.com/v1"
            if not model:
                self.model = "claude-3-5-haiku-latest"

    def is_api_key_configured(self) -> bool:
        return bool(self.api_key and len(self.api_key) >= 8 and self.provider != "template_fallback")

    def call_provider(self, system_prompt: str, user_prompt: str) -> tuple[str, int, int]:
        """Execute HTTP request to the configured 3rd-party LLM API. Returns (content, tokens_in, tokens_out)."""
        if not self.is_api_key_configured():
            raise RuntimeError("API Key pihak ketiga belum diisi atau mode template_fallback aktif.")

        if self.provider in ("openai_compatible", "gemini", "groq", "openrouter"):
            url = f"{self.base_url}/chat/completions"
            headers = {
                "Authorization": f"Bearer {self.api_key}",
                "Content-Type": "application/json",
            }
            body: dict[str, Any] = {
                "model": self.model,
                "messages": [
                    {"role": "system", "content": system_prompt},
                    {"role": "user", "content": user_prompt},
                ],
                "temperature": self.temperature,
                "max_tokens": 1024,
            }
            if "openai.com" in self.base_url or "groq.com" in self.base_url:
                body["response_format"] = {"type": "json_object"}

            with httpx.Client(timeout=self.timeout) as client:
                resp = client.post(url, headers=headers, json=body)
                resp.raise_for_status()
                data = resp.json()

            content = data["choices"][0]["message"]["content"]
            usage = data.get("usage", {})
            return content, int(usage.get("prompt_tokens", 0)), int(usage.get("completion_tokens", 0))

        if self.provider == "anthropic":
            url = f"{self.base_url}/messages"
            headers = {
                "x-api-key": self.api_key,
                "anthropic-version": "2023-06-01",
                "Content-Type": "application/json",
            }
            body = {
                "model": self.model,
                "max_tokens": 1024,
                "temperature": self.temperature,
                "system": system_prompt,
                "messages": [{"role": "user", "content": user_prompt}],
            }
            with httpx.Client(timeout=self.timeout) as client:
                resp = client.post(url, headers=headers, json=body)
                resp.raise_for_status()
                data = resp.json()

            content_blocks = data.get("content", [])
            text_out = "".join(b.get("text", "") for b in content_blocks if b.get("type") == "text")
            usage = data.get("usage", {})
            return text_out, int(usage.get("input_tokens", 0)), int(usage.get("output_tokens", 0))

        raise ValueError(f"Provider LLM '{self.provider}' tidak didukung.")


def generate_validated_interpretation(
    payload: StructuredInterpretationInput,
    max_retries: int = 2,
) -> dict[str, Any]:
    """
    Full Layer 1–6 LLM pipeline:
    1. Build structured prompt
    2. Call 3rd-party LLM API (if API key configured)
    3. Parse Pydantic schema & run Layer-3 Fact Validator
    4. Retry up to `max_retries` with error feedback if validation fails
    5. Fallback to deterministic template if API key not set or retries exhausted
    """
    t0 = time.perf_counter()
    cfg = payload.llm_config
    adapter = ThirdPartyLLMAdapter(
        provider=cfg.provider if cfg else None,
        api_key=cfg.api_key if cfg else None,
        base_url=cfg.base_url if cfg else None,
        model=cfg.model if cfg else None,
        temperature=cfg.temperature if cfg else None,
        prompt_version=cfg.prompt_version if cfg else None,
    )

    retries_used = 0
    last_errors: list[str] = []
    tokens_in_total = 0
    tokens_out_total = 0
    provider_error: str | None = None

    if adapter.is_api_key_configured():
        for attempt in range(max_retries + 1):
            retries_used = attempt
            sys_prompt, usr_prompt = build_prompts(
                payload,
                prompt_version=adapter.prompt_version,
                retry_errors=last_errors if attempt > 0 else None,
            )
            try:
                raw_text, t_in, t_out = adapter.call_provider(sys_prompt, usr_prompt)
                tokens_in_total += t_in
                tokens_out_total += t_out
                parsed_json = _extract_json_object(raw_text)
                narrative_obj = NarrativeOutputSchema.model_validate(parsed_json)
                val_res = validate_narrative(narrative_obj, payload)
                if val_res["passed"]:
                    latency_ms = int((time.perf_counter() - t0) * 1000)
                    return {
                        "narrative": narrative_obj.model_dump(),
                        "validation": {
                            "passed": True,
                            "checks": val_res["checks"],
                            "metrics": val_res["metrics"],
                            "errors": [],
                            "retries": retries_used,
                        },
                        "meta": {
                            "provider": adapter.provider,
                            "model": adapter.model,
                            "prompt_version": adapter.prompt_version,
                            "latency_ms": latency_ms,
                            "tokens_in": tokens_in_total,
                            "tokens_out": tokens_out_total,
                            "used_fallback": False,
                            "fallback_reason": None,
                        },
                    }
                last_errors = val_res["errors"]
            except Exception as exc:
                provider_error = str(exc)
                last_errors = [f"Error pemanggilan/parsing LLM: {provider_error}"]
                # If network/auth error on first try, break immediately to fallback without wasting time
                if "401" in provider_error or "403" in provider_error or "ConnectError" in provider_error:
                    break
    else:
        provider_error = "API Key LLM pihak ketiga belum dikonfigurasi (menggunakan narasi templat deterministik)."

    # Layer-5 Fallback: Deterministic Template Narrative
    fallback_obj = generate_fallback_narrative(payload)
    val_fallback = validate_narrative(fallback_obj, payload)
    latency_ms = int((time.perf_counter() - t0) * 1000)

    return {
        "narrative": fallback_obj.model_dump(),
        "validation": {
            "passed": val_fallback["passed"],
            "checks": val_fallback["checks"],
            "metrics": val_fallback["metrics"],
            "errors": last_errors,
            "retries": retries_used,
        },
        "meta": {
            "provider": adapter.provider if adapter.is_api_key_configured() else "template_fallback",
            "model": adapter.model if adapter.is_api_key_configured() else "deterministic-v1",
            "prompt_version": adapter.prompt_version,
            "latency_ms": latency_ms,
            "tokens_in": tokens_in_total,
            "tokens_out": tokens_out_total,
            "used_fallback": True,
            "fallback_reason": provider_error or ("; ".join(last_errors) if last_errors else "fallback"),
        },
    }

