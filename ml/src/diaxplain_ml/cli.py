"""Unified CLI for DiaXplain ML Research Pipeline & Service."""

from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path

import pandas as pd
import uvicorn
from xgboost import XGBClassifier

from diaxplain_ml.data.eda import run_eda_pipeline
from diaxplain_ml.data.loader import (
    download_kaggle_dataset,
    ensure_dataset_available,
    load_raw_dataset,
)
from diaxplain_ml.data.split import create_stratified_split
from diaxplain_ml.explain.shap_explainer import run_global_shap_analysis
from diaxplain_ml.llm.evaluate import run_llm_evaluation_harness
from diaxplain_ml.training.experiments import run_all_experiments_pipeline

ML_ROOT = Path(__file__).resolve().parents[2]


def cmd_download_dataset(args: argparse.Namespace) -> None:
    raw_dir = ML_ROOT / "data" / "raw"
    try:
        csv_path = download_kaggle_dataset(raw_dir)
        print(f"[OK] Dataset Kaggle berhasil diunduh ke: {csv_path}")
    except Exception as exc:
        if args.fallback_bootstrap:
            csv_path, source_type = ensure_dataset_available(raw_dir, allow_bootstrap=True)
            print(f"[INFO] Menggunakan dataset terkalibrasi BRFSS ({source_type}): {csv_path}")
        else:
            print(f"[ERROR] {exc}", file=sys.stderr)
            sys.exit(1)


def cmd_run_pipeline(args: argparse.Namespace) -> None:
    raw_dir = ML_ROOT / "data" / "raw"
    processed_dir = ML_ROOT / "data" / "processed"
    artifacts_dir = ML_ROOT / "artifacts"

    print("1/5 Memeriksa ketersediaan dataset BRFSS 2015...")
    csv_path, source_type = ensure_dataset_available(raw_dir, allow_bootstrap=True)
    df, sha256 = load_raw_dataset(csv_path)
    print(f"    -> Dataset dimuat ({len(df):,} baris x {df.shape[1]} kolom, source={source_type}, sha256={sha256[:12]}...)")

    print("2/5 Menjalankan Data Understanding (EDA) & Stratified Split (Seed 42)...")
    run_eda_pipeline(df, sha256, artifacts_dir / "eda", source_type=source_type)
    split_meta = create_stratified_split(df, processed_dir, test_size=0.2, n_splits=5, seed=42)
    train_df = pd.read_parquet(processed_dir / "train.parquet")
    test_df = pd.read_parquet(processed_dir / "test.parquet")

    print("3/5 Menjalankan Baseline E0, Weakness Gate (RM2), Skenario E1–E6, dan Seleksi Best Model...")
    exp_out = run_all_experiments_pipeline(
        train_df=train_df,
        test_df=test_df,
        split_meta=split_meta,
        dataset_sha256=sha256,
        artifacts_dir=artifacts_dir,
        optuna_trials=args.trials,
        seed=42,
    )
    best_model: XGBClassifier = exp_out["best_model"]
    best_code: str = exp_out["best_code"]
    best_tech: str = exp_out["selection_report"]["selected_technique"]
    print(f"    -> Best Model terpilih: {best_code} ({best_tech})")

    print("4/5 Menghitung SHAP Global & Plot Beeswarm/Dependence pada Best Model (RM5)...")
    run_global_shap_analysis(
        model=best_model,
        test_df=test_df,
        shap_dir=artifacts_dir / "shap",
        max_samples=800,
        seed=42,
    )

    print("5/5 Menjalankan Harness Evaluasi Interpretasi LLM & Guardrail (RM6)...")
    run_llm_evaluation_harness(
        model=best_model,
        test_df=test_df,
        llm_eval_dir=artifacts_dir / "llm_eval",
        technique_name=best_tech,
        n_cases_per_class=20,
        seed=42,
    )
    print("[SELESAI] Seluruh artefak eksperimen, model_v1, SHAP, dan evaluasi LLM tersimpan di ml/artifacts/!")


def cmd_serve(args: argparse.Namespace) -> None:
    uvicorn.run(
        "diaxplain_ml.serve.app:app",
        host=args.host,
        port=args.port,
        reload=args.reload,
    )


def main() -> None:
    parser = argparse.ArgumentParser(prog="diaxplain", description="DiaXplain ML Pipeline CLI")
    sub = parser.add_subparsers(dest="command", required=True)

    p_dl = sub.add_parser("download-dataset", help="Unduh dataset Kaggle BRFSS 2015")
    p_dl.add_argument("--fallback-bootstrap", action="store_true", help="Gunakan bootstrap BRFSS bila offline")
    p_dl.set_defaults(func=cmd_download_dataset)

    p_run = sub.add_parser("run-pipeline", help="Jalankan seluruh pipeline riset (EDA -> E0-E6 -> SHAP -> LLM Eval)")
    p_run.add_argument("--trials", type=int, default=6, help="Jumlah trial Optuna per skenario HPO")
    p_run.set_defaults(func=cmd_run_pipeline)

    p_srv = sub.add_parser("serve", help="Jalankan FastAPI ML Service")
    p_srv.add_argument("--host", default="127.0.0.1")
    p_srv.add_argument("--port", type=int, default=8001)
    p_srv.add_argument("--reload", action="store_true")
    p_srv.set_defaults(func=cmd_serve)

    args = parser.parse_args()
    args.func(args)


if __name__ == "__main__":
    main()

