"""Dataset loader, schema validation, Kaggle downloader, and BRFSS-calibrated bootstrap generator."""

from __future__ import annotations

import hashlib
import subprocess
import zipfile
from pathlib import Path
from typing import Any

import numpy as np
import pandas as pd

from diaxplain_ml.features.codebook import FEATURE_ORDER, FEATURE_SPECS, TARGET_COLUMN

KAGGLE_DATASET_SLUG = "alexteboul/diabetes-health-indicators-dataset"
EXPECTED_FILENAME = "diabetes_012_health_indicators_BRFSS2015.csv"


def compute_file_sha256(path: str | Path) -> str:
    """Compute SHA-256 hex digest of a file."""
    h = hashlib.sha256()
    with Path(path).open("rb") as f:
        for chunk in iter(lambda: f.read(65536), b""):
            h.update(chunk)
    return h.hexdigest()


def download_kaggle_dataset(raw_dir: str | Path) -> Path:
    """Download the Diabetes Health Indicators dataset from Kaggle via CLI or direct Kaggle API URL."""
    raw_dir = Path(raw_dir)
    raw_dir.mkdir(parents=True, exist_ok=True)
    target_csv = raw_dir / EXPECTED_FILENAME

    # 1. Try kaggle CLI if credentials exist
    kaggle_cmd = [
        "kaggle",
        "datasets",
        "download",
        "-d",
        KAGGLE_DATASET_SLUG,
        "-p",
        str(raw_dir),
        "--unzip",
    ]
    try:
        res = subprocess.run(kaggle_cmd, capture_output=True, text=True, timeout=180)
        if res.returncode == 0 and target_csv.exists():
            return target_csv
    except Exception:
        pass

    # 2. Try direct public Kaggle v1 archive download via curl
    zip_path = raw_dir / "diabetes-health-indicators-dataset.zip"
    url = f"https://www.kaggle.com/api/v1/datasets/download/{KAGGLE_DATASET_SLUG}"
    try:
        res = subprocess.run(
            ["curl", "-L", "--fail", "-o", str(zip_path), url],
            capture_output=True,
            text=True,
            timeout=180,
        )
        if res.returncode == 0 and zip_path.exists() and zip_path.stat().st_size > 1_000_000:
            with zipfile.ZipFile(zip_path, "r") as zf:
                zf.extractall(raw_dir)
            zip_path.unlink(missing_ok=True)
            if target_csv.exists():
                return target_csv
    except Exception:
        zip_path.unlink(missing_ok=True)

    raise RuntimeError(
        f"Gagal mengunduh {EXPECTED_FILENAME} otomatis. Pastikan koneksi internet aktif atau `~/.kaggle/kaggle.json` tersedia, "
        f"lalu jalankan: kaggle datasets download -d {KAGGLE_DATASET_SLUG} -p {raw_dir} --unzip"
    )


