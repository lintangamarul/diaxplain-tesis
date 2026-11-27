"""Bootstrap 95% confidence intervals and paired fold comparison."""

from __future__ import annotations

from typing import Any

import numpy as np
from scipy.stats import wilcoxon
from sklearn.metrics import f1_score, recall_score


def compute_bootstrap_ci(
    y_true: np.ndarray,
    y_pred: np.ndarray,
    n_iterations: int = 200,
    seed: int = 42,
) -> dict[str, Any]:
    """Compute 95% bootstrap confidence intervals for Macro-F1 and Prediabetes F1/Recall."""
    rng = np.random.default_rng(seed)
    y_true = np.asarray(y_true, dtype=int)
    y_pred = np.asarray(y_pred, dtype=int)
    n = len(y_true)

    macro_f1s = []
    prediab_f1s = []
    prediab_recalls = []

    for _ in range(n_iterations):
        idx = rng.integers(0, n, size=n)
        yt = y_true[idx]
        yp = y_pred[idx]
        macro_f1s.append(float(f1_score(yt, yp, average="macro", zero_division=0)))
        f1_per_cls = f1_score(yt, yp, labels=[0, 1, 2], average=None, zero_division=0)
        rec_per_cls = recall_score(yt, yp, labels=[0, 1, 2], average=None, zero_division=0)
        prediab_f1s.append(float(f1_per_cls[1]))
        prediab_recalls.append(float(rec_per_cls[1]))

    def _ci(vals: list[float]) -> dict[str, float]:
        arr = np.array(vals)
        return {
            "mean": round(float(np.mean(arr)), 4),
            "std": round(float(np.std(arr)), 4),
            "ci95_low": round(float(np.percentile(arr, 2.5)), 4),
            "ci95_high": round(float(np.percentile(arr, 97.5)), 4),
        }

    return {
        "n_iterations": n_iterations,
        "macro_f1": _ci(macro_f1s),
        "prediabetes_f1": _ci(prediab_f1s),
        "prediabetes_recall": _ci(prediab_recalls),
    }


def compare_folds_wilcoxon(
    baseline_scores: list[float],
    improved_scores: list[float],
) -> dict[str, Any]:
    """Compare per-fold scores using Wilcoxon signed-rank test + mean difference."""
    b = np.asarray(baseline_scores, dtype=float)
    imp = np.asarray(improved_scores, dtype=float)
    diff = imp - b
    mean_diff = float(np.mean(diff))

    if np.allclose(diff, 0.0):
        return {
            "mean_diff": 0.0,
            "p_value": 1.0,
            "statistic": 0.0,
            "note": "Skor per-fold identik.",
        }

    try:
        stat, p_val = wilcoxon(imp, b, zero_method="zsplit")
        return {
            "mean_diff": round(mean_diff, 4),
            "statistic": round(float(stat), 4),
            "p_value": round(float(p_val), 4),
            "note": "Uji Wilcoxon signed-rank berpasangan pada 5-fold CV.",
        }
    except Exception as exc:
        return {
            "mean_diff": round(mean_diff, 4),
            "statistic": None,
            "p_value": None,
            "note": f"Selisih rata-rata dilaporkan ({exc}).",
        }

