# Panduan Pengguna Umum — Aplikasi DiaXplain

Panduan ini ditujukan bagi **Pengguna Umum (Masyarakat / Individu)** yang ingin melakukan skrining mandiri risiko status diabetes (*Tidak Diabetes*, *Prediabetes*, atau *Diabetes*), memahami faktor pendorong & protektif melalui grafik **TreeSHAP**, membaca narasi penjelasan **AI (LLM)**, memantau perubahan antar waktu, serta mengelola privasi data pribadi sesuai **UU PDP**.

> **Penting:** DiaXplain adalah **alat bantu skrining awal**, bukan alat diagnosis medis. Segera konsultasikan hasil risiko sedang (*Prediabetes*) atau tinggi (*Diabetes*) ke dokter atau fasilitas kesehatan terdekat untuk pemeriksaan laboratorium (Gula Darah Puasa, TTGO, atau HbA1c).

---

## 1. Registrasi & Masuk ke Akun

### A. Membuat Akun Baru
1. Buka halaman utama aplikasi (`/`), lalu klik tombol **Daftar Akun** di pojok kanan atas.
2. Isi formulir pendaftaran pada `/register`:
   - **Nama Lengkap**
   - **Alamat Email** aktif
   - **Kata Sandi Baru** (minimal 8 karakter) dan **Konfirmasi Kata Sandi**
   - Centang kotak persetujuan pemahaman bahwa DiaXplain bukan alat diagnosis medis.
3. Klik **Buat Akun & Mulai Skrining**. Anda akan otomatis masuk ke halaman **Dashboard**.

### B. Masuk dengan Akun Terdaftar / Akun Demo
1. Klik **Masuk** (`/login`), lalu masukkan email dan kata sandi Anda.
2. Untuk simulasi cepat tanpa mendaftar, gunakan akun demo `pengguna@diaxplain.id` (sandi: `password`) atau klik tombol **Mode Demo Sidang** di pojok kanan bawah layar → pilih **1. Pengguna Umum**.

---

## 2. Mengenal Dashboard Pengguna Umum

Pada halaman **Dashboard** (`/dashboard`), Anda dapat melihat:
- **Menu Sidebar Kiri (`UTAMA`)**: `Dashboard`, `Skrining Baru`, `Riwayat Skrining`, dan `Bandingkan Skrining`, serta tautan `Metodologi & Batasan` di bagian bawah sidebar.
- **4 Kartu Ringkasan KPI**: Total skrining yang pernah Anda lakukan, serta jumlah hasil *Tidak Diabetes (0)*, *Prediabetes (1)*, dan *Diabetes (2)*.
- **Tabel Riwayat Skrining Terbaru**: Menampilkan ID skrining, tanggal, BMI & kategori usia, status klasifikasi, tingkat keyakinan model, status narasi AI, serta tombol cepat **Detail SPK** dan **PDF**.

---

## 3. Melakukan Skrining Baru (21 Indikator BRFSS)

Klik tombol **`+ Skrining Baru`** pada topbar atau sidebar (`/skrining/baru`). Formulir dibagi menjadi **3 tahap terstruktur** dengan indikator progres:

### Tahap 1: Profil & Biometrik
1. **Jenis Kelamin Biologis (`Sex`)**: Pilih Perempuan atau Laki-laki.
2. **Usia Saat Ini (Tahun)**: Ketik usia Anda (minimal 18 tahun). Sistem otomatis memetakan ke 13 kategori usia BRFSS (`Age`).
3. **Kalkulator BMI Otomatis**: Masukkan **Berat Badan (kg)** dan **Tinggi Badan (cm)** — nilai **BMI** serta kategori status gizinya (*Normal Ideal*, *Overweight*, *Obesitas*) akan dihitung otomatis. Anda juga dapat menyesuaikan nilai `BMI` secara langsung.
4. **Persetujuan**: Pastikan kotak persetujuan pemrosesan 21 indikator dan pemahaman non-diagnostik tercentang, lalu klik **Lanjut Tahap Berikutnya →**.

*(Tips Uji Coba: Di kanan atas halaman tersedia tombol **Simulasi Kasus** — `Sehat`, `Prediabetes`, `Risiko Tinggi` — untuk mengisi contoh profil secara instan.)*

### Tahap 2: Riwayat Medis
Jawab kondisi klinis yang pernah Anda alami:
- Tekanan Darah Tinggi (`HighBP`), Kolesterol Tinggi (`HighChol`), Cek Kolesterol 5 tahun terakhir (`CholCheck`), Riwayat Stroke (`Stroke`), Penyakit Jantung Koroner/Serangan Jantung (`HeartDiseaseorAttack`), dan Kesulitan Berjalan/Naik Tangga (`DiffWalk`).
- **Penilaian Kesehatan Umum (`GenHlth`)**: Skala 1 (*Sangat Baik*) sampai 5 (*Buruk*).
- **Hari Kesehatan Mental (`MentHlth`)** & **Hari Kesehatan Fisik (`PhysHlth`)** kurang baik dalam 30 hari terakhir (0–30 hari).
- Klik **Lanjut Tahap Berikutnya →**.