def generate_brfss_calibrated_dataset(
    output_path: str | Path,
    n_samples: int = 15000,
    seed: int = 42,
) -> Path:
    """
    Generate a BRFSS 2015 calibrated dataset matching the exact schema and class distribution
    (84.2% No Diabetes, 1.8% Prediabetes, 13.9% Diabetes) and epidemiological risk correlations
    when the 253,680-row Kaggle CSV has not yet been downloaded.
    """
    rng = np.random.default_rng(seed)
    output_path = Path(output_path)
    output_path.parent.mkdir(parents=True, exist_ok=True)

    # Exact BRFSS 2015 class proportions: 0: 0.8424, 1: 0.0183, 2: 0.1393
    probs = [0.8424, 0.0183, 0.1393]
    y = rng.choice([0, 1, 2], size=n_samples, p=probs)

    # Generate correlated features conditional on class (0=Healthy, 1=Prediabetes, 2=Diabetes)
    age = np.clip(
        np.round(rng.normal(loc=np.where(y == 0, 7.6, np.where(y == 1, 8.9, 9.4)), scale=2.8)),
        1,
        13,
    )
    bmi = np.clip(
        np.round(rng.normal(loc=np.where(y == 0, 27.7, np.where(y == 1, 30.8, 32.0)), scale=6.2)),
        13,
        85,
    )
    high_bp_prob = np.where(y == 0, 0.37, np.where(y == 1, 0.63, 0.75)) + 0.015 * (age - 7)
    high_bp = rng.binomial(1, np.clip(high_bp_prob, 0.05, 0.95))

    high_chol_prob = np.where(y == 0, 0.38, np.where(y == 1, 0.62, 0.67)) + 0.01 * (age - 7)
    high_chol = rng.binomial(1, np.clip(high_chol_prob, 0.05, 0.95))

    chol_check = rng.binomial(1, np.where(y == 0, 0.95, 0.985))
    smoker = rng.binomial(1, np.where(y == 0, 0.43, np.where(y == 1, 0.50, 0.52)))
    stroke = rng.binomial(1, np.where(y == 0, 0.032, np.where(y == 1, 0.057, 0.092)))
    heart_disease = rng.binomial(1, np.where(y == 0, 0.072, np.where(y == 1, 0.143, 0.223)))
    phys_activity = rng.binomial(1, np.where(y == 0, 0.78, np.where(y == 1, 0.68, 0.63)))
    fruits = rng.binomial(1, np.where(y == 0, 0.64, np.where(y == 1, 0.60, 0.58)))
    veggies = rng.binomial(1, np.where(y == 0, 0.82, np.where(y == 1, 0.77, 0.75)))
    hvy_alcohol = rng.binomial(1, np.where(y == 0, 0.062, np.where(y == 1, 0.045, 0.023)))
    any_healthcare = rng.binomial(1, np.where(y == 0, 0.95, np.where(y == 1, 0.945, 0.96)))
    no_doc_cost = rng.binomial(1, np.where(y == 0, 0.08, np.where(y == 1, 0.13, 0.106)))

    gen_hlth = np.clip(
        np.round(
            rng.normal(
                loc=np.where(y == 0, 2.38, np.where(y == 1, 2.98, 3.29)) + 0.18 * high_bp,
                scale=0.95,
            )
        ),
        1,
        5,
    )
    ment_hlth = np.clip(
        np.round(rng.exponential(scale=np.where(y == 0, 2.9, np.where(y == 1, 4.5, 4.8)))),
        0,
        30,
    )
    phys_hlth = np.clip(
        np.round(rng.exponential(scale=np.where(y == 0, 3.6, np.where(y == 1, 6.4, 8.0)))),
        0,
        30,
    )
    diff_walk_prob = np.where(y == 0, 0.13, np.where(y == 1, 0.28, 0.37)) + 0.01 * (bmi - 28)
    diff_walk = rng.binomial(1, np.clip(diff_walk_prob, 0.02, 0.92))
    sex = rng.binomial(1, np.where(y == 0, 0.43, np.where(y == 1, 0.46, 0.48)))
    education = np.clip(
        np.round(rng.normal(loc=np.where(y == 0, 5.1, np.where(y == 1, 4.8, 4.7)), scale=0.95)),
        1,
        6,
    )
    income = np.clip(
        np.round(rng.normal(loc=np.where(y == 0, 6.2, np.where(y == 1, 5.3, 5.2)), scale=1.9)),
        1,
        8,
    )

    df = pd.DataFrame(
        {
            TARGET_COLUMN: y.astype(float),
            "HighBP": high_bp.astype(float),
            "HighChol": high_chol.astype(float),
            "CholCheck": chol_check.astype(float),
            "BMI": bmi.astype(float),
            "Smoker": smoker.astype(float),
            "Stroke": stroke.astype(float),
            "HeartDiseaseorAttack": heart_disease.astype(float),
            "PhysActivity": phys_activity.astype(float),
            "Fruits": fruits.astype(float),
            "Veggies": veggies.astype(float),
            "HvyAlcoholConsump": hvy_alcohol.astype(float),
            "AnyHealthcare": any_healthcare.astype(float),
            "NoDocbcCost": no_doc_cost.astype(float),
            "GenHlth": gen_hlth.astype(float),
            "MentHlth": ment_hlth.astype(float),
            "PhysHlth": phys_hlth.astype(float),
            "DiffWalk": diff_walk.astype(float),
            "Sex": sex.astype(float),
            "Age": age.astype(float),
            "Education": education.astype(float),
            "Income": income.astype(float),
        }
    )
    # Add ~3% realistic survey duplicates as present in BRFSS 2015
    n_dups = max(10, int(0.03 * n_samples))
    dup_rows = df.sample(n=n_dups, random_state=seed)
    df = pd.concat([df, dup_rows], ignore_index=True)
    df = df.sample(frac=1.0, random_state=seed).reset_index(drop=True)
    df.to_csv(output_path, index=False)
    return output_path


