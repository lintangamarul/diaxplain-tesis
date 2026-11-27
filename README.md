# DiaXplain — SPK Klasifikasi Status Diabetes (XGBoost + SHAP + LLM)

> **Pengembangan Sistem Pendukung Keputusan Klasifikasi Status Diabetes Menggunakan XGBoost dengan Explainable AI (SHAP) dan Large Language Model untuk Interpretasi Hasil Prediksi**
>
> Penulis: **Lintang Amarul Fatah** (NIM 25.01.85.0020) — Universitas Stikubank (Unisbank) Semarang

![Laravel](https://img.shields.io/badge/Laravel-12-red) ![Python](https://img.shields.io/badge/Python-3.11%2B-blue) ![XGBoost](https://img.shields.io/badge/XGBoost-SHAP-teal)

> ⚠️ **Disclaimer:** Sistem ini adalah alat bantu skrining awal untuk keperluan riset/akademik, **bukan alat diagnosis medis**.

## Fitur Utama

- **Pipeline ML reproducible (`ml/`)** — dataset BRFSS 2015 (253.680 baris, 21 fitur), eksperimen `E0`–`E6` (Class Weight, SMOTE, SMOTENC, SMOTETomek, Optuna), Weakness Gate, seleksi model terbaik, evaluasi 3 kelas (Non-Diabetes / Prediabetes / Diabetes).
- **Explainable AI (SHAP)** — TreeSHAP global & lokal dengan verifikasi aditif.
- **Interpretasi LLM + guardrail 7 lapis** — memakai **API key pihak ketiga** (OpenAI-compatible, Gemini, Groq, OpenRouter, DeepSeek, Anthropic). Tanpa key → otomatis memakai narasi template deterministik.
- **Aplikasi web Laravel (`web/`)** — 3 peran (Pengguna, Tenaga Medis, Admin/Peneliti), skrining 21 fitur, laporan PDF, riwayat, dashboard eksperimen, penilaian narasi (Likert), audit log.

## Struktur Repositori

```
├── ml/        # Pipeline ML, FastAPI service, notebook, artifacts/
├── web/       # Aplikasi Laravel
├── docs/      # Panduan eksperimen, evaluasi LLM, etika & privasi
├── docker/ + docker-compose.yml
└── IMPLEMENTATION_PLAN.md
```

## Prasyarat

- Python 3.11+ , PHP 8.2+ & Composer, Node tidak wajib (Tailwind/Alpine via CDN)
- macOS: `brew install libomp` (diperlukan XGBoost)

## Instalasi dari Nol (setelah `git clone`)

```bash
git clone https://github.com/<username>/<repo>.git
cd <repo>

# 1) ML service
cd ml
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt && pip install -e .
cp .env.example .env
cd ..

# 2) Web Laravel
cd web
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
cd ..
```

## Dataset Kaggle (via command)

```bash
# Butuh ~/.kaggle/kaggle.json (Kaggle > Settings > Create New Token)
ml/.venv/bin/diaxplain download-dataset
# atau
ml/.venv/bin/kaggle datasets download -d alexteboul/diabetes-health-indicators-dataset -p ml/data/raw --unzip
```

Latih ulang seluruh eksperimen lalu impor ke database web:

```bash
ml/.venv/bin/diaxplain run-pipeline --trials 20
cd web && php artisan experiments:import
```

> Folder `ml/artifacts/` sudah disertakan (hasil eksperimen & model), sehingga aplikasi dapat langsung dijalankan tanpa training ulang.

## Menjalankan Aplikasi

```bash
# Terminal 1 — ML service (port 8001)
ml/.venv/bin/diaxplain serve --host 127.0.0.1 --port 8001

# Terminal 2 — Web (port 8000)
cd web && php artisan serve --port=8000
```

Buka http://127.0.0.1:8000

## Akun Demo (Seeder)

| Peran | Email | Password |
|---|---|---|
| Admin / Peneliti | `admin@diaxplain.id` | `password` |
| Tenaga Medis | `nakes@diaxplain.id` | `password` |
| Pengguna Umum | `pengguna@diaxplain.id` | `password` |

> Ubah password dan nonaktifkan tombol demo-login sebelum deployment publik.

## Konfigurasi API Key LLM (tanpa Ollama)

1. Login sebagai **Admin** → menu **API Key AI / LLM** (`/admin/llm-config`).
2. Pilih preset penyedia, isi API key, centang uji koneksi, lalu **Simpan**.
3. Alternatif lewat `.env` (`ml/.env`):

```env
LLM_PROVIDER=openai_compatible
LLM_API_KEY=isi-key-anda
LLM_BASE_URL=https://api.openai.com/v1
LLM_MODEL=gpt-4o-mini
```

**Jangan pernah commit file `.env` atau API key** (sudah di-ignore oleh `.gitignore`).

## Pengujian

```bash
cd web && php artisan test
cd .. && ml/.venv/bin/pytest ml/tests -v
```

## Docker (opsional)

```bash
docker compose up --build
```

## Dokumentasi

- [IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md)
- [docs/EXPERIMENT_GUIDE.md](docs/EXPERIMENT_GUIDE.md)
- [docs/LLM_EVALUATION_GUIDE.md](docs/LLM_EVALUATION_GUIDE.md)
- [docs/ETHICS_AND_PRIVACY.md](docs/ETHICS_AND_PRIVACY.md)

## Lisensi

Proyek akademik (tesis). Dataset © CDC BRFSS 2015 / Kaggle — mengikuti lisensi aslinya.
