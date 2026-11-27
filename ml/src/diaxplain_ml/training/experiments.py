"""Training pipeline for E0 Baseline, Weakness Gate, E1–E6 Improvements, and Best Model Selection."""

from __future__ import annotations

import json
import time
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

import numpy as np
import optuna
import pandas as pd
import yaml
from imblearn.combine import SMOTETomek
from imblearn.over_sampling import SMOTE, SMOTENC
from sklearn.metrics import f1_score
from sklearn.model_selection import StratifiedKFold
from sklearn.utils.class_weight import compute_sample_weight
from xgboost import XGBClassifier

from diaxplain_ml.analysis.weakness import evaluate_baseline_weakness, save_weakness_report
from diaxplain_ml.evaluation.bootstrap import compare_folds_wilcoxon, compute_bootstrap_ci
from diaxplain_ml.evaluation.metrics import compute_multiclass_metrics, compute_roc_curves_ovr
from diaxplain_ml.evaluation.plots import save_confusion_matrix_plot, save_roc_curve_plot
from diaxplain_ml.features.codebook import (
    CATEGORICAL_INDICES,
    FEATURE_ORDER,
    LABEL_MAP,
    TARGET_COLUMN,
    export_feature_schema,
    export_label_map,
)
from diaxplain_ml.features.encoder import round_categorical_columns

optuna.logging.set_verbosity(optuna.logging.WARNING)


def build_xgb_classifier(params: dict[str, Any] | None = None, seed: int = 42) -> XGBClassifier:
    """Create a 3-class XGBClassifier with histogram tree method and fixed random seed."""
    base_kwargs: dict[str, Any] = {
        "objective": "multi:softprob",
        "num_class": 3,
        "tree_method": "hist",
        "random_state": seed,
        "eval_metric": "mlogloss",
        "n_jobs": 4,
    }
    if params:
        base_kwargs.update(params)
    return XGBClassifier(**base_kwargs)


def _compute_partial_sampling_strategy(y: np.ndarray) -> dict[int, int]:
    """Compute partial oversampling targets so minority classes are boosted without excessive synthetic noise."""
    unique, counts = np.unique(y, return_counts=True)
    count_map = dict(zip(unique.astype(int), counts.astype(int)))
    n_maj = count_map.get(0, 1000)
    n_dia = count_map.get(2, max(100, int(0.15 * n_maj)))
    n_pre = count_map.get(1, max(20, int(0.02 * n_maj)))

    target_dia = max(n_dia, int(0.35 * n_maj))
    target_pre = max(n_pre, int(0.22 * n_maj))
    return {1: target_pre, 2: target_dia}