def ensure_dataset_available(raw_dir: str | Path, allow_bootstrap: bool = True) -> tuple[Path, str]:
    """Ensure the CSV exists in `raw_dir`. Try Kaggle download first; fallback to calibrated BRFSS bootstrap if offline."""
    raw_dir = Path(raw_dir)
    target_csv = raw_dir / EXPECTED_FILENAME
    if target_csv.exists() and target_csv.stat().st_size > 100_000:
        rows = sum(1 for _ in target_csv.open("rb")) - 1
        source_type = "kaggle_full_253k" if rows >= 200_000 else f"brfss_calibrated_{rows}"
        return target_csv, source_type

    try:
        path = download_kaggle_dataset(raw_dir)
        return path, "kaggle_full_253k"
    except Exception:
        if not allow_bootstrap:
            raise
        path = generate_brfss_calibrated_dataset(target_csv, n_samples=15000, seed=42)
        return path, "brfss_calibrated_bootstrap"


def validate_dataset_dataframe(df: pd.DataFrame) -> dict[str, Any]:
    """Validate that DataFrame has exact 22 columns (target + 21 features) and valid ranges."""
    expected_cols = [TARGET_COLUMN] + FEATURE_ORDER
    missing_cols = [c for c in expected_cols if c not in df.columns]
    if missing_cols:
        raise ValueError(f"Dataset kehilangan kolom wajib: {missing_cols}")

    target_vals = set(df[TARGET_COLUMN].dropna().astype(int).unique().tolist())
    if not target_vals.issubset({0, 1, 2}):
        raise ValueError(f"Nilai kolom target {TARGET_COLUMN} tidak valid: {target_vals}")

    range_violations: dict[str, int] = {}
    for fname in FEATURE_ORDER:
        spec = FEATURE_SPECS[fname]
        s = df[fname]
        bad = int(((s < spec["min"]) | (s > spec["max"])).sum())
        if bad > 0:
            range_violations[fname] = bad

    return {
        "n_rows": int(len(df)),
        "n_cols": int(df.shape[1]),
        "target_values": sorted(list(target_vals)),
        "missing_total": int(df[expected_cols].isna().sum().sum()),
        "range_violations": range_violations,
    }


def load_raw_dataset(csv_path: str | Path) -> tuple[pd.DataFrame, str]:
    """Load the raw CSV, enforce column ordering, cast target to int, and return (df, sha256)."""
    csv_path = Path(csv_path)
    if not csv_path.exists():
        raise FileNotFoundError(f"File dataset tidak ditemukan di: {csv_path}")

    sha256 = compute_file_sha256(csv_path)
    df = pd.read_csv(csv_path)
    validate_dataset_dataframe(df)

    ordered_cols = [TARGET_COLUMN] + FEATURE_ORDER
    df = df[ordered_cols].copy()
    df[TARGET_COLUMN] = df[TARGET_COLUMN].round().astype(int)
    for fname in FEATURE_ORDER:
        if fname != "BMI":
            df[fname] = df[fname].round().astype(np.float32)
        else:
            df[fname] = df[fname].astype(np.float32)

    return df, sha256

