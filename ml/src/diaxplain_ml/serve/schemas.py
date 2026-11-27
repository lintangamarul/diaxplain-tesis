"""Pydantic v2 request/response contracts for `/v1/assess`, `/v1/interpret`, and `/v1/llm/config`."""

from __future__ import annotations

from typing import Any
from pydantic import BaseModel, Field, field_validator

from diaxplain_ml.features.codebook import FEATURE_ORDER, FEATURE_SPECS


class AssessOptionsSchema(BaseModel):
    top_k: int = Field(default=6, ge=1, le=21)


class AssessRequestSchema(BaseModel):
    case_id: str = Field(..., min_length=1, max_length=120)
    features: dict[str, float | int]
    options: AssessOptionsSchema = Field(default_factory=AssessOptionsSchema)

    @field_validator("features")
    @classmethod
    def check_21_features(cls, v: dict[str, float | int]) -> dict[str, float | int]:
        missing = [f for f in FEATURE_ORDER if f not in v]
        if missing:
            raise ValueError(f"Fitur wajib tidak lengkap: {', '.join(missing)}")
        for fname in FEATURE_ORDER:
            spec = FEATURE_SPECS[fname]
            val = float(v[fname])
            if val < float(spec["min"]) or val > float(spec["max"]):
                raise ValueError(
                    f"Nilai {fname}={val} di luar rentang [{spec['min']}, {spec['max']}]."
                )
        return v


class UpdateLLMConfigSchema(BaseModel):
    provider: str = Field(..., description="openai_compatible | gemini | anthropic | template_fallback")
    api_key: str | None = Field(default=None)
    base_url: str | None = Field(default=None)
    model: str = Field(default="gpt-4o-mini")
    temperature: float = Field(default=0.2, ge=0.0, le=1.5)
    prompt_version: str = Field(default="p1.0")
    test_connection: bool = Field(default=False)