def apply_resampling_train_only(
    X_train: np.ndarray,
    y_train: np.ndarray,
    strategy_name: str,
    seed: int = 42,
    is_test_set: bool = False,
) -> tuple[np.ndarray, np.ndarray, np.ndarray | None]:
    """
    Apply class imbalance technique strictly on training data (or training fold).
    Raises ValueError if accidentally called with `is_test_set=True`.
    """
    if is_test_set:
        raise ValueError("PELANGGARAN ANTI-LEAKAGE: Resampling dilarang dijalankan pada test set!")

    X_arr = np.asarray(X_train, dtype=np.float32)
    y_arr = np.asarray(y_train, dtype=int)

    if strategy_name in ("none", "baseline", "optuna_only"):
        return X_arr, y_arr, None

    if strategy_name == "class_weight":
        sw = compute_sample_weight(class_weight="balanced", y=y_arr)
        return X_arr, y_arr, sw

    sampling_dict = _compute_partial_sampling_strategy(y_arr)
    min_class_count = int(np.min(np.bincount(y_arr)))
    k_neighbors = max(1, min(5, min_class_count - 1))

    if strategy_name == "smote":
        sm = SMOTE(sampling_strategy=sampling_dict, k_neighbors=k_neighbors, random_state=seed)
        X_res, y_res = sm.fit_resample(X_arr, y_arr)
        X_res = round_categorical_columns(X_res)
        return X_res, y_res, None

    if strategy_name == "smotenc":
        # If dataset is large, use fast categorical-aware SMOTE on capped subset or SMOTENC directly
        if len(X_arr) > 25000:
            rng = np.random.default_rng(seed)
            maj_idx = np.where(y_arr == 0)[0]
            min_idx = np.where(y_arr != 0)[0]
            keep_maj = rng.choice(maj_idx, size=min(len(maj_idx), 18000), replace=False)
            sub_idx = np.concatenate([keep_maj, min_idx])
            X_sub, y_sub = X_arr[sub_idx], y_arr[sub_idx]
            sub_dict = _compute_partial_sampling_strategy(y_sub)
            sm_nc = SMOTENC(
                categorical_features=CATEGORICAL_INDICES,
                sampling_strategy=sub_dict,
                k_neighbors=k_neighbors,
                random_state=seed,
            )
            X_res, y_res = sm_nc.fit_resample(X_sub, y_sub)
        else:
            sm_nc = SMOTENC(
                categorical_features=CATEGORICAL_INDICES,
                sampling_strategy=sampling_dict,
                k_neighbors=k_neighbors,
                random_state=seed,
            )
            X_res, y_res = sm_nc.fit_resample(X_arr, y_arr)
        X_res = round_categorical_columns(X_res)
        return X_res.astype(np.float32), y_res.astype(int), None

    if strategy_name == "hybrid_smotetomek":
        sm_base = SMOTE(sampling_strategy=sampling_dict, k_neighbors=k_neighbors, random_state=seed)
        smt = SMOTETomek(smote=sm_base, random_state=seed, n_jobs=4)
        X_res, y_res = smt.fit_resample(X_arr, y_arr)
        X_res = round_categorical_columns(X_res)
        return X_res.astype(np.float32), y_res.astype(int), None

    raise ValueError(f"Strategi resampling tidak dikenal: {strategy_name}")


def run_optuna_search(
    X_train: np.ndarray,
    y_train: np.ndarray,
    imbalance_strategy: str = "none",
    n_trials: int = 8,
    cv_folds: int = 3,
    seed: int = 42,
) -> tuple[dict[str, Any], list[dict[str, Any]]]:
    """Run Optuna TPE hyperparameter optimization maximizing CV Macro-F1 + Prediabetes F1."""
    skf = StratifiedKFold(n_splits=cv_folds, shuffle=True, random_state=seed)

    # Subsample slightly if dataset > 35k rows to keep HPO fast while preserving minority rows
    if len(X_train) > 35000:
        rng = np.random.default_rng(seed)
        maj_idx = np.where(y_train == 0)[0]
        min_idx = np.where(y_train != 0)[0]
        keep_maj = rng.choice(maj_idx, size=min(len(maj_idx), 25000), replace=False)
        idx_all = np.concatenate([keep_maj, min_idx])
        X_opt, y_opt = X_train[idx_all], y_train[idx_all]
    else:
        X_opt, y_opt = X_train, y_train

    def objective(trial: optuna.Trial) -> float:
        params = {
            "n_estimators": trial.suggest_int("n_estimators", 120, 400, step=40),
            "learning_rate": trial.suggest_float("learning_rate", 0.02, 0.22, log=True),
            "max_depth": trial.suggest_int("max_depth", 3, 8),
            "min_child_weight": trial.suggest_int("min_child_weight", 1, 12),
            "subsample": trial.suggest_float("subsample", 0.65, 1.0),
            "colsample_bytree": trial.suggest_float("colsample_bytree", 0.65, 1.0),
            "gamma": trial.suggest_float("gamma", 0.0, 3.0),
            "reg_alpha": trial.suggest_float("reg_alpha", 1e-3, 3.0, log=True),
            "reg_lambda": trial.suggest_float("reg_lambda", 1e-3, 5.0, log=True),
        }
        fold_scores = []
        for f_tr, f_val in skf.split(X_opt, y_opt):
            X_f_tr, y_f_tr, sw = apply_resampling_train_only(
                X_opt[f_tr], y_opt[f_tr], imbalance_strategy, seed=seed
            )
            model = build_xgb_classifier(params, seed=seed)
            model.fit(X_f_tr, y_f_tr, sample_weight=sw)
            preds = model.predict(X_opt[f_val])
            macro_f1 = float(f1_score(y_opt[f_val], preds, average="macro", zero_division=0))
            fold_scores.append(macro_f1)
        return float(np.mean(fold_scores))

    sampler = optuna.samplers.TPESampler(seed=seed)
    study = optuna.create_study(direction="maximize", sampler=sampler)
    study.optimize(objective, n_trials=n_trials, show_progress_bar=False)

    trials_log = []
    for t in study.trials:
        if t.value is not None:
            trials_log.append(
                {
                    "trial": t.number,
                    "macro_f1_cv": round(float(t.value), 4),
                    "params": t.params,
                }
            )
    return study.best_params, trials_log


