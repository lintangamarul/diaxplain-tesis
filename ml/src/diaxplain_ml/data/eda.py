"""Exploratory Data Analysis (EDA) artifact generator for Chapter III & IV."""

from __future__ import annotations

import json
from pathlib import Path
from typing import Any

import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
import numpy as np
import pandas as pd

from diaxplain_ml.features.codebook import (
    FEATURE_ORDER,
    FEATURE_SPECS,
    LABEL_DISPLAY_ID,
    LABEL_MAP,
    TARGET_COLUMN,
)


def run_eda_pipeline(
    df: pd.DataFrame,
    dataset_sha256: str,
    eda_dir: str | Path,
    source_type: str = "kaggle_full_253k",
) -> dict[str, Any]:
    """Generate all required EDA artifacts in `ml/artifacts/eda/`."""
    eda_dir = Path(eda_dir)
    eda_dir.mkdir(parents=True, exist_ok=True)

    # 1. Dataset hash
    (eda_dir / "dataset_hash.txt").write_text(f"{dataset_sha256}\n", encoding="utf-8")

    # 2. Class distribution
    total = len(df)
    counts = df[TARGET_COLUMN].value_counts().sort_index()
    dist_rows = []
    for cls_code in [0, 1, 2]:
        c = int(counts.get(cls_code, 0))
        pct = round(100.0 * c / max(1, total), 3)
        dist_rows.append(
            {
                "class_code": cls_code,
                "label": LABEL_MAP[cls_code],
                "label_id": LABEL_DISPLAY_ID[cls_code],
                "count": c,
                "percentage": pct,
            }
        )
    dist_df = pd.DataFrame(dist_rows)
    dist_df.to_csv(eda_dir / "class_distribution.csv", index=False)

    # Plot class distribution (>=200 DPI, Indonesian labels)
    fig, ax = plt.subplots(figsize=(7.5, 4.5), dpi=220)
    colors = ["#10b981", "#f59e0b", "#ef4444"]
    bars = ax.bar(
        [r["label_id"] for r in dist_rows],
        [r["count"] for r in dist_rows],
        color=colors,
        edgecolor="#1e293b",
        linewidth=0.8,
        width=0.55,
    )
    ax.set_title("Distribusi Kelas Status Diabetes (Diabetes_012)", fontsize=12, fontweight="bold", pad=12)
    ax.set_xlabel("Kelas Target", fontsize=10)
    ax.set_ylabel("Jumlah Responden", fontsize=10)
    ax.grid(axis="y", linestyle="--", alpha=0.4)
    for bar, r in zip(bars, dist_rows):
        ax.text(
            bar.get_x() + bar.get_width() / 2.0,
            bar.get_height() + total * 0.015,
            f"{r['count']:,}\n({r['percentage']:.2f}%)",
            ha="center",
            va="bottom",
            fontsize=9,
            fontweight="bold",
        )
    ax.set_ylim(0, max(r["count"] for r in dist_rows) * 1.18)
    fig.tight_layout()
    fig.savefig(eda_dir / "class_distribution.png", dpi=220)
    plt.close(fig)

    # 3. Dtypes and missing values
    dtype_rows = []
    for col in [TARGET_COLUMN] + FEATURE_ORDER:
        s = df[col]
        dtype_rows.append(
            {
                "column": col,
                "dtype": str(s.dtype),
                "missing_count": int(s.isna().sum()),
                "min": float(s.min()),
                "max": float(s.max()),
                "mean": round(float(s.mean()), 4),
                "std": round(float(s.std()), 4),
                "median": float(s.median()),
            }
        )
    pd.DataFrame(dtype_rows).to_csv(eda_dir / "dtypes_missing.csv", index=False)

    # 4. Duplicates analysis
    dup_mask = df.duplicated(keep="first")
    dup_total = int(dup_mask.sum())
    dup_by_class = {
        LABEL_MAP[int(k)]: int(v)
        for k, v in df.loc[dup_mask, TARGET_COLUMN].value_counts().sort_index().items()
    }
    duplicates_info = {
        "total_rows": total,
        "duplicate_rows": dup_total,
        "duplicate_percentage": round(100.0 * dup_total / max(1, total), 3),
        "unique_rows": int(total - dup_total),
        "duplicates_by_class": dup_by_class,
    }
    with (eda_dir / "duplicates.json").open("w", encoding="utf-8") as f:
        json.dump(duplicates_info, f, indent=2, ensure_ascii=False)

    # 5. Feature vs Target summary & Spearman correlation
    fvt_rows = []
    for fname in FEATURE_ORDER:
        spec = FEATURE_SPECS[fname]
        corr = float(df[fname].corr(df[TARGET_COLUMN], method="spearman"))
        means = df.groupby(TARGET_COLUMN)[fname].mean().to_dict()
        fvt_rows.append(
            {
                "feature": fname,
                "label_id": spec["label_id"],
                "group": spec["group"],
                "mean_no_diabetes": round(float(means.get(0, 0.0)), 4),
                "mean_prediabetes": round(float(means.get(1, 0.0)), 4),
                "mean_diabetes": round(float(means.get(2, 0.0)), 4),
                "spearman_corr": round(corr, 4),
                "abs_spearman_corr": round(abs(corr), 4),
            }
        )
    fvt_df = pd.DataFrame(fvt_rows).sort_values("abs_spearman_corr", ascending=False)
    fvt_df.to_csv(eda_dir / "feature_vs_target.csv", index=False)

    # Plot Top 12 correlated features
    top_fvt = fvt_df.head(12).iloc[::-1]
    fig, ax = plt.subplots(figsize=(8.5, 5.2), dpi=220)
    bar_colors = ["#0284c7" if c >= 0 else "#e11d48" for c in top_fvt["spearman_corr"]]
    ax.barh(top_fvt["label_id"], top_fvt["spearman_corr"], color=bar_colors, edgecolor="#0f172a", height=0.65)
    ax.axvline(0, color="#334155", linewidth=0.9)
    ax.set_title("Korelasi Spearman Fitur terhadap Status Diabetes (Top 12)", fontsize=11, fontweight="bold")
    ax.set_xlabel("Koefisien Korelasi Spearman (ρ)", fontsize=10)
    ax.grid(axis="x", linestyle="--", alpha=0.4)
    fig.tight_layout()
    fig.savefig(eda_dir / "feature_vs_target.png", dpi=220)
    plt.close(fig)

    # 6. Outliers BMI summary
    bmi_s = df["BMI"]
    q25, q50, q75 = np.percentile(bmi_s, [25, 50, 75])
    iqr = q75 - q25
    outliers_bmi = {
        "min": float(bmi_s.min()),
        "p01": round(float(np.percentile(bmi_s, 1)), 2),
        "q25": round(float(q25), 2),
        "median": round(float(q50), 2),
        "q75": round(float(q75), 2),
        "p99": round(float(np.percentile(bmi_s, 99)), 2),
        "max": float(bmi_s.max()),
        "iqr": round(float(iqr), 2),
        "above_iqr_upper_fence_count": int((bmi_s > (q75 + 1.5 * iqr)).sum()),
        "decision": "Dipertahankan pada pelatihan karena algoritma pohon keputusan (XGBoost) invarian terhadap transformasi monotonik dan tahan terhadap outlier; pembatasan 12–98 diterapkan pada validasi form aplikasi.",
    }
    with (eda_dir / "outliers_bmi.json").open("w", encoding="utf-8") as f:
        json.dump(outliers_bmi, f, indent=2, ensure_ascii=False)

    summary = {
        "dataset_sha256": dataset_sha256,
        "source_type": source_type,
        "total_rows": total,
        "total_features": len(FEATURE_ORDER),
        "class_distribution": dist_rows,
        "duplicates": duplicates_info,
        "outliers_bmi": outliers_bmi,
    }
    with (eda_dir / "eda_summary.json").open("w", encoding="utf-8") as f:
        json.dump(summary, f, indent=2, ensure_ascii=False)

    return summary

