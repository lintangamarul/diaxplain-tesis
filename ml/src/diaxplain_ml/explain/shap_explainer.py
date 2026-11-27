"""SHAP Explainable AI Module (Global & Local TreeSHAP for 3-class XGBoost)."""

from __future__ import annotations

import json
from pathlib import Path
from typing import Any

import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
import numpy as np
import pandas as pd
import shap
from xgboost import XGBClassifier

from diaxplain_ml.features.codebook import (
    FEATURE_ORDER,
    FEATURE_SPECS,
    LABEL_DISPLAY_ID,
    LABEL_MAP,
    TARGET_COLUMN,
    format_feature_display,
)


def normalize_multiclass_shap_values(
    raw_shap: list[np.ndarray] | np.ndarray,
    n_samples: int,
    n_features: int = 21,
    n_classes: int = 3,
) -> np.ndarray:
    """
    Normalize SHAP values from different `shap` versions into a consistent 3D array
    of shape `(n_samples, n_features, n_classes)`.
    """
    if isinstance(raw_shap, list):
        # List of `n_classes` arrays, each `(n_samples, n_features)`
        stacked = np.stack([np.asarray(arr, dtype=np.float64) for arr in raw_shap], axis=-1)
        if stacked.ndim == 2 and n_samples == 1:
            stacked = stacked[np.newaxis, :, :]
        return stacked

    arr = np.asarray(raw_shap, dtype=np.float64)
    if arr.ndim == 2:
        if arr.shape == (n_features, n_classes) and n_samples == 1:
            return arr[np.newaxis, :, :]
        raise ValueError(f"Bentuk matriks SHAP 2D tidak sesuai multikelas: {arr.shape}")

    if arr.ndim == 3:
        if arr.shape == (n_samples, n_features, n_classes):
            return arr
        if arr.shape == (n_classes, n_samples, n_features):
            return np.transpose(arr, (1, 2, 0))
        if arr.shape == (n_samples, n_classes, n_features):
            return np.transpose(arr, (0, 2, 1))

    raise ValueError(f"Bentuk keluaran SHAP tidak dikenali: {arr.shape}")


def normalize_expected_values(expected_value: Any, n_classes: int = 3) -> np.ndarray:
    """Normalize TreeExplainer `expected_value` into 1D array of shape `(n_classes,)`."""
    arr = np.asarray(expected_value, dtype=np.float64).reshape(-1)
    if len(arr) != n_classes:
        raise ValueError(f"Panjang expected_value ({len(arr)}) != jumlah kelas ({n_classes}).")
    return arr


def compute_local_shap(
    explainer: shap.TreeExplainer,
    model: XGBClassifier,
    X_row: np.ndarray,
    predicted_class: int,
    top_k: int = 6,
) -> dict[str, Any]:
    """
    Compute local SHAP explanation for a single observation `X_row` (shape 1x21)
    with respect to `predicted_class` (and Prediabetes class=1 context).
    """
    X_row = np.asarray(X_row, dtype=np.float32).reshape(1, -1)
    raw_shap = explainer.shap_values(X_row)
    shap_3d = normalize_multiclass_shap_values(raw_shap, n_samples=1, n_features=len(FEATURE_ORDER), n_classes=3)
    base_values = normalize_expected_values(explainer.expected_value, n_classes=3)

    class_shap = shap_3d[0, :, predicted_class]
    base_val = float(base_values[predicted_class])

    # Margin verification: sum(shap) + base_value ≈ raw margin
    raw_margins = model.predict(X_row, output_margin=True)[0]
    predicted_margin = float(raw_margins[predicted_class])
    shap_sum_margin = float(np.sum(class_shap) + base_val)

    all_features_list: list[dict[str, Any]] = []
    for idx, fname in enumerate(FEATURE_ORDER):
        spec = FEATURE_SPECS[fname]
        raw_val = float(X_row[0, idx])
        clean_val = round(raw_val, 1) if fname == "BMI" else int(round(raw_val))
        sv = float(class_shap[idx])
        direction = "naik" if sv >= 0 else "turun"
        all_features_list.append(
            {
                "feature": fname,
                "label_id": spec["label_id"],
                "raw_value": clean_val,
                "display": format_feature_display(fname, clean_val),
                "shap": round(sv, 4),
                "abs_shap": round(abs(sv), 4),
                "direction": direction,
            }
        )

    # Sort descending by |SHAP|
    sorted_features = sorted(all_features_list, key=lambda x: x["abs_shap"], reverse=True)
    top_features = []
    for rank_idx, item in enumerate(sorted_features[:top_k], start=1):
        entry = {
            "rank": rank_idx,
            "feature": item["feature"],
            "label_id": item["label_id"],
            "raw_value": item["raw_value"],
            "display": item["display"],
            "shap": item["shap"],
            "direction": item["direction"],
        }
        top_features.append(entry)

    return {
        "explained_class": LABEL_MAP[predicted_class],
        "explained_class_id": LABEL_DISPLAY_ID[predicted_class],
        "explained_class_code": int(predicted_class),
        "base_value": round(base_val, 4),
        "margin_value": round(predicted_margin, 4),
        "additive_sum": round(shap_sum_margin, 4),
        "additive_diff": round(abs(predicted_margin - shap_sum_margin), 6),
        "top": top_features,
        "all_features": sorted_features,
    }