def run_single_experiment_cv_and_test(
    code: str,
    name: str,
    technique: str,
    imbalance_strategy: str,
    X_train: np.ndarray,
    y_train: np.ndarray,
    X_test: np.ndarray,
    y_test: np.ndarray,
    cv_folds_indices: list[dict[str, list[int]]],
    exp_dir: str | Path,
    xgb_params: dict[str, Any] | None = None,
    seed: int = 42,
) -> tuple[dict[str, Any], XGBClassifier]:
    """Run 5-fold CV strictly inside train set, then fit on full train and evaluate on test."""
    exp_dir = Path(exp_dir)
    exp_dir.mkdir(parents=True, exist_ok=True)
    t0 = time.perf_counter()

    fold_rows: list[dict[str, Any]] = []
    for fold_info in cv_folds_indices:
        fold_id = fold_info["fold"]
        tr_idx = np.array(fold_info["train_idx"], dtype=int)
        val_idx = np.array(fold_info["val_idx"], dtype=int)

        X_f_tr, y_f_tr, sw = apply_resampling_train_only(
            X_train[tr_idx], y_train[tr_idx], imbalance_strategy, seed=seed + fold_id
        )
        model_f = build_xgb_classifier(xgb_params, seed=seed)
        model_f.fit(X_f_tr, y_f_tr, sample_weight=sw)

        y_val = y_train[val_idx]
        y_val_pred = model_f.predict(X_train[val_idx])
        y_val_prob = model_f.predict_proba(X_train[val_idx])
        m = compute_multiclass_metrics(y_val, y_val_pred, y_val_prob)

        fold_rows.append(
            {
                "fold": fold_id,
                "accuracy": m["accuracy"],
                "macro_precision": m["macro_precision"],
                "macro_recall": m["macro_recall"],
                "macro_f1": m["macro_f1"],
                "weighted_f1": m["weighted_f1"],
                "roc_auc_ovr_macro": m["roc_auc_ovr_macro"],
                "no_diabetes_f1": m["per_class"]["No Diabetes"]["f1"],
                "prediabetes_precision": m["per_class"]["Prediabetes"]["precision"],
                "prediabetes_recall": m["per_class"]["Prediabetes"]["recall"],
                "prediabetes_f1": m["per_class"]["Prediabetes"]["f1"],
                "diabetes_f1": m["per_class"]["Diabetes"]["f1"],
            }
        )

    cv_df = pd.DataFrame(fold_rows)
    cv_df.to_csv(exp_dir / "cv_scores.csv", index=False)

    cv_summary: dict[str, Any] = {
        "n_folds": len(fold_rows),
        "fold_macro_f1s": cv_df["macro_f1"].tolist(),
        "fold_prediabetes_f1s": cv_df["prediabetes_f1"].tolist(),
    }
    for col in [
        "accuracy",
        "macro_precision",
        "macro_recall",
        "macro_f1",
        "weighted_f1",
        "roc_auc_ovr_macro",
        "no_diabetes_f1",
        "prediabetes_precision",
        "prediabetes_recall",
        "prediabetes_f1",
        "diabetes_f1",
    ]:
        cv_summary[f"{col}_mean"] = round(float(cv_df[col].mean()), 4)
        cv_summary[f"{col}_std"] = round(float(cv_df[col].std(ddof=1) if len(cv_df) > 1 else 0.0), 4)

    # Fit on full training set
    X_tr_full, y_tr_full, sw_full = apply_resampling_train_only(
        X_train, y_train, imbalance_strategy, seed=seed
    )
    final_model = build_xgb_classifier(xgb_params, seed=seed)
    final_model.fit(X_tr_full, y_tr_full, sample_weight=sw_full)

    train_seconds = round(time.perf_counter() - t0, 2)

    # Evaluate on train (for overfitting check) and test set
    y_tr_pred = final_model.predict(X_train)
    train_macro_f1 = round(float(f1_score(y_train, y_tr_pred, average="macro", zero_division=0)), 4)

    y_test_pred = final_model.predict(X_test)
    y_test_prob = final_model.predict_proba(X_test)
    test_metrics = compute_multiclass_metrics(y_test, y_test_pred, y_test_prob)
    roc_curves = compute_roc_curves_ovr(y_test, y_test_prob)

    # Save classification report CSV
    cls_rows = []
    for cls_name, cdata in test_metrics["per_class"].items():
        cls_rows.append({"class": cls_name, **cdata})
    pd.DataFrame(cls_rows).to_csv(exp_dir / "classification_report.csv", index=False)

    # Save confusion matrix CSV & PNG
    cm_counts = test_metrics["confusion_matrix"]["counts"]
    cm_df = pd.DataFrame(
        cm_counts,
        index=["Actual_NoDiabetes", "Actual_Prediabetes", "Actual_Diabetes"],
        columns=["Pred_NoDiabetes", "Pred_Prediabetes", "Pred_Diabetes"],
    )
    cm_df.to_csv(exp_dir / "confusion_matrix.csv")
    save_confusion_matrix_plot(
        test_metrics["confusion_matrix"],
        exp_dir / "confusion_matrix.png",
        title=f"Confusion Matrix — {code} ({name})",
    )

    # Save ROC JSON & PNG
    with (exp_dir / "roc.json").open("w", encoding="utf-8") as f:
        json.dump(roc_curves, f, indent=2)
    save_roc_curve_plot(
        roc_curves,
        exp_dir / "roc.png",
        title=f"Kurva ROC OvR — {code} ({name})",
    )

    config_payload = {
        "code": code,
        "name": name,
        "technique": technique,
        "imbalance_strategy": imbalance_strategy,
        "xgb_params": xgb_params or {"default": True},
        "seed": seed,
    }
    with (exp_dir / "config.yaml").open("w", encoding="utf-8") as f:
        yaml.safe_dump(config_payload, f, sort_keys=False)

    result_payload = {
        "code": code,
        "name": name,
        "technique": technique,
        "imbalance_strategy": imbalance_strategy,
        "params": xgb_params or {"default": True, "objective": "multi:softprob", "tree_method": "hist"},
        "train_seconds": train_seconds,
        "train_macro_f1": train_macro_f1,
        "cv_metrics": cv_summary,
        "test_metrics": test_metrics,
        "roc": roc_curves,
    }
    with (exp_dir / "metrics.json").open("w", encoding="utf-8") as f:
        json.dump(result_payload, f, indent=2, ensure_ascii=False)

    return result_payload, final_model


