"""Pydantic schemas for Structured Interpretation (Input 9.2) and LLM Narrative (Output 9.3)."""

from __future__ import annotations

from typing import Any
from pydantic import BaseModel, Field


class ModelRefSchema(BaseModel):
    version: str = "xgb-v1.0.0"
    technique: str = "class_weight+optuna"


class PredictionSummarySchema(BaseModel):
    label: str
    label_code: int
    probabilities: dict[str, float]
    confidence_note: str


class InputItemSchema(BaseModel):
    feature: str
    label_id: str
    value: float | int
    display: str


class ShapTopItemSchema(BaseModel):
    rank: int
    feature: str
    label_id: str
    display: str
    shap: float
    direction: str  # "naik" | "turun"


class LLMConfigOverrideSchema(BaseModel):
    """Optional runtime override from Laravel Admin UI for 3rd-party LLM API keys."""
    provider: str | None = None
    api_key: str | None = None
    base_url: str | None = None
    model: str | None = None
    temperature: float | None = None
    prompt_version: str | None = None


class StructuredInterpretationInput(BaseModel):
    case_id: str
    model: ModelRefSchema
    prediction: PredictionSummarySchema
    input_summary: list[InputItemSchema]
    shap_top: list[ShapTopItemSchema]
    allowed_features: list[str]
    language: str = "id"
    audience: str = "awam"
    llm_config: LLMConfigOverrideSchema | None = None


class FactorExplanationItem(BaseModel):
    feature: str
    penjelasan: str


class NarrativeOutputSchema(BaseModel):
    ringkasan: str = Field(..., min_length=20)
    faktor_yang_meningkatkan: list[FactorExplanationItem] = Field(default_factory=list)
    faktor_yang_menurunkan: list[FactorExplanationItem] = Field(default_factory=list)
    saran_umum: list[str] = Field(default_factory=list)
    kapan_ke_tenaga_kesehatan: str = Field(..., min_length=15)


class ValidationResultSchema(BaseModel):
    passed: bool
    checks: dict[str, bool]
    errors: list[str] = Field(default_factory=list)
    retries: int = 0
    meta: dict[str, Any] = Field(default_factory=dict)

