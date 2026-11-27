# Panduan Admin & Peneliti — Aplikasi DiaXplain

Panduan ini ditujukan bagi **Administrator & Peneliti Tesis** (`admin`) untuk mengelola seluruh siklus riset dan operasional **DiaXplain**: memantau hasil eksperimen empiris `E0`–`E6` & TreeSHAP global (`RM1`–`RM6`), mengonfigurasi **API Key Layanan AI Pihak Ketiga** (tanpa Ollama), menyinkronkan artefak Python ML, mengelola versi model, mengatur pengguna & peran (RBAC), mengekspor data evaluasi pakar ke CSV, serta mengaudit log keamanan.

---

## 1. Masuk & Mengenal Tab Workspace Dashboard Admin

1. Masuk di `/login` menggunakan akun Administrator (`admin@diaxplain.id` / sandi default `password`, atau melalui **Mode Demo Sidang** → **3. Admin & Peneliti**).
2. Pada sidebar kiri, Anda memiliki akses penuh ke seluruh 3 kategori:
   - **`UTAMA`**: Dashboard, Skrining Baru, Riwayat Skrining, Bandingkan Skrining.
   - **`KLINIS & EVALUASI`**: Penilaian Narasi (RM6).
   - **`RISET & SISTEM`**: Eksperimen E0–E6 & SHAP, Konfigurasi AI / LLM, Tata Kelola & Audit.
3. Halaman **Dashboard** (`/dashboard`) tersusun dalam **3 Tab Workspace**:
   - **`Ringkasan Sistem & AI`**: Menampilkan 4 KPI utama (Skenario Eksperimen Terimpor, Layanan AI/LLM beserta status `API Aktif` / `Mode Templat`, Jumlah Penilaian Pakar RM6, Total Skrining & Akun) serta strip status koneksi FastAPI ML Service.
   - **`Aktivitas Skrining Pribadi`**: Ringkasan 4 KPI skrining dan tabel riwayat skrining akun Anda.
   - **`Status Evaluasi RM6`**: Progres penilaian narasi LLM dan pintasan cepat menuju antrean penilaian serta ekspor CSV.

---

## 2. Mengelola Dashboard Eksperimen Riset (`RM1`–`RM6`)

Buka menu **Eksperimen E0–E6 & SHAP** (`/admin/eksperimen`). Halaman ini menyajikan seluruh temuan empiris langsung dari artefak pipeline Python (`ml/artifacts/`):

1. **KPI Ringkasan Riset (RM1–RM6)**: Macro-F1 Baseline `E0` & Recall Prediabetes, jumlah temuan *Weakness Gate*, skor model terbaik terpilih (mis. `E6`) beserta ROC-AUC OvR, dan skor *Faithfulness* LLM + Top-1 fitur SHAP global.
2. **Diagnosis Gerbang Kelemahan Baseline (`RM2`)**: Menampilkan temuan otomatis (`W1_MINORITY_CLASS_BIAS`, `W2_ACCURACY_PARADOX`, `W3_HYPERPARAMETER_SUBOPTIMALITY`) beserta aksi rekomendasi empiris.
3. **Matriks Perbandingan Skenario `E0`–`E6` (`RM3`–`RM4`)**: Tabel lengkap CV Macro-F1 (Mean ± SD), Test Accuracy, Balanced Accuracy, Test Macro-F1, Recall Kelas 1 (Prediabetes), Recall Kelas 2 (Diabetes), ROC-AUC OvR, dan kotak **Justifikasi Empiris Model Terbaik**.
4. **Grafik Perbandingan & Confusion Matrix 3×3 Interaktif**: Pilih skenario (`E0` s.d. `E6`) pada dropdown di sudut kartu *Confusion Matrix* untuk meninjau distribusi prediksi benar dan misklasifikasi per kelas.
5. **Peringkat Kepentingan Fitur Global TreeSHAP (`RM5`)**: Grafik batang horizontal Top-10 dan tabel peringkat `mean(|SHAP|)` lintas 3 kelas.
6. **Sinkronisasi Ulang Artefak ML**: Setelah menjalankan ulang training Python (`diaxplain run-pipeline`), klik tombol **`↻ Sinkronkan Artefak ML`** (atau jalankan `php artisan experiments:import`) untuk memperbarui seluruh metrik, model aktif, dan grafik di database Laravel.

---

## 3. Mengonfigurasi API Key Layanan AI Pihak Ketiga (Tanpa Ollama)

Buka menu **Konfigurasi AI / LLM** (`/admin/llm-config`):

1. **Pilih Preset Penyedia Cepat** (mengisi *Adapter Protokol*, *Base URL*, dan *Nama Model* otomatis):
   - **OpenAI Official** (`openai_compatible` · `gpt-4o-mini`)
   - **Google Gemini** (`gemini` · `gemini-2.5-flash`)
   - **Groq Cloud** (`openai_compatible` · `llama-3.3-70b-versatile`)
   - **OpenRouter** (`openai_compatible` · `openai/gpt-4o-mini`)
   - **DeepSeek API** (`openai_compatible` · `deepseek-chat`)
   - **Anthropic Claude** (`anthropic` · `claude-3-5-haiku-latest`)
   - Atau pilih **`Template Fallback Deterministik`** untuk menonaktifkan panggilan API eksternal sepenuhnya.
