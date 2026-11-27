"""Stratified train/test split and strict anti-leakage verification."""

from __future__ import annotations

import json
from pathlib import Path
from typing import Any

import numpy as np
import pandas as pd
from sklearn.model_selection import StratifiedKFold, train_test_split

from diaxplain_ml.features.codebook import FEATURE_ORDER, TARGET_COLUMN


def verify_no_split_leakage(train_indices: list[int] | np.ndarray, test_indices: list[int] | np.ndarray) -> bool:
    """Assert that train and test row indices are strictly disjoint."""
    s_train = set(np.asarray(train_indices, dtype=int).tolist())
    s_test = set(np.asarray(test_indices, dtype=int).tolist())
    overlap = s_train.intersection(s_test)
    if overlap:
        raise AssertionError(f"Terdeteksi kebocoran indeks train/test sebanyak {len(overlap)} baris!")
    return True


def create_stratified_split(
    df: pd.DataFrame,
    processed_dir: str | Path,
    test_size: float = 0.2,
    n_splits: int = 5,
    seed: int = 42,
) -> dict[str, Any]:
    """Perform a single stratified train-test split + 5-fold CV indices inside train only."""
    processed_dir = Path(processed_dir)
    processed_dir.mkdir(parents=True, exist_ok=True)

    indices = np.arange(len(df))
    y = df[TARGET_COLUMN].to_numpy(dtype=int)

    train_idx, test_idx = train_test_split(
        indices,
        test_size=test_size,
        stratify=y,
        random_state=seed,
    )

    verify_no_split_leakage(train_idx, test_idx)

    train_df = df.iloc[train_idx].reset_index(drop=True)
    test_df = df.iloc[test_idx].reset_index(drop=True)

    # Save parquet files
    train_path = processed_dir / "train.parquet"
    test_path = processed_dir / "test.parquet"
    train_df.to_parquet(train_path, index=False)
    test_df.to_parquet(test_path, index=False)

    # Create 5-fold CV indices strictly within train_df (0 .. len(train_df)-1)
    skf = StratifiedKFold(n_splits=n_splits, shuffle=True, random_state=seed)
    y_train = train_df[TARGET_COLUMN].to_numpy(dtype=int)
    cv_folds: list[dict[str, list[int]]] = []
    for fold_id, (f_tr, f_val) in enumerate(skf.split(train_df[FEATURE_ORDER], y_train)):
        verify_no_split_leakage(f_tr, f_val)
        cv_folds.append(
            {
                "fold": fold_id,
                "train_idx": f_tr.astype(int).tolist(),
                "val_idx": f_val.astype(int).tolist(),
            }
        )

    split_meta = {
        "seed": seed,
        "test_size": test_size,
        "n_splits": n_splits,
        "total_rows": int(len(df)),
        "train_rows": int(len(train_df)),
        "test_rows": int(len(test_df)),
        "train_class_counts": {str(k): int(v) for k, v in train_df[TARGET_COLUMN].value_counts().sort_index().items()},
        "test_class_counts": {str(k): int(v) for k, v in test_df[TARGET_COLUMN].value_counts().sort_index().items()},
        "train_indices": train_idx.astype(int).tolist(),
        "test_indices": test_idx.astype(int).tolist(),
        "cv_folds": cv_folds,
    }

    split_json_path = processed_dir / "split_indices.json"
    with split_json_path.open("w", encoding="utf-8") as f:
        json.dump(split_meta, f, indent=2)

    return split_meta

