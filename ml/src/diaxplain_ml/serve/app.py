"""FastAPI Application for DiaXplain ML & LLM Interpretation Service (Section 10)."""

from __future__ import annotations

import os
from pathlib import Path
from typing import Any

import numpy as np
from dotenv import load_dotenv
from fastapi import Depends, FastAPI, HTTPException, status

from diaxplain_ml.explain.shap_explainer import compute_local_shap
from diaxplain_ml.features.codebook import LABEL_DISPLAY_ID, LABEL_MAP
from diaxplain_ml.features.encoder import build_input_summary, validate_and_order_features
from diaxplain_ml.llm.evaluate import compute_confidence_note
from diaxplain_ml.llm.providers.adapter import ThirdPartyLLMAdapter, generate_validated_interpretation
from diaxplain_ml.llm.schema import StructuredInterpretationInput
from diaxplain_ml.serve.model_registry import ModelRegistry
from diaxplain_ml.serve.schemas import AssessRequestSchema, UpdateLLMConfigSchema
from diaxplain_ml.serve.security import verify_service_token

ML_ROOT = Path(__file__).resolve().parents[3]
load_dotenv(ML_ROOT / ".env")

registry = ModelRegistry(base_dir=ML_ROOT)
registry.load()

app = FastAPI(
    title="DiaXplain ML & XAI Service",
    description="Layanan Klasifikasi Status Diabetes (XGBoost + TreeSHAP + Guardrailed 3rd-Party LLM API)",
    version="1.0.0",
)


@app.get("/health")
def health_check() -> dict[str, Any]:
    adapter = ThirdPartyLLMAdapter()
    return {
        "status": "ok",
        "model_loaded": registry.is_loaded,
        "model_version": registry.metadata.get("model_version", "xgb-v1.0.0") if registry.is_loaded else None,
        "llm": {
            "provider": adapter.provider,
            "model": adapter.model,
            "base_url": adapter.base_url,
            "api_key_configured": adapter.is_api_key_configured(),
            "prompt_version": adapter.prompt_version,
        },
    }


@app.get("/v1/model/info", dependencies=[Depends(verify_service_token)])
def model_info() -> dict[str, Any]:
    if not registry.is_loaded:
        registry.load()
    if not registry.is_loaded:
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="Model belum dimuat di direktori artefak.",
        )
    return registry.get_info()


@app.post("/v1/reload", dependencies=[Depends(verify_service_token)])
def reload_model(payload: dict[str, Any] | None = None) -> dict[str, Any]:
    custom_dir = payload.get("artifact_path") if payload else None
    ok = registry.load(custom_dir=custom_dir)
    if not ok:
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="Gagal memuat model dari path yang diberikan.",
        )
    return {
        "reloaded": True,
        "model_version": registry.metadata.get("model_version", "xgb-v1.0.0"),
        "technique": registry.metadata.get("technique", ""),
    }


@app.post("/v1/assess", dependencies=[Depends(verify_service_token)])
def assess_screening(req: AssessRequestSchema) -> dict[str, Any]:
    if not registry.is_loaded:
        registry.load()
    if not registry.is_loaded or registry.model is None or registry.explainer is None:
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="Model XGBoost belum tersedia. Jalankan pipeline pelatihan terlebih dahulu.",
        )

    try:
        X_arr, warnings = validate_and_order_features(req.features)
    except ValueError as exc:
        raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail=str(exc)) from exc

    probs = registry.model.predict_proba(X_arr)[0]
    pred_code = int(np.argmax(probs))
    pred_label = LABEL_MAP[pred_code]
    prob_map = {LABEL_MAP[i]: round(float(probs[i]), 4) for i in [0, 1, 2]}
    conf_note = compute_confidence_note(float(np.max(probs)))

    shap_result = compute_local_shap(
        explainer=registry.explainer,
        model=registry.model,
        X_row=X_arr,
        predicted_class=pred_code,
        top_k=req.options.top_k,
    )
    input_summary = build_input_summary(req.features)

    return {
        "model_version": registry.metadata.get("model_version", "xgb-v1.0.0"),
        "model_technique": registry.metadata.get("technique", "class_weight+optuna"),
        "prediction": {
            "label": pred_label,
            "label_id": LABEL_DISPLAY_ID[pred_code],
            "label_code": pred_code,
            "probabilities": prob_map,
            "confidence_note": conf_note,
        },
        "shap": {
            "explained_class": shap_result["explained_class"],
            "explained_class_id": shap_result["explained_class_id"],
            "base_value": shap_result["base_value"],
            "margin_value": shap_result["margin_value"],
            "top": shap_result["top"],
        },
        "input_summary": input_summary,
        "warnings": warnings,
    }