def run_global_shap_analysis(
    model: XGBClassifier,
    test_df: pd.DataFrame,
    shap_dir: str | Path,
    max_samples: int = 1200,
    seed: int = 42,
) -> dict[str, Any]:
    """
    Compute Global SHAP on a stratified subset of the test set (ensuring Prediabetes is well represented)
    and generate all required PNG plots & JSON rankings in `ml/artifacts/shap/`.
    """
    shap_dir = Path(shap_dir)
    shap_dir.mkdir(parents=True, exist_ok=True)

    # Stratified sample ensuring all Prediabetes rows in test (up to 400) + balanced samples from 0 & 2
    rng = np.random.default_rng(seed)
    sampled_indices: list[int] = []
    for cls_code, quota in [(0, 500), (1, 300), (2, 400)]:
        cls_idx = np.where(test_df[TARGET_COLUMN].to_numpy(dtype=int) == cls_code)[0]
        if len(cls_idx) > quota:
            chosen = rng.choice(cls_idx, size=quota, replace=False)
        else:
            chosen = cls_idx
        sampled_indices.extend(chosen.astype(int).tolist())

    sampled_indices = sorted(sampled_indices[:max_samples])
    sample_df = test_df.iloc[sampled_indices].reset_index(drop=True)
    X_sample = sample_df[FEATURE_ORDER].to_numpy(dtype=np.float32)

    with (shap_dir / "shap_sample_index.json").open("w", encoding="utf-8") as f:
        json.dump(
            {
                "n_samples": len(sampled_indices),
                "seed": seed,
                "indices_in_test": sampled_indices,
            },
            f,
            indent=2,
        )

    explainer = shap.TreeExplainer(model)
    raw_shap = explainer.shap_values(X_sample)
    shap_3d = normalize_multiclass_shap_values(
        raw_shap, n_samples=len(X_sample), n_features=len(FEATURE_ORDER), n_classes=3
    )
    base_values = normalize_expected_values(explainer.expected_value, n_classes=3)

    # Compute mean |SHAP| per feature per class and overall
    mean_abs_per_class = np.mean(np.abs(shap_3d), axis=0)  # shape (21, 3)
    mean_abs_overall = np.mean(mean_abs_per_class, axis=1)  # shape (21,)

    ranking_rows: list[dict[str, Any]] = []
    for i, fname in enumerate(FEATURE_ORDER):
        spec = FEATURE_SPECS[fname]
        ranking_rows.append(
            {
                "feature": fname,
                "label_id": spec["label_id"],
                "group": spec["group"],
                "mean_abs_shap_overall": round(float(mean_abs_overall[i]), 4),
                "mean_abs_shap_no_diabetes": round(float(mean_abs_per_class[i, 0]), 4),
                "mean_abs_shap_prediabetes": round(float(mean_abs_per_class[i, 1]), 4),
                "mean_abs_shap_diabetes": round(float(mean_abs_per_class[i, 2]), 4),
            }
        )

    ranking_rows.sort(key=lambda r: r["mean_abs_shap_overall"], reverse=True)
    for rank_i, row in enumerate(ranking_rows, start=1):
        row["rank"] = rank_i

    global_payload = {
        "n_samples": len(X_sample),
        "base_values": {LABEL_MAP[c]: round(float(base_values[c]), 4) for c in [0, 1, 2]},
        "ranking": ranking_rows,
        "top_5_features": [r["feature"] for r in ranking_rows[:5]],
    }
    with (shap_dir / "shap_global.json").open("w", encoding="utf-8") as f:
        json.dump(global_payload, f, indent=2, ensure_ascii=False)

    # 1. Overall stacked bar chart (`shap_bar_overall.png`)
    top15 = ranking_rows[:15][::-1]
    labels_id = [r["label_id"] for r in top15]
    v0 = np.array([r["mean_abs_shap_no_diabetes"] for r in top15])
    v1 = np.array([r["mean_abs_shap_prediabetes"] for r in top15])
    v2 = np.array([r["mean_abs_shap_diabetes"] for r in top15])

    fig, ax = plt.subplots(figsize=(9.5, 6.2), dpi=220)
    ax.barh(labels_id, v0, color="#10b981", label="Tidak Diabetes (Kelas 0)", height=0.65)
    ax.barh(labels_id, v1, left=v0, color="#f59e0b", label="Prediabetes (Kelas 1)", height=0.65)
    ax.barh(labels_id, v2, left=v0 + v1, color="#ef4444", label="Diabetes (Kelas 2)", height=0.65)
    ax.set_title("Tingkat Kepentingan Fitur Global (Mean |SHAP| Lintas Kelas — Top 15)", fontsize=11, fontweight="bold")
    ax.set_xlabel("Akumulasi Rata-rata |Nilai SHAP| (Log-Odds)")
    ax.legend(loc="lower right", frameon=True)
    ax.grid(axis="x", linestyle="--", alpha=0.4)
    fig.tight_layout()
    fig.savefig(shap_dir / "shap_bar_overall.png", dpi=220)
    plt.close(fig)

    # 2. Per-class beeswarm-style distribution summary (`shap_summary_class0/1/2.png`)
    for cls_code in [0, 1, 2]:
        cls_label = LABEL_DISPLAY_ID[cls_code]
        cls_vals = shap_3d[:, :, cls_code]  # (n, 21)
        cls_order = np.argsort(np.mean(np.abs(cls_vals), axis=0))[-12:]  # top 12 features

        fig, ax = plt.subplots(figsize=(8.8, 5.4), dpi=220)
        for y_pos, f_idx in enumerate(cls_order):
            f_shap = cls_vals[:, f_idx]
            f_raw = X_sample[:, f_idx]
            # Normalize feature values 0..1 for color mapping
            f_min, f_max = float(np.min(f_raw)), float(np.max(f_raw))
            f_norm = (f_raw - f_min) / max(f_max - f_min, 1e-6)
            jitter = rng.normal(0, 0.12, size=len(f_shap))
            sc = ax.scatter(
                f_shap,
                np.full_like(f_shap, y_pos) + jitter,
                c=f_norm,
                cmap="coolwarm",
                s=10,
                alpha=0.65,
                vmin=0.0,
                vmax=1.0,
            )
        ax.axvline(0.0, color="#1e293b", linewidth=1.0, linestyle="--")
        ax.set_yticks(range(len(cls_order)), [FEATURE_SPECS[FEATURE_ORDER[i]]["label_id"] for i in cls_order])
        ax.set_title(f"Distribusi Dampak Fitur SHAP — Kelas {cls_label}", fontsize=11, fontweight="bold")
        ax.set_xlabel(f"Nilai SHAP terhadap Log-Odds Kelas {cls_label}")
        cbar = fig.colorbar(sc, ax=ax, fraction=0.035, pad=0.03)
        cbar.set_label("Nilai Fitur (Rendah → Tinggi)")
        ax.grid(axis="x", linestyle="--", alpha=0.35)
        fig.tight_layout()
        fig.savefig(shap_dir / f"shap_summary_class{cls_code}.png", dpi=220)
        plt.close(fig)

    # 3. Dependence plot for Top 5 features on Diabetes class (`shap_dependence_top5.png`)
    top5_names = [r["feature"] for r in ranking_rows[:5]]
    fig, axes = plt.subplots(1, 5, figsize=(16.0, 3.8), dpi=220)
    fig.suptitle("SHAP Dependence Plot — 5 Fitur Teratas (Kontribusi terhadap Kelas Diabetes)", fontsize=12, fontweight="bold")
    for idx_plot, fname in enumerate(top5_names):
        f_idx = FEATURE_ORDER.index(fname)
        ax = axes[idx_plot]
        x_vals = X_sample[:, f_idx]
        y_vals = shap_3d[:, f_idx, 2]
        ax.scatter(x_vals, y_vals, c=x_vals, cmap="viridis", s=12, alpha=0.6)
        ax.axhline(0.0, color="#64748b", linestyle="--", linewidth=0.9)
        ax.set_title(FEATURE_SPECS[fname]["label_id"][:26], fontsize=9, fontweight="bold")
        ax.set_xlabel(fname, fontsize=8)
        if idx_plot == 0:
            ax.set_ylabel("Nilai SHAP (Diabetes)", fontsize=9)
        ax.grid(True, linestyle="--", alpha=0.3)
    fig.tight_layout()
    fig.savefig(shap_dir / "shap_dependence_top5.png", dpi=220)
    plt.close(fig)

    return global_payload

