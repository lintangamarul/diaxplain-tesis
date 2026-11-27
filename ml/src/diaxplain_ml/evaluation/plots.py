"""High-DPI Matplotlib plots (Indonesian labels) for Confusion Matrix and One-vs-Rest ROC."""

from __future__ import annotations

from pathlib import Path
from typing import Any

import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
import numpy as np


def save_confusion_matrix_plot(
    cm_data: dict[str, Any],
    output_path: str | Path,
    title: str = "Confusion Matrix Klasifikasi Status Diabetes",
) -> None:
    """Save side-by-side raw count & row-normalized Confusion Matrix PNG (>=200 DPI)."""
    output_path = Path(output_path)
    output_path.parent.mkdir(parents=True, exist_ok=True)

    counts = np.array(cm_data["counts"], dtype=int)
    norm = np.array(cm_data["normalized_by_row"], dtype=float)
    labels = cm_data.get("labels_id") or cm_data["labels"]

    fig, axes = plt.subplots(1, 2, figsize=(11.5, 4.8), dpi=220)
    fig.suptitle(title, fontsize=12, fontweight="bold", y=0.98)

    # Panel 1: Counts
    im0 = axes[0].imshow(counts, cmap="Blues")
    axes[0].set_title("Jumlah Sampel (Absolut)", fontsize=10, fontweight="bold")
    axes[0].set_xticks(range(3), labels, rotation=15)
    axes[0].set_yticks(range(3), labels)
    axes[0].set_xlabel("Kelas Prediksi")
    axes[0].set_ylabel("Kelas Aktual")
    max_c = counts.max() if counts.size else 1
    for i in range(3):
        for j in range(3):
            color = "white" if counts[i, j] > 0.55 * max_c else "#0f172a"
            axes[0].text(j, i, f"{counts[i, j]:,}", ha="center", va="center", color=color, fontweight="bold")
    fig.colorbar(im0, ax=axes[0], fraction=0.046, pad=0.04)

    # Panel 2: Row-normalized (Recall per class)
    im1 = axes[1].imshow(norm, cmap="YlGnBu", vmin=0.0, vmax=1.0)
    axes[1].set_title("Proporsi per Baris (Recall per Kelas)", fontsize=10, fontweight="bold")
    axes[1].set_xticks(range(3), labels, rotation=15)
    axes[1].set_yticks(range(3), labels)
    axes[1].set_xlabel("Kelas Prediksi")
    axes[1].set_ylabel("Kelas Aktual")
    for i in range(3):
        for j in range(3):
            color = "white" if norm[i, j] > 0.55 else "#0f172a"
            axes[1].text(j, i, f"{norm[i, j]*100:.1f}%", ha="center", va="center", color=color, fontweight="bold")
    fig.colorbar(im1, ax=axes[1], fraction=0.046, pad=0.04)

    fig.tight_layout()
    fig.savefig(output_path, dpi=220)
    plt.close(fig)


def save_roc_curve_plot(
    roc_data: dict[str, Any],
    output_path: str | Path,
    title: str = "Kurva ROC One-vs-Rest (OvR) per Kelas",
) -> None:
    """Save One-vs-Rest ROC curve PNG (>=200 DPI)."""
    output_path = Path(output_path)
    output_path.parent.mkdir(parents=True, exist_ok=True)

    fig, ax = plt.subplots(figsize=(6.8, 5.2), dpi=220)
    palette = {
        "No Diabetes": "#10b981",
        "Prediabetes": "#f59e0b",
        "Diabetes": "#ef4444",
    }
    for cls_name, curve in roc_data.items():
        ax.plot(
            curve["fpr"],
            curve["tpr"],
            label=f"{curve['label_id']} (AUC = {curve['auc']:.3f})",
            color=palette.get(cls_name, "#3b82f6"),
            linewidth=2.0,
        )

    ax.plot([0, 1], [0, 1], linestyle="--", color="#64748b", linewidth=1.1, label="Acak (AUC = 0.500)")
    ax.set_title(title, fontsize=11, fontweight="bold")
    ax.set_xlabel("False Positive Rate (1 - Spesifisitas)")
    ax.set_ylabel("True Positive Rate (Sensitivitas / Recall)")
    ax.legend(loc="lower right", frameon=True)
    ax.grid(True, linestyle="--", alpha=0.4)
    fig.tight_layout()
    fig.savefig(output_path, dpi=220)
    plt.close(fig)