@app.post("/v1/interpret", dependencies=[Depends(verify_service_token)])
def interpret_screening(req: StructuredInterpretationInput) -> dict[str, Any]:
    return generate_validated_interpretation(req)


@app.post("/v1/llm/config", dependencies=[Depends(verify_service_token)])
def update_llm_config(req: UpdateLLMConfigSchema) -> dict[str, Any]:
    """Update runtime environment variables & `.env` for 3rd-party LLM API keys."""
    os.environ["LLM_PROVIDER"] = req.provider.strip()
    if req.api_key is not None and req.api_key.strip() != "" and not req.api_key.startswith("****"):
        os.environ["LLM_API_KEY"] = req.api_key.strip()
    if req.base_url is not None:
        os.environ["LLM_BASE_URL"] = req.base_url.strip()
    os.environ["LLM_MODEL"] = req.model.strip()
    os.environ["LLM_TEMPERATURE"] = str(req.temperature)
    os.environ["PROMPT_VERSION"] = req.prompt_version.strip()

    # Persist to ml/.env
    env_file = ML_ROOT / ".env"
    env_lines = [
        "# DiaXplain ML Service Environment Configuration",
        f"SERVICE_TOKEN={os.getenv('SERVICE_TOKEN', 'diaxplain-internal-secret-token-2026')}",
        f"MODEL_DIR={os.getenv('MODEL_DIR', 'artifacts/model_v1')}",
        f"LLM_PROVIDER={os.getenv('LLM_PROVIDER', 'openai_compatible')}",
        f"LLM_API_KEY={os.getenv('LLM_API_KEY', '')}",
        f"LLM_BASE_URL={os.getenv('LLM_BASE_URL', 'https://api.openai.com/v1')}",
        f"LLM_MODEL={os.getenv('LLM_MODEL', 'gpt-4o-mini')}",
        f"LLM_TIMEOUT={os.getenv('LLM_TIMEOUT', '45')}",
        f"LLM_TEMPERATURE={os.getenv('LLM_TEMPERATURE', '0.2')}",
        f"PROMPT_VERSION={os.getenv('PROMPT_VERSION', 'p1.0')}",
    ]
    env_file.write_text("\n".join(env_lines) + "\n", encoding="utf-8")

    adapter = ThirdPartyLLMAdapter()
    test_result: dict[str, Any] | None = None
    if req.test_connection:
        if not adapter.is_api_key_configured():
            test_result = {
                "ok": False,
                "message": "API Key pihak ketiga belum diisi (menggunakan mode Templat Deterministik).",
            }
        else:
            try:
                content, t_in, t_out = adapter.call_provider(
                    "Balas hanya dengan JSON: {\"status\": \"connected\"}",
                    "Tes koneksi API Key DiaXplain.",
                )
                test_result = {
                    "ok": True,
                    "message": f"Berhasil terhubung ke {adapter.provider} ({adapter.model})!",
                    "sample_response": content[:120],
                    "tokens": t_in + t_out,
                }
            except Exception as exc:
                test_result = {
                    "ok": False,
                    "message": f"Gagal menghubungi API provider: {exc}",
                }

    return {
        "updated": True,
        "provider": adapter.provider,
        "model": adapter.model,
        "base_url": adapter.base_url,
        "api_key_configured": adapter.is_api_key_configured(),
        "test_result": test_result,
    }

