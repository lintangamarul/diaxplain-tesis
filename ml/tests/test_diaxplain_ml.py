"""Comprehensive pytest suite for DiaXplain ML (Anti-Leakage, SHAP, LLM Guardrails, API Contracts)."""

from __future__ import annotations

from pathlib import Path

import numpy as np
import pandas as pd
import pytest
import shap
from fastapi.testclient import TestClient

from diaxplain_ml.analysis.weakness import evaluate_baseline_weakness
from diaxplain_ml.data.loader import generate_brfss_calibrated_dataset, load_raw_dataset
from diaxplain_ml.data.split import create_stratified_split, verify_no_split_leakage
from diaxplain_ml.explain.shap_explainer import compute_local_shap, normalize_multiclass_shap_values
from diaxplain_ml.features.codebook import FEATURE_ORDER, age_years_to_category, compute_bmi
from diaxplain_ml.features.encoder import validate_and_order_features
from diaxplain_ml.llm.fallback import generate_fallback_narrative
from diaxplain_ml.llm.schema import NarrativeOutputSchema, StructuredInterpretationInput
from diaxplain_ml.llm.validator import validate_narrative
from diaxplain_ml.serve.app import app, registry
from diaxplain_ml.training.experiments import apply_resampling_train_only, build_xgb_classifier


def test_feature_conversions_and_validation() -> None:
    assert age_years_to_category(21) == 1
    assert age_years_to_category(57) == 8
    assert age_years_to_category(82) == 13
    assert compute_bmi(75.0, 170.0) == 26.0

    valid_input = {f: 1 for f in FEATURE_ORDER}
    valid_input["BMI"] = 28.5
    valid_input["MentHlth"] = 3
    valid_input["PhysHlth"] = 5
    valid_input["GenHlth"] = 3
    valid_input["Age"] = 8
    valid_input["Education"] = 5
    valid_input["Income"] = 6

    arr, warnings = validate_and_order_features(valid_input)
    assert arr.shape == (1, 21)
    assert len(warnings) == 0

    bad_input = dict(valid_input)
    bad_input["BMI"] = 150.0  # Out of range [12, 98]
    with pytest.raises(ValueError, match="BMI"):
        validate_and_order_features(bad_input)


def test_anti_leakage_split_and_resampling(tmp_path: Path) -> None:
    csv_path = generate_brfss_calibrated_dataset(tmp_path / "sample.csv", n_samples=600, seed=42)
    df, _ = load_raw_dataset(csv_path)
    split_meta = create_stratified_split(df, tmp_path / "proc", test_size=0.2, n_splits=5, seed=42)

    assert verify_no_split_leakage(split_meta["train_indices"], split_meta["test_indices"])
    for fold in split_meta["cv_folds"]:
        assert verify_no_split_leakage(fold["train_idx"], fold["val_idx"])

    X_dummy = df[FEATURE_ORDER].to_numpy(dtype=np.float32)[:100]
    y_dummy = df["Diabetes_012"].to_numpy(dtype=int)[:100]

    # Resampling on test set MUST raise anti-leakage ValueError
    with pytest.raises(ValueError, match="ANTI-LEAKAGE"):
        apply_resampling_train_only(X_dummy, y_dummy, "smote", is_test_set=True)


def test_weakness_gate_identifies_imbalance() -> None:
    synthetic_test_metrics = {
        "accuracy": 0.848,
        "macro_f1": 0.442,
        "per_class": {
            "No Diabetes": {"f1": 0.915, "recall": 0.975, "precision": 0.862},
            "Prediabetes": {"f1": 0.012, "recall": 0.006, "precision": 0.250},
            "Diabetes": {"f1": 0.400, "recall": 0.280, "precision": 0.700},
        },
        "confusion_matrix": {
            "labels": ["No Diabetes", "Prediabetes", "Diabetes"],
            "counts": [[4100, 5, 100], [85, 1, 14], [500, 3, 200]],
        },
    }
    report = evaluate_baseline_weakness(
        baseline_cv_summary={"macro_f1_mean": 0.440, "macro_f1_std": 0.012},
        baseline_test_metrics=synthetic_test_metrics,
        train_macro_f1=0.520,
    )
    assert report["gate_passed_for_improvement"] is True
    assert "class_weight" in report["recommended_techniques"]
    assert "smote" in report["recommended_techniques"]


def test_shap_normalization_and_additivity() -> None:
    rng = np.random.default_rng(42)
    X = rng.integers(0, 2, size=(120, 21)).astype(np.float32)
    X[:, 3] = rng.uniform(18, 40, size=120)
    X[:, 13] = rng.integers(1, 6, size=120)
    X[:, 18] = rng.integers(1, 14, size=120)
    X[:, 19] = rng.integers(1, 7, size=120)
    X[:, 20] = rng.integers(1, 9, size=120)
    y = rng.choice([0, 1, 2], size=120, p=[0.6, 0.2, 0.2])

    clf = build_xgb_classifier({"n_estimators": 25, "max_depth": 3}, seed=42)
    clf.fit(X, y)
    explainer = shap.TreeExplainer(clf)

    # Check both list-per-class and 3D array normalization
    fake_list = [np.zeros((5, 21)), np.ones((5, 21)), np.full((5, 21), 2.0)]
    norm = normalize_multiclass_shap_values(fake_list, n_samples=5, n_features=21, n_classes=3)
    assert norm.shape == (5, 21, 3)

    local = compute_local_shap(explainer, clf, X[0:1], predicted_class=1, top_k=6)
    assert len(local["top"]) == 6
    assert local["additive_diff"] < 1e-3
    for item in local["top"]:
        expected_dir = "naik" if item["shap"] >= 0 else "turun"
        assert item["direction"] == expected_dir


