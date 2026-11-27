"""Multiclass evaluation metrics for 3-class Diabetes status classification."""

from __future__ import annotations

from typing import Any

import numpy as np
from sklearn.metrics import (
    accuracy_score,
    balanced_accuracy_score,
    confusion_matrix,
    f1_score,
    log_loss,
    matthews_corrcoef,
    precision_recall_fscore_support,
    roc_auc_score,
    roc_curve,
)

from diaxplain_ml.features.codebook import LABEL_DISPLAY_ID, LABEL_MAP


def compute_multiclass_metrics(
    y_true: np.ndarray,
    y_pred: np.ndarray,
    y_prob: np.ndarray,
) -> dict[str, Any]:
    """Compute complete outline metrics + supporting metrics for 3-class Diabetes_012."""
    y_true = np.asarray(y_true, dtype=int)
    y_pred = np.asarray(y_pred, dtype=int)
    y_prob = np.asarray(y_prob, dtype=float)

    acc = float(accuracy_score(y_true, y_pred))
    prec_c, rec_c, f1_c, sup_c = precision_recall_fscore_support(
        y_true, y_pred, labels=[0, 1, 2], zero_division=0
    )
    prec_macro = float(np.mean(prec_c))
    rec_macro = float(np.mean(rec_c))
    f1_macro = float(f1_score(y_true, y_pred, average="macro", zero_division=0))

    prec_weighted, rec_weighted, f1_weighted, _ = precision_recall_fscore_support(
        y_true, y_pred, average="weighted", zero_division=0
    )

    # One-vs-Rest ROC-AUC per class & macro
    roc_per_class: dict[str, float] = {}
    try:
        roc_ovr_all = roc_auc_score(y_true, y_prob, multi_class="ovr", average=None, labels=[0, 1, 2])
        for cls_idx, val in enumerate(roc_ovr_all):
            roc_per_class[LABEL_MAP[cls_idx]] = round(float(val), 4)
        roc_macro = round(float(np.mean(roc_ovr_all)), 4)
        roc_weighted = round(
            float(roc_auc_score(y_true, y_prob, multi_class="ovr", average="weighted", labels=[0, 1, 2])), 4
        )
    except Exception:
        roc_per_class = {LABEL_MAP[i]: 0.5 for i in [0, 1, 2]}
        roc_macro = 0.5
        roc_weighted = 0.5

    cm_counts = confusion_matrix(y_true, y_pred, labels=[0, 1, 2])
    row_sums = np.maximum(cm_counts.sum(axis=1, keepdims=True), 1)
    cm_norm = np.round(cm_counts / row_sums, 4)

    per_class: dict[str, dict[str, Any]] = {}
    for idx in [0, 1, 2]:
        cls_name = LABEL_MAP[idx]
        per_class[cls_name] = {
            "class_code": idx,
            "label_id": LABEL_DISPLAY_ID[idx],
            "precision": round(float(prec_c[idx]), 4),
            "recall": round(float(rec_c[idx]), 4),
            "f1": round(float(f1_c[idx]), 4),
            "roc_auc_ovr": roc_per_class.get(cls_name, 0.5),
            "support": int(sup_c[idx]),
        }

    try:
        mlogloss = round(float(log_loss(y_true, y_prob, labels=[0, 1, 2])), 4)
    except Exception:
        mlogloss = 0.0

    return {
        "accuracy": round(acc, 4),
        "macro_precision": round(prec_macro, 4),
        "macro_recall": round(rec_macro, 4),
        "macro_f1": round(f1_macro, 4),
        "weighted_precision": round(float(prec_weighted), 4),
        "weighted_recall": round(float(rec_weighted), 4),
        "weighted_f1": round(float(f1_weighted), 4),
        "roc_auc_ovr_macro": roc_macro,
        "roc_auc_ovr_weighted": roc_weighted,
        "mlogloss": mlogloss,
        "per_class": per_class,
        "confusion_matrix": {
            "labels": [LABEL_MAP[0], LABEL_MAP[1], LABEL_MAP[2]],
            "labels_id": [LABEL_DISPLAY_ID[0], LABEL_DISPLAY_ID[1], LABEL_DISPLAY_ID[2]],
            "counts": cm_counts.astype(int).tolist(),
            "normalized_by_row": cm_norm.tolist(),
        },
        "supporting_metrics": {
            "balanced_accuracy": round(float(balanced_accuracy_score(y_true, y_pred)), 4),
            "mcc": round(float(matthews_corrcoef(y_true, y_pred)), 4),
        },
    }


def compute_roc_curves_ovr(
    y_true: np.ndarray,
    y_prob: np.ndarray,
    max_points: int = 60,
) -> dict[str, Any]:
    """Compute downsampled One-vs-Rest ROC curves for JSON/Chart.js & Matplotlib plotting."""
    y_true = np.asarray(y_true, dtype=int)
    y_prob = np.asarray(y_prob, dtype=float)
    curves: dict[str, Any] = {}

    for cls_idx in [0, 1, 2]:
        cls_name = LABEL_MAP[cls_idx]
        binary_true = (y_true == cls_idx).astype(int)
        fpr, tpr, _ = roc_curve(binary_true, y_prob[:, cls_idx])
        if len(fpr) > max_points:
            idx = np.linspace(0, len(fpr) - 1, max_points).astype(int)
            fpr = fpr[idx]
            tpr = tpr[idx]
        auc_val = float(roc_auc_score(binary_true, y_prob[:, cls_idx])) if len(np.unique(binary_true)) > 1 else 0.5
        curves[cls_name] = {
            "label_id": LABEL_DISPLAY_ID[cls_idx],
            "auc": round(auc_val, 4),
            "fpr": [round(float(x), 4) for x in fpr],
            "tpr": [round(float(x), 4) for x in tpr],
        }
    return curves

