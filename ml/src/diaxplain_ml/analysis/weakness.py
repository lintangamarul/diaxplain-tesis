"""Weakness Gate (RM2) — Rule-based empirical identification of baseline model weaknesses."""

from __future__ import annotations

import json
from pathlib import Path
from typing import Any

import numpy as np


def evaluate_baseline_weakness(
    baseline_cv_summary: dict[str, Any],
    baseline_test_metrics: dict[str, Any],
    train_macro_f1: float,
    thresholds: dict[str, Any] | None = None,
) -> dict[str, Any]:
    """
    Apply formal Weakness Gate rules (Section 7.5) to baseline metrics to determine
    which performance improvement techniques are empirically justified.
    """
    cfg = {
        "f1_minority_ratio_threshold": 0.5,
        "recall_gap_threshold": 0.30,
        "acc_macro_f1_gap_threshold": 0.15,
        "train_cv_gap_threshold": 0.05,
        "cv_sd_threshold": 0.02,
    }
    if thresholds:
        cfg.update(thresholds)

    per_class = baseline_test_metrics["per_class"]
    f1_no = float(per_class["No Diabetes"]["f1"])
    f1_pre = float(per_class["Prediabetes"]["f1"])
    f1_dia = float(per_class["Diabetes"]["f1"])

    rec_no = float(per_class["No Diabetes"]["recall"])
    rec_pre = float(per_class["Prediabetes"]["recall"])
    rec_dia = float(per_class["Diabetes"]["recall"])

    other_f1_mean = (f1_no + f1_dia) / 2.0
    f1_minority_ratio = round(f1_pre / max(other_f1_mean, 1e-6), 4)
    max_recall_gap = round(max(rec_no, rec_pre, rec_dia) - min(rec_no, rec_pre, rec_dia), 4)

    acc = float(baseline_test_metrics["accuracy"])
    macro_f1 = float(baseline_test_metrics["macro_f1"])
    acc_macro_gap = round(acc - macro_f1, 4)

    cv_macro_f1_mean = float(baseline_cv_summary.get("macro_f1_mean", macro_f1))
    cv_macro_f1_std = float(baseline_cv_summary.get("macro_f1_std", 0.0))
    train_cv_gap = round(float(train_macro_f1) - cv_macro_f1_mean, 4)

    findings: list[dict[str, Any]] = []
    recommended_techniques: set[str] = set()

    # Rule 1: Minority class bias (Prediabetes)
    r1_triggered = (
        f1_minority_ratio < cfg["f1_minority_ratio_threshold"]
        or max_recall_gap > cfg["recall_gap_threshold"]
    )
    findings.append(
        {
            "rule_id": "W1_MINORITY_CLASS_BIAS",
            "triggered": bool(r1_triggered),
            "metric_values": {
                "prediabetes_f1": f1_pre,
                "prediabetes_recall": rec_pre,
                "other_classes_f1_mean": round(other_f1_mean, 4),
                "f1_minority_ratio": f1_minority_ratio,
                "max_recall_gap": max_recall_gap,
            },
            "threshold": {
                "f1_minority_ratio_min": cfg["f1_minority_ratio_threshold"],
                "recall_gap_max": cfg["recall_gap_threshold"],
            },
            "diagnosis": (
                f"Model baseline mengalami bias ekstrem ke kelas mayoritas: F1 Prediabetes ({f1_pre:.4f}) "
                f"dan Recall Prediabetes ({rec_pre:.4f}) sangat rendah dibanding No Diabetes ({rec_no:.4f})."
                if r1_triggered
                else "Rasio F1 kelas minoritas berada di atas ambang batas."
            ),
            "recommended_action": "Class Imbalance Handling (Class Weight, SMOTE, SMOTENC, SMOTETomek/SMOTEENN)",
        }
    )
    if r1_triggered:
        recommended_techniques.update(["class_weight", "smote", "smotenc", "hybrid_sampling"])

    # Rule 2: Accuracy illusion due to severe class imbalance
    r2_triggered = acc_macro_gap > cfg["acc_macro_f1_gap_threshold"]
    findings.append(
        {
            "rule_id": "W2_ACCURACY_PARADOX",
            "triggered": bool(r2_triggered),
            "metric_values": {
                "accuracy": acc,
                "macro_f1": macro_f1,
                "accuracy_minus_macro_f1": acc_macro_gap,
            },
            "threshold": {"acc_macro_f1_gap_max": cfg["acc_macro_f1_gap_threshold"]},
            "diagnosis": (
                f"Terjadi Accuracy Paradox: Accuracy keseluruhan tinggi ({acc:.4f}), namun Macro-F1 rendah ({macro_f1:.4f}, "
                f"selisih {acc_macro_gap:.4f} > {cfg['acc_macro_f1_gap_threshold']})."
                if r2_triggered
                else "Selisih Accuracy dan Macro-F1 dalam batas wajar."
            ),
            "recommended_action": "Gunakan Macro-F1 & F1/Recall Prediabetes sebagai kriteria seleksi utama serta terapkan penanganan class imbalance.",
        }
    )
    if r2_triggered:
        recommended_techniques.update(["class_weight", "smote", "smotenc"])

    # Rule 3: Default hyperparameter suboptimality / train-CV gap
    r3_triggered = (
        train_cv_gap > cfg["train_cv_gap_threshold"]
        or cv_macro_f1_std > cfg["cv_sd_threshold"]
        or macro_f1 < 0.55
    )
    findings.append(
        {
            "rule_id": "W3_HYPERPARAMETER_SUBOPTIMALITY",
            "triggered": bool(r3_triggered),
            "metric_values": {
                "train_macro_f1": round(float(train_macro_f1), 4),
                "cv_macro_f1_mean": round(cv_macro_f1_mean, 4),
                "train_minus_cv_gap": train_cv_gap,
                "cv_macro_f1_std": round(cv_macro_f1_std, 4),
            },
            "threshold": {
                "train_cv_gap_max": cfg["train_cv_gap_threshold"],
                "cv_sd_max": cfg["cv_sd_threshold"],
            },
            "diagnosis": (
                f"Konfigurasi hyperparameter default XGBoost belum optimal untuk batas keputusan multikelas "
                f"(selisih Train–CV Macro-F1 = {train_cv_gap:.4f})."
            ),
            "recommended_action": "Hyperparameter Optimization menggunakan Optuna (TPE Sampler) dengan objektif Macro-F1 CV.",
        }
    )
    if r3_triggered:
        recommended_techniques.update(["optuna_hpo", "combined_imbalance_and_optuna"])

    # Confusion matrix top misclassified pair
    cm = np.array(baseline_test_metrics["confusion_matrix"]["counts"], dtype=int)
    labels = baseline_test_metrics["confusion_matrix"]["labels"]
    off_diag = []
    for i in range(3):
        for j in range(3):
            if i != j:
                row_total = max(int(cm[i].sum()), 1)
                off_diag.append(
                    {
                        "actual": labels[i],
                        "predicted": labels[j],
                        "count": int(cm[i, j]),
                        "row_percentage": round(100.0 * int(cm[i, j]) / row_total, 2),
                    }
                )
    off_diag.sort(key=lambda x: x["row_percentage"], reverse=True)

    return {
        "gate_passed_for_improvement": len(recommended_techniques) > 0,
        "thresholds_used": cfg,
        "findings": findings,
        "top_misclassifications": off_diag[:4],
        "recommended_techniques": sorted(list(recommended_techniques)),
        "summary_conclusion": (
            "Evaluasi baseline XGBoost membuktikan adanya kelemahan struktural akibat ketidakseimbangan kelas ekstrem "
            f"(Recall Prediabetes = {rec_pre:.4f}, F1 Prediabetes = {f1_pre:.4f}) serta belum optimalnya batas keputusan "
            "pada hyperparameter default. Oleh karena itu, skenario peningkatan performa E1–E6 (Class Weight, SMOTE/SMOTENC, "
            "Hybrid Sampling, dan Optimasi Hyperparameter Optuna) secara empiris direkomendasikan untuk diuji."
        ),
    }


def save_weakness_report(report: dict[str, Any], path: str | Path) -> None:
    path = Path(path)
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", encoding="utf-8") as f:
        json.dump(report, f, indent=2, ensure_ascii=False)