def test_llm_validator_adversarial_and_fallback() -> None:
    sample_payload = StructuredInterpretationInput.model_validate(
        {
            "case_id": "scr_test_01",
            "model": {"version": "xgb-v1.0.0", "technique": "class_weight+optuna"},
            "prediction": {
                "label": "Prediabetes",
                "label_code": 1,
                "probabilities": {"No Diabetes": 0.28, "Prediabetes": 0.49, "Diabetes": 0.23},
                "confidence_note": "sedang",
            },
            "input_summary": [
                {"feature": "BMI", "label_id": "Indeks Massa Tubuh", "value": 31.4, "display": "31,4 kg/m²"},
                {"feature": "HighBP", "label_id": "Tekanan Darah Tinggi", "value": 1, "display": "Ya"},
                {"feature": "PhysActivity", "label_id": "Aktivitas Fisik", "value": 1, "display": "Ya"},
            ],
            "shap_top": [
                {"rank": 1, "feature": "BMI", "label_id": "Indeks Massa Tubuh", "display": "31,4", "shap": 0.42, "direction": "naik"},
                {"rank": 2, "feature": "PhysActivity", "label_id": "Aktivitas Fisik", "display": "Ya", "shap": -0.18, "direction": "turun"},
            ],
            "allowed_features": FEATURE_ORDER,
            "language": "id",
            "audience": "awam",
        }
    )

    # 1. Deterministic fallback MUST pass 100% of validator rules
    fb_narrative = generate_fallback_narrative(sample_payload)
    val_ok = validate_narrative(fb_narrative, sample_payload)
    assert val_ok["passed"] is True
    assert val_ok["checks"]["forbidden_phrases"] is False

    # 2. Adversarial narrative with hallucinated feature & forbidden diagnosis/drug MUST fail
    bad_narrative = NarrativeOutputSchema(
        ringkasan="Berdasarkan hasil ini, Anda menderita Prediabetes dan harus minum metformin 500mg setiap hari.",
        faktor_yang_meningkatkan=[
            {"feature": "KadarGlukosaDarah", "penjelasan": "Kadar gula darah Anda tinggi."},
        ],
        faktor_yang_menurunkan=[
            {"feature": "BMI", "penjelasan": "BMI Anda sebesar 19,0 menurunkan risiko."},
        ],
        saran_umum=["Minum insulin 10mg."],
        kapan_ke_tenaga_kesehatan="Segera ke dokter untuk resep obat.",
    )
    val_bad = validate_narrative(bad_narrative, sample_payload)
    assert val_bad["passed"] is False
    assert val_bad["checks"]["feature_whitelist"] is False
    assert val_bad["checks"]["direction"] is False
    assert val_bad["checks"]["numeric"] is False
    assert val_bad["checks"]["forbidden_phrases"] is True


def test_fastapi_endpoints() -> None:
    client = TestClient(app)
    r_health = client.get("/health")
    assert r_health.status_code == 200
    assert r_health.json()["status"] == "ok"

    # Unauthorized without X-Service-Token
    r_unauth = client.post("/v1/assess", json={"case_id": "1", "features": {}})
    assert r_unauth.status_code == 401

    headers = {"X-Service-Token": "diaxplain-internal-secret-token-2026"}
    r_info = client.get("/v1/model/info", headers=headers)
    assert r_info.status_code == 200
    assert r_info.json()["loaded"] is True

    features = {f: 1 for f in FEATURE_ORDER}
    features["BMI"] = 31.4
    features["MentHlth"] = 2
    features["PhysHlth"] = 4
    features["GenHlth"] = 3
    features["Age"] = 9
    features["Education"] = 5
    features["Income"] = 6

    r_assess = client.post(
        "/v1/assess",
        headers=headers,
        json={"case_id": "scr_api_01", "features": features, "options": {"top_k": 6}},
    )
    assert r_assess.status_code == 200
    assess_data = r_assess.json()
    assert "prediction" in assess_data
    assert len(assess_data["shap"]["top"]) == 6

    # Test /v1/interpret endpoint
    r_interp = client.post(
        "/v1/interpret",
        headers=headers,
        json={
            "case_id": "scr_api_01",
            "model": {"version": assess_data["model_version"], "technique": assess_data["model_technique"]},
            "prediction": {
                "label": assess_data["prediction"]["label"],
                "label_code": assess_data["prediction"]["label_code"],
                "probabilities": assess_data["prediction"]["probabilities"],
                "confidence_note": assess_data["prediction"]["confidence_note"],
            },
            "input_summary": assess_data["input_summary"],
            "shap_top": assess_data["shap"]["top"],
            "allowed_features": FEATURE_ORDER,
            "language": "id",
            "audience": "awam",
        },
    )
    assert r_interp.status_code == 200
    interp_data = r_interp.json()
    assert interp_data["validation"]["passed"] is True
    assert "ringkasan" in interp_data["narrative"]