2. **Masukkan API Key**: Ketik API key Anda pada kolom **API Key Pihak Ketiga**. Key yang tersimpan akan selalu ditampilkan dalam bentuk tersamarkan (*masked*) demi keamanan. Untuk menghapus key tersimpan, centang *"Hapus API Key yang tersimpan"*.
3. **Atur Parameter Inferensi**:
   - **Temperature**: Disarankan `0.2` agar narasi stabil dan faktual.
   - **Fitur Top-K SHAP**: Default `6` (rentang 3–15 fitur teratas yang dikirim ke prompt LLM).
   - **Versi Prompt**: Default `p1.0` (tercatat pada setiap interpretasi untuk audit eksperimen prompt).
4. **Simpan & Uji Koneksi**: Pastikan kotak *"Uji koneksi langsung saat menyimpan"* tercentang, lalu klik **Simpan & Sinkronkan ke ML Service**. Sistem akan menyimpan konfigurasi di database Laravel sekaligus menyinkronkannya ke FastAPI ML Service (`/v1/llm/config`).
5. **Pantau Log Panggilan LLM**: Di bagian bawah halaman tersedia tabel log panggilan terbaru (waktu, kasus, prediksi, provider/model, mode `Live LLM API` / `Mode Templat`, dan latensi dalam `ms`).

> **Keamanan Kredensial:** Jangan pernah mempublikasikan file `ml/.env` atau `web/.env` ke repositori publik. Keduanya sudah dikecualikan pada `.gitignore`.

---

## 4. Tata Kelola Model, Pengguna (RBAC) & Log Audit

Klik menu **Tata Kelola & Audit** pada sidebar kiri untuk mengakses 3 sub-halaman:

### A. Registri Model XGBoost (`/admin/models`)
- Menampilkan seluruh versi model yang terdaftar (tag versi, nama model, teknik, Macro-F1, Balanced Accuracy, jumlah prediksi, dan status `Aktif` / `Arsip`).
- Klik **Aktifkan** pada versi model tertentu untuk mengganti model aktif yang melayani skrining.

### B. Manajemen Pengguna & RBAC (`/admin/users`)
- **Tambah Pengguna Baru**: Klik tombol **`+ Tambah Pengguna Baru`** untuk membuka modal dialog pendaftaran akun baru (`Pengguna Umum`, `Tenaga Medis / Klinisi`, atau `Admin & Peneliti`).
- **Ubah Peran / Status Akun**: Pada setiap baris pengguna, ubah dropdown peran atau status (`Aktif` / `Nonaktif`), lalu klik **Simpan**. Akun berstatus `Nonaktif` otomatis ditolak saat mencoba login.

### C. Log Audit Keamanan (`/admin/audit-logs`)
- Mencatat secara kronologis seluruh aktivitas sensitif: `auth.login`, `auth.demo_switch`, `skrining.unduh_pdf`, `privasi.hapus_semua_skrining`, `nakes.nilai_narasi`, `admin.ubah_konfigurasi_llm`, `admin.impor_eksperimen`, `admin.ubah_role_pengguna`, dan `admin.ekspor_csv_penilaian`.

---

## 5. Ekspor Data Evaluasi Pakar (Lampiran Tesis)

Untuk mengolah data penilaian pakar (uji reliabilitas *Krippendorff's Alpha*, rata-rata Likert per aspek, dan perbandingan provider) pada Bab Hasil Tesis:
- Klik tombol **Unduh CSV Penilaian** pada Dashboard Admin (`Tab Status Evaluasi RM6`), halaman **Eksperimen E0–E6**, atau halaman **Penilaian Narasi (RM6)** (`/admin/ratings/export`).
- File CSV (`DiaXplain_Evaluasi_Narasi_LLM_<timestamp>.csv`) berisi kolom: `id_rating`, `id_interpretasi`, `id_skrining`, `label_prediksi`, `provider_llm`, `model_llm`, `versi_prompt`, `used_fallback`, `penilai_nama`, `penilai_role`, `skor_akurasi`, `skor_kejelasan`, `skor_kegunaan`, `skor_keamanan`, `skor_rerata`, `komentar`, dan `tanggal_penilaian`.

---

## 6. Alur Operasional Pembaruan Dataset Kaggle & Pelatihan Ulang

Jika ingin melatih ulang seluruh eksperimen menggunakan dataset penuh Kaggle BRFSS 2015:

```bash
# 1. Unduh dataset Kaggle (butuh ~/.kaggle/kaggle.json)
cd ml
./.venv/bin/diaxplain download-dataset --force

# 2. Jalankan ulang seluruh pipeline (EDA, E0-E6, Weakness Gate, SHAP, Evaluasi LLM)
./.venv/bin/diaxplain run-pipeline --trials 20

# 3. Sinkronkan artefak terbaru ke database aplikasi web Laravel
cd ../web
php artisan experiments:import
```

Setelah selesai, buka `/admin/eksperimen` untuk memverifikasi metrik terbaru dan pastikan FastAPI ML Service (`diaxplain serve --host 127.0.0.1 --port 8001`) aktif.