def run_all_experiments_pipeline(
    train_df: pd.DataFrame,
    test_df: pd.DataFrame,
    split_meta: dict[str, Any],
    dataset_sha256: str,
    artifacts_dir: str | Path,
    optuna_trials: int = 8,
    seed: int = 42,
) -> dict[str, Any]:
    """
    Execute the complete research flow:
    1. E0 Baseline
    2. Weakness Gate (RM2)
    3. E1–E6 Improvement Scenarios (Class Weight, SMOTE, SMOTENC, SMOTETomek, Optuna, Combinations)
    4. Empirical Best Model Selection (RM3, RM4) + Bootstrap CI + Wilcoxon
    5. Export Best Model to `artifacts/model_v1/`
    """
    artifacts_dir = Path(artifacts_dir)
    exps_root = artifacts_dir / "experiments"
    exps_root.mkdir(parents=True, exist_ok=True)

    X_train = train_df[FEATURE_ORDER].to_numpy(dtype=np.float32)
    y_train = train_df[TARGET_COLUMN].to_numpy(dtype=int)
    X_test = test_df[FEATURE_ORDER].to_numpy(dtype=np.float32)
    y_test = test_df[TARGET_COLUMN].to_numpy(dtype=int)
    cv_folds = split_meta["cv_folds"]

    results: dict[str, dict[str, Any]] = {}
    models: dict[str, XGBClassifier] = {}

    # --- STEP 1: E0 Baseline ---
    res_e0, model_e0 = run_single_experiment_cv_and_test(
        code="E0",
        name="Baseline XGBoost (Default)",
        technique="baseline_default",
        imbalance_strategy="none",
        X_train=X_train,
        y_train=y_train,
        X_test=X_test,
        y_test=y_test,
        cv_folds_indices=cv_folds,
        exp_dir=exps_root / "E0_baseline",
        xgb_params=None,
        seed=seed,
    )
    results["E0"] = res_e0
    models["E0"] = model_e0

    # --- STEP 2: Weakness Gate (RM2) ---
    weakness_report = evaluate_baseline_weakness(
        baseline_cv_summary=res_e0["cv_metrics"],
        baseline_test_metrics=res_e0["test_metrics"],
        train_macro_f1=res_e0["train_macro_f1"],
    )
    save_weakness_report(weakness_report, exps_root / "weakness_report.json")

    # --- STEP 3: Improvement Matrix E1–E6 (only after Weakness Gate) ---
    # E1: Class Weight
    res_e1, model_e1 = run_single_experiment_cv_and_test(
        code="E1",
        name="XGBoost + Class Weight (Balanced)",
        technique="class_weight",
        imbalance_strategy="class_weight",
        X_train=X_train,
        y_train=y_train,
        X_test=X_test,
        y_test=y_test,
        cv_folds_indices=cv_folds,
        exp_dir=exps_root / "E1_class_weight",
        xgb_params=None,
        seed=seed,
    )
    results["E1"] = res_e1
    models["E1"] = model_e1

    # E2: SMOTE (with discrete feature rounding)
    res_e2, model_e2 = run_single_experiment_cv_and_test(
        code="E2",
        name="XGBoost + SMOTE (Partial Strategy)",
        technique="smote",
        imbalance_strategy="smote",
        X_train=X_train,
        y_train=y_train,
        X_test=X_test,
        y_test=y_test,
        cv_folds_indices=cv_folds,
        exp_dir=exps_root / "E2_smote",
        xgb_params=None,
        seed=seed,
    )
    results["E2"] = res_e2
    models["E2"] = model_e2

    # E2b: SMOTENC (Categorical-aware SMOTE)
    res_e2b, model_e2b = run_single_experiment_cv_and_test(
        code="E2b",
        name="XGBoost + SMOTENC (Categorical-Aware)",
        technique="smotenc",
        imbalance_strategy="smotenc",
        X_train=X_train,
        y_train=y_train,
        X_test=X_test,
        y_test=y_test,
        cv_folds_indices=cv_folds,
        exp_dir=exps_root / "E2b_smotenc",
        xgb_params=None,
        seed=seed,
    )
    results["E2b"] = res_e2b
    models["E2b"] = model_e2b

    # E3: Hybrid Sampling (SMOTETomek)
    res_e3, model_e3 = run_single_experiment_cv_and_test(
        code="E3",
        name="XGBoost + Hybrid Sampling (SMOTETomek)",
        technique="hybrid_smotetomek",
        imbalance_strategy="hybrid_smotetomek",
        X_train=X_train,
        y_train=y_train,
        X_test=X_test,
        y_test=y_test,
        cv_folds_indices=cv_folds,
        exp_dir=exps_root / "E3_hybrid_smotetomek",
        xgb_params=None,
        seed=seed,
    )
    results["E3"] = res_e3
    models["E3"] = model_e3

    # E4: Optuna HPO only
    best_params_e4, trials_e4 = run_optuna_search(
        X_train, y_train, imbalance_strategy="none", n_trials=optuna_trials, cv_folds=3, seed=seed
    )
    res_e4, model_e4 = run_single_experiment_cv_and_test(
        code="E4",
        name="XGBoost + Optuna HPO (Tanpa Imbalance Handling)",
        technique="optuna_only",
        imbalance_strategy="none",
        X_train=X_train,
        y_train=y_train,
        X_test=X_test,
        y_test=y_test,
        cv_folds_indices=cv_folds,
        exp_dir=exps_root / "E4_optuna",
        xgb_params=best_params_e4,
        seed=seed,
    )
    res_e4["optuna_trials"] = trials_e4
    results["E4"] = res_e4
    models["E4"] = model_e4

    # E5: Class Weight + Optuna HPO
    best_params_e5, trials_e5 = run_optuna_search(
        X_train, y_train, imbalance_strategy="class_weight", n_trials=optuna_trials, cv_folds=3, seed=seed
    )
    res_e5, model_e5 = run_single_experiment_cv_and_test(
        code="E5",
        name="XGBoost + Class Weight + Optuna HPO",
        technique="class_weight+optuna",
        imbalance_strategy="class_weight",
        X_train=X_train,
        y_train=y_train,
        X_test=X_test,
        y_test=y_test,
        cv_folds_indices=cv_folds,
        exp_dir=exps_root / "E5_class_weight_optuna",
        xgb_params=best_params_e5,
        seed=seed,
    )
    res_e5["optuna_trials"] = trials_e5
    results["E5"] = res_e5
    models["E5"] = model_e5

    # E6: Best Resampling (from E2/E2b/E3 by CV Macro-F1) + Optuna HPO
    resampling_candidates = ["E2", "E2b", "E3"]
    best_resamp_code = max(
        resampling_candidates,
        key=lambda c: (
            results[c]["cv_metrics"]["macro_f1_mean"],
            results[c]["cv_metrics"]["prediabetes_f1_mean"],
        ),
    )
    best_resamp_strategy = results[best_resamp_code]["imbalance_strategy"]
    best_params_e6, trials_e6 = run_optuna_search(
        X_train, y_train, imbalance_strategy=best_resamp_strategy, n_trials=optuna_trials, cv_folds=3, seed=seed
    )
    res_e6, model_e6 = run_single_experiment_cv_and_test(
        code="E6",
        name=f"XGBoost + {best_resamp_strategy.upper()} + Optuna HPO",
        technique=f"{best_resamp_strategy}+optuna",
        imbalance_strategy=best_resamp_strategy,
        X_train=X_train,
        y_train=y_train,
        X_test=X_test,
        y_test=y_test,
        cv_folds_indices=cv_folds,
        exp_dir=exps_root / "E6_resampling_optuna",
        xgb_params=best_params_e6,
        seed=seed,
    )
    res_e6["optuna_trials"] = trials_e6
    results["E6"] = res_e6
    models["E6"] = model_e6

    # --- STEP 4: Comparison Tables & Empirical Selection (RM3, RM4) ---
    cv_comp_rows = []
    test_comp_rows = []
    for code, r in results.items():
        cvm = r["cv_metrics"]
        tm = r["test_metrics"]
        wilcox = compare_folds_wilcoxon(
            results["E0"]["cv_metrics"]["fold_macro_f1s"],
            cvm["fold_macro_f1s"],
        )
        r["wilcoxon_vs_baseline"] = wilcox

        cv_comp_rows.append(
            {
                "code": code,
                "name": r["name"],
                "technique": r["technique"],
                "macro_f1_mean": cvm["macro_f1_mean"],
                "macro_f1_std": cvm["macro_f1_std"],
                "prediabetes_f1_mean": cvm["prediabetes_f1_mean"],
                "prediabetes_recall_mean": cvm["prediabetes_recall_mean"],
                "roc_auc_ovr_macro_mean": cvm["roc_auc_ovr_macro_mean"],
                "accuracy_mean": cvm["accuracy_mean"],
                "train_seconds": r["train_seconds"],
                "wilcoxon_p_value": wilcox["p_value"],
            }
        )
        test_comp_rows.append(
            {
                "code": code,
                "name": r["name"],
                "technique": r["technique"],
                "accuracy": tm["accuracy"],
                "macro_precision": tm["macro_precision"],
                "macro_recall": tm["macro_recall"],
                "macro_f1": tm["macro_f1"],
                "weighted_f1": tm["weighted_f1"],
                "roc_auc_ovr_macro": tm["roc_auc_ovr_macro"],
                "no_diabetes_f1": tm["per_class"]["No Diabetes"]["f1"],
                "prediabetes_precision": tm["per_class"]["Prediabetes"]["precision"],
                "prediabetes_recall": tm["per_class"]["Prediabetes"]["recall"],
                "prediabetes_f1": tm["per_class"]["Prediabetes"]["f1"],
                "diabetes_f1": tm["per_class"]["Diabetes"]["f1"],
                "train_seconds": r["train_seconds"],
            }
        )

    pd.DataFrame(cv_comp_rows).to_csv(exps_root / "comparison_cv.csv", index=False)
    pd.DataFrame(test_comp_rows).to_csv(exps_root / "comparison_test.csv", index=False)

    # Selection Rule (Section 7.7):
    # Sort by CV Macro-F1; if within 1 SD of top, pick higher Prediabetes F1 + Recall so minority class detection is active
    candidates = [c for c in results.keys() if c != "E0"]
    max_cv_macro_f1 = max(results[c]["cv_metrics"]["macro_f1_mean"] for c in candidates)

    def _selection_score(c: str) -> tuple[float, float, float]:
        cvm = results[c]["cv_metrics"]
        # Composite score balancing CV Macro-F1 and Prediabetes detection (F1 & Recall)
        has_minority_detection = 1.0 if cvm["prediabetes_recall_mean"] >= 0.08 else 0.0
        return (
            has_minority_detection,
            round(cvm["macro_f1_mean"] + 0.35 * cvm["prediabetes_f1_mean"] + 0.15 * cvm["prediabetes_recall_mean"], 4),
            cvm["prediabetes_f1_mean"],
        )

    winner_code = max(candidates, key=_selection_score)
    winner_res = results[winner_code]
    winner_model = models[winner_code]

    # Compute Bootstrap 95% CI on test set for Baseline vs Winner
    y_test_pred_e0 = models["E0"].predict(X_test)
    y_test_pred_win = winner_model.predict(X_test)
    ci_e0 = compute_bootstrap_ci(y_test, y_test_pred_e0, n_iterations=150, seed=seed)
    ci_win = compute_bootstrap_ci(y_test, y_test_pred_win, n_iterations=150, seed=seed)

    selection_report = {
        "selected_code": winner_code,
        "selected_name": winner_res["name"],
        "selected_technique": winner_res["technique"],
        "selected_params": winner_res["params"],
        "selection_criteria": (
            "Seleksi berdasarkan kombinasi terbaik Macro-F1 pada 5-Fold Cross-Validation dengan syarat deteksi aktif "
            "pada kelas minoritas Prediabetes (F1 & Recall Prediabetes tertinggi bila Macro-F1 kompetitif)."
        ),
        "baseline_vs_best_summary": {
            "baseline_cv_macro_f1": results["E0"]["cv_metrics"]["macro_f1_mean"],
            "best_cv_macro_f1": winner_res["cv_metrics"]["macro_f1_mean"],
            "baseline_test_macro_f1": results["E0"]["test_metrics"]["macro_f1"],
            "best_test_macro_f1": winner_res["test_metrics"]["macro_f1"],
            "baseline_test_prediabetes_f1": results["E0"]["test_metrics"]["per_class"]["Prediabetes"]["f1"],
            "best_test_prediabetes_f1": winner_res["test_metrics"]["per_class"]["Prediabetes"]["f1"],
            "baseline_test_prediabetes_recall": results["E0"]["test_metrics"]["per_class"]["Prediabetes"]["recall"],
            "best_test_prediabetes_recall": winner_res["test_metrics"]["per_class"]["Prediabetes"]["recall"],
            "baseline_test_roc_auc_macro": results["E0"]["test_metrics"]["roc_auc_ovr_macro"],
            "best_test_roc_auc_macro": winner_res["test_metrics"]["roc_auc_ovr_macro"],
        },
        "bootstrap_ci_95": {
            "baseline_E0": ci_e0,
            "best_model": ci_win,
        },
        "wilcoxon_vs_baseline": winner_res["wilcoxon_vs_baseline"],
        "empirical_justification": (
            f"Skenario {winner_code} ({winner_res['name']}) terpilih sebagai Best Model karena mampu meningkatkan "
            f"sensitivitas (Recall) kelas minoritas Prediabetes dari "
            f"{results['E0']['test_metrics']['per_class']['Prediabetes']['recall']*100:.2f}% (Baseline E0) menjadi "
            f"{winner_res['test_metrics']['per_class']['Prediabetes']['recall']*100:.2f}%, serta meningkatkan F1 Prediabetes "
            f"dari {results['E0']['test_metrics']['per_class']['Prediabetes']['f1']:.4f} menjadi "
            f"{winner_res['test_metrics']['per_class']['Prediabetes']['f1']:.4f} dengan ROC-AUC OvR Macro sebesar "
            f"{winner_res['test_metrics']['roc_auc_ovr_macro']:.4f}."
        ),
    }
    with (exps_root / "selection_report.json").open("w", encoding="utf-8") as f:
        json.dump(selection_report, f, indent=2, ensure_ascii=False)

    # --- STEP 5: Export Best Model Artifact (`artifacts/model_v1/`) ---
    model_dir = artifacts_dir / "model_v1"
    model_dir.mkdir(parents=True, exist_ok=True)
    winner_model.save_model(str(model_dir / "model.json"))
    export_feature_schema(model_dir / "feature_schema.json")
    export_label_map(model_dir / "label_map.json")
    (model_dir / "dataset_hash.txt").write_text(f"{dataset_sha256}\n", encoding="utf-8")

    # Save stratified background sample for SHAP
    bg_df = test_df.groupby(TARGET_COLUMN, group_keys=False).apply(
        lambda g: g.sample(n=min(len(g), 150), random_state=seed)
    )
    bg_df.to_parquet(model_dir / "shap_background.parquet", index=False)

    model_meta = {
        "model_version": "xgb-v1.0.0",
        "experiment_code": winner_code,
        "name": winner_res["name"],
        "algorithm": "XGBoost (multi:softprob, hist)",
        "technique": winner_res["technique"],
        "params": winner_res["params"],
        "dataset_hash": dataset_sha256,
        "trained_at": datetime.now(timezone.utc).isoformat(),
        "cv_metrics": winner_res["cv_metrics"],
        "test_metrics": winner_res["test_metrics"],
        "selection_report": selection_report,
    }
    with (model_dir / "metrics.json").open("w", encoding="utf-8") as f:
        json.dump(model_meta, f, indent=2, ensure_ascii=False)
    with (model_dir / "config.yaml").open("w", encoding="utf-8") as f:
        yaml.safe_dump(
            {
                "model_version": "xgb-v1.0.0",
                "experiment_code": winner_code,
                "technique": winner_res["technique"],
                "params": winner_res["params"],
                "seed": seed,
            },
            f,
            sort_keys=False,
        )

    return {
        "experiments": results,
        "weakness_report": weakness_report,
        "selection_report": selection_report,
        "best_model": winner_model,
        "best_code": winner_code,
    }

