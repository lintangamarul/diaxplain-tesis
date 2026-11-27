# Panduan Eksperimen Machine Learning & Unduh Dataset Kaggle (DiaXplain)

Dokumen ini menjelaskan cara mengunduh dataset asli **Diabetes Health Indicators Dataset (BRFSS 2015)** dari Kaggle serta menjalankan seluruh pipeline eksperimen untuk Bab III dan Bab IV tesis.

---

## 1. Mengunduh Dataset dari Kaggle via Command Line

Dataset utama yang digunakan sesuai Batasan Masalah Tesis adalah:
- **Slug Kaggle:** `alexteboul/diabetes-health-indicators-dataset`
- **File Utama:** `diabetes_012_health_indicators_BRFSS2015.csv` (253.680 baris × 22 kolom)

### Opsi A — Menggunakan CLI `diaxplain` (Otomatis)
Dari direktori `ml/`, jalankan:
```bash
./.venv/bin/diaxplain download-dataset
```
Perintah ini akan mencoba mengunduh otomatis menggunakan Kaggle CLI atau endpoint unduhan publik Kaggle dan mengekstraknya ke `ml/data/raw/diabetes_012_health_indicators_BRFSS2015.csv`.

### Opsi B — Menggunakan Kaggle CLI Secara Langsung
Jika Anda menggunakan token `kaggle.json` (simpan di `~/.kaggle/kaggle.json` dengan `chmod 600 ~/.kaggle/kaggle.json`):
```bash
ml/.venv/bin/kaggle datasets download -d alexteboul/diabetes-health-indicators-dataset -p ml/data/raw --unzip
```

---

## 2. Menjalankan Pipeline Eksperimen Lengkap (Seed = 42)

Untuk menjalankan seluruh tahapan riset secara berurutan (EDA → Stratified Split → Baseline E0 → Weakness Gate RM2 → Skenario Peningkatan E1–E6 → Seleksi Best Model RM3/RM4 → SHAP Global RM5 → Evaluasi LLM RM6):

```bash
ml/.venv/bin/diaxplain run-pipeline --trials 20
```

Seluruh artefak akan tersimpan otomatis di `ml/artifacts/`:
- `ml/artifacts/eda/`: Distribusi kelas, pemeriksaan duplikat, korelasi fitur, outlier BMI, dan `dataset_hash.txt` (SHA-256).
- `ml/artifacts/experiments/`: Metrik lengkap `E0_baseline` s.d. `E6_resampling_optuna`, `weakness_report.json`, `comparison_cv.csv`, `comparison_test.csv`, dan `selection_report.json`.
- `ml/artifacts/model_v1/`: Model XGBoost terbaik (`model.json`), `feature_schema.json`, `label_map.json`, dan `metrics.json`.
- `ml/artifacts/shap/`: `shap_global.json`, `shap_bar_overall.png`, `shap_summary_class0/1/2.png`, dan `shap_dependence_top5.png`.
- `ml/artifacts/llm_eval/`: `llm_eval_cases.json`, `results.csv`, `human_ratings.csv`, dan `summary.json`.

---

## 3. Mengimpor Hasil Eksperimen ke Aplikasi Web Laravel

Setelah pipeline selesai dijalankan, sinkronkan hasil eksperimen ke database aplikasi web Laravel:
```bash
cd web
php artisan experiments:import
```

