"""Feature validation, ordering, and post-SMOTE rounding utilities."""

from __future__ import annotations

from typing import Any

import numpy as np
import pandas as pd

from .codebook import (
    CATEGORICAL_INDICES,
    FEATURE_ORDER,
    FEATURE_SPECS,
    format_feature_display,
)


def validate_and_order_features(features: dict[str, Any]) -> tuple[np.ndarray, list[str]]:
    """Validate 21 input features against schema bounds and return 1x21 float32 array + warnings."""
    missing = [f for f in FEATURE_ORDER if f not in features]
    if missing:
        raise ValueError(f"Fitur wajib belum lengkap: {', '.join(missing)}")

    values: list[float] = []
    warnings: list[str] = []

    for fname in FEATURE_ORDER:
        raw = features[fname]
        spec = FEATURE_SPECS[fname]
        try:
            val = float(raw)
        except (TypeError, ValueError) as exc:
            raise ValueError(f"Nilai fitur '{fname}' harus berupa angka valid.") from exc

        min_v = float(spec["min"])
        max_v = float(spec["max"])
        if val < min_v or val > max_v:
            raise ValueError(
                f"Fitur '{fname}' ({spec['label_id']}) bernilai {val} di luar rentang valid [{min_v}, {max_v}]."
            )

        if spec["type"] in ("binary", "ordinal", "continuous_int"):
            if abs(val - round(val)) > 1e-6:
                raise ValueError(f"Fitur '{fname}' harus berupa bilangan bulat dalam rentang [{int(min_v)}, {int(max_v)}].")
            val = float(int(round(val)))

        if fname == "BMI" and (val < 14.0 or val > 65.0):
            warnings.append(
                f"Nilai BMI ({val:.1f}) berada pada ekor distribusi ekstrem dataset survei; pastikan input berat & tinggi badan sudah benar."
            )

        values.append(val)

    arr = np.array([values], dtype=np.float32)
    return arr, warnings


def build_input_summary(features: dict[str, Any]) -> list[dict[str, Any]]:
    """Build structured input summary list for API response and LLM input."""
    summary: list[dict[str, Any]] = []
    for fname in FEATURE_ORDER:
        spec = FEATURE_SPECS[fname]
        raw = features[fname]
        val = round(float(raw), 1) if fname == "BMI" else int(round(float(raw)))
        summary.append(
            {
                "feature": fname,
                "label_id": spec["label_id"],
                "group": spec["group"],
                "value": val,
                "display": format_feature_display(fname, val),
            }
        )
    return summary


def round_categorical_columns(X: np.ndarray | pd.DataFrame) -> np.ndarray:
    """Round categorical/ordinal columns after standard SMOTE to preserve valid discrete codes."""
    arr = np.asarray(X, dtype=np.float32).copy()
    for idx in CATEGORICAL_INDICES:
        fname = FEATURE_ORDER[idx]
        spec = FEATURE_SPECS[fname]
        arr[:, idx] = np.clip(np.round(arr[:, idx]), spec["min"], spec["max"])
    # Also round integer day counts (MentHlth, PhysHlth)
    for fname in ("MentHlth", "PhysHlth"):
        idx = FEATURE_SPECS[fname]["index"]
        arr[:, idx] = np.clip(np.round(arr[:, idx]), 0, 30)
    return arr

