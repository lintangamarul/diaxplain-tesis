"""Active XGBoost + TreeSHAP Model Registry for FastAPI service."""

from __future__ import annotations

import json
import os
from pathlib import Path
from typing import Any

import shap
from xgboost import XGBClassifier

from diaxplain_ml.features.codebook import LABEL_DISPLAY_ID, LABEL_MAP, get_feature_schema_dict


class ModelRegistry:
    def __init__(self, base_dir: str | Path | None = None) -> None:
        self.base_dir = Path(base_dir or Path(__file__).resolve().parents[3])
        self.model: XGBClassifier | None = None
        self.explainer: shap.TreeExplainer | None = None
        self.metadata: dict[str, Any] = {}
        self.model_dir: Path = self.base_dir / os.getenv("MODEL_DIR", "artifacts/model_v1")

    def load(self, custom_dir: str | Path | None = None) -> bool:
        if custom_dir:
            p = Path(custom_dir)
            self.model_dir = p if p.is_absolute() else (self.base_dir / p)
        else:
            env_dir = os.getenv("MODEL_DIR", "artifacts/model_v1")
            p = Path(env_dir)
            self.model_dir = p if p.is_absolute() else (self.base_dir / p)

        model_file = self.model_dir / "model.json"
        metrics_file = self.model_dir / "metrics.json"
        if not model_file.exists():
            self.model = None
            self.explainer = None
            return False

        clf = XGBClassifier()
        clf.load_model(str(model_file))
        self.model = clf
        self.explainer = shap.TreeExplainer(clf)

        if metrics_file.exists():
            with metrics_file.open("r", encoding="utf-8") as f:
                self.metadata = json.load(f)
        else:
            self.metadata = {
                "model_version": "xgb-v1.0.0",
                "technique": "class_weight+optuna",
                "algorithm": "XGBoost",
            }
        return True

    @property
    def is_loaded(self) -> bool:
        return self.model is not None and self.explainer is not None

    def get_info(self) -> dict[str, Any]:
        return {
            "loaded": self.is_loaded,
            "model_version": self.metadata.get("model_version", "xgb-v1.0.0"),
            "experiment_code": self.metadata.get("experiment_code", "E5"),
            "name": self.metadata.get("name", "XGBoost Best Model"),
            "technique": self.metadata.get("technique", "class_weight+optuna"),
            "params": self.metadata.get("params", {}),
            "dataset_hash": self.metadata.get("dataset_hash", ""),
            "trained_at": self.metadata.get("trained_at", ""),
            "cv_metrics": self.metadata.get("cv_metrics", {}),
            "test_metrics": self.metadata.get("test_metrics", {}),
            "feature_schema": get_feature_schema_dict(),
            "label_map": {
                "code_to_label": {str(k): v for k, v in LABEL_MAP.items()},
                "code_to_display_id": {str(k): v for k, v in LABEL_DISPLAY_ID.items()},
            },
        }