### Tahap 3: Gaya Hidup & Persepsi Sosial-Ekonomi
1. Jawab pertanyaan gaya hidup & akses layanan: Riwayat Merokok ≥ 100 batang (`Smoker`), Aktivitas Fisik 30 hari terakhir (`PhysActivity`), Konsumsi Buah Harian (`Fruits`), Konsumsi Sayur Harian (`Veggies`), Konsumsi Alkohol Berat (`HvyAlcoholConsump`), Kepemilikan Jaminan Kesehatan/BPJS (`AnyHealthcare`), dan Kendala Biaya Berobat (`NoDocbcCost`).
2. Pilih tingkat **Pendidikan Terakhir (`Education`)** dan **Pendapatan Rumah Tangga (`Income`)** skala relatif 1–8.
3. Periksa strip ringkasan **21 / 21 Fitur Terisi** di bagian bawah, lalu klik **Proses Analisis XGBoost + SHAP + LLM**.

---

## 4. Membaca Laporan Hasil SPK (`/skrining/{id}`)

Setelah formulir dikirim, Anda diarahkan ke halaman **Detail Laporan SPK** yang terdiri dari:

### A. Ringkasan Prediksi & Distribusi Probabilitas (Bagian Atas)
- **Kartu Status Klasifikasi**: Menampilkan hasil kelas prediksi teratas (*Tidak Diabetes*, *Prediabetes*, atau *Diabetes*), persentase probabilitas, dan tingkat keyakinan model.
- **Grafik Probabilitas 3 Kelas**: Visualisasi batang horizontal peluang dari ketiga kelas berdasarkan keluaran `multi:softprob` model XGBoost.
- **Tombol Aksi**: Klik **Unduh PDF** untuk menyimpan laporan lengkap yang dapat dibawa saat konsultasi dokter, atau **+ Skrining Ulang** untuk memulai skrining baru.

### B. Tiga Tab Analisis Detail
1. **Tab `Ringkasan & Narasi LLM`**:
   - Menampilkan penjelasan naratif berbahasa Indonesia yang mudah dipahami, dibagi menjadi **Ringkasan**, **▲ Faktor Pendorong Risiko**, **▼ Faktor Protektif / Penurun**, **Saran Gaya Hidup Sehat Umum (Non-Farmakologis)**, dan **Kapan Perlu ke Dokter / Faskes**.
   - Setiap narasi melewati **Guardrail 7-Lapis** sehingga bebas halusinasi angka, bebas klaim diagnosis definitif, dan tidak meresepkan obat.
   - Klik **↻ Susun Ulang Narasi AI** jika ingin menghasilkan ulang penjelasan dengan konfigurasi AI terbaru.
2. **Tab `Analisis Fitur TreeSHAP`**:
   - Menampilkan grafik batang kontribusi lokal **TreeSHAP** dan daftar Top-K fitur paling berpengaruh:
     - **▲ Merah / Rose (`SHAP > 0`)**: Indikator yang mendorong kecenderungan ke arah kelas prediksi.
     - **▼ Hijau / Emerald (`SHAP < 0`)**: Indikator protektif yang menahan atau menurunkan kecenderungan kelas prediksi.
3. **Tab `Data Input 21 Fitur`**:
   - Menampilkan tabel kartu lengkap seluruh 21 nilai indikator yang Anda kirimkan untuk transparansi penuh.

---

## 5. Mengelola Riwayat & Membandingkan Skrining

### A. Riwayat Skrining (`/skrining/riwayat`)
- Gunakan **Tab Filter Status** (`Semua`, `Tidak Diabetes`, `Prediabetes`, `Diabetes`) untuk menyaring riwayat berdasarkan hasil klasifikasi.
- Gunakan kolom **Cari** di kanan toolbar untuk mencari berdasarkan kode skrining/pseudonim.
- Pada setiap baris tabel tersedia aksi **Detail** (buka laporan SPK), **PDF** (unduh laporan), dan **Hapus** (hapus satu riwayat tertentu).

### B. Bandingkan 2 Skrining Antar-Waktu (`/skrining/bandingkan`)
Fitur ini berguna untuk memantau perkembangan setelah perubahan gaya hidup (misalnya penurunan berat badan/BMI atau rutin berolahraga):
1. Pilih **Skrining A (Acuan)** dan **Skrining B (Pembanding)**, lalu klik **Bandingkan**.
2. Amati **progress bar probabilitas 3 kelas** beserta indikator **selisih (`Δ`)** pada Skrining B (contoh: `Δ -12,4%` atau `Δ +8,1%`).
3. Periksa perubahan nilai **BMI**, status **Hipertensi**, **Aktivitas Fisik**, dan pergeseran faktor **Top TreeSHAP** di kedua kartu.
4. *(Jika Anda memilih dua ID skrining yang sama persis, sistem akan menampilkan banner info pengingat.)*

---

## 6. Hak Privasi & Penghapusan Data Pribadi (UU PDP)

Sesuai **UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi (UU PDP)**:
1. Data riwayat skrining Anda bersifat pribadi — pengguna lain tidak dapat mengakses laporan Anda (dilindungi kontrol otorisasi per pemilik data).
2. Untuk menghapus **seluruh** riwayat skrining Anda secara permanen, buka menu profil di kanan atas → klik **Privasi & Data Saya (UU PDP)** (atau gulir ke bagian bawah halaman **Riwayat Skrining**).
3. Klik tombol **Hapus Semua Data Saya (UU PDP)**, baca dialog konfirmasi, lalu klik **Ya, Hapus Permanen**. Tindakan ini tidak dapat dibatalkan.
