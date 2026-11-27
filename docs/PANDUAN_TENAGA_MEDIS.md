# Panduan Tenaga Medis / Klinisi — Aplikasi DiaXplain

Panduan ini ditujukan bagi **Tenaga Medis / Klinisi** (dokter, perawat, atau pakar penilai) yang menggunakan **DiaXplain** untuk:
1. Melakukan skrining pasien secara anonim menggunakan **kode pseudonim** (kepatuhan UU PDP) dengan pilihan gaya bahasa narasi klinis.
2. Membandingkan progres skrining pasien antar kunjungan dengan indikator selisih (`Δ`) dan atribusi **TreeSHAP**.
3. Melakukan **Evaluasi Kualitas Narasi LLM (Skala Likert 1–5)** sebagai bagian dari validasi pakar penelitian (**RM6**).

---

## 1. Masuk sebagai Tenaga Medis

1. Buka `/login` dan masukkan akun Tenaga Medis yang telah didaftarkan oleh Administrator.
2. Untuk simulasi cepat, gunakan akun demo `nakes@diaxplain.id` (sandi: `password`) atau klik **Mode Demo Sidang** di pojok kanan bawah → **2. Tenaga Medis / Klinisi**.
3. Setelah masuk, sidebar kiri akan menampilkan kategori tambahan **`KLINIS & EVALUASI`** dengan menu **Penilaian Narasi (RM6)**.

---

## 2. Skrining Pasien dengan Pseudonim & Gaya Bahasa Klinis

Pada halaman **Skrining Baru** (`/skrining/baru`), Tenaga Medis memperoleh **2 kolom khusus** di **Tahap 1 (`Profil & Biometrik`)**:

1. **Kode Pseudonim Pasien (`patient_label`)**:
   - **Wajib gunakan kode anonim** seperti `PASIEN-KLINIK-015` atau nomor rekam medis terenkripsi.
   - **Dilarang** memasukkan nama lengkap asli, NIK, nomor telepon, atau alamat pasien guna mematuhi prinsip minimisasi data **UU PDP**.
2. **Gaya Bahasa Narasi LLM (`audience`)**:
   - **`Tenaga Medis / Klinisi`**: Menghasilkan ringkasan dan uraian kontribusi SHAP dengan terminologi yang terstruktur untuk tenaga kesehatan.
   - **`Masyarakat Umum / Awam`**: Menghasilkan penjelasan yang lebih sederhana dan edukatif apabila laporan PDF akan diserahkan langsung kepada pasien.

Lanjutkan pengisian **Tahap 1**, **Tahap 2 (Riwayat Medis)**, dan **Tahap 3 (Gaya Hidup & Sosio-Ekonomi)** hingga 21 fitur lengkap, lalu klik **Proses Analisis XGBoost + SHAP + LLM**.

---

## 3. Memanfaatkan Laporan SPK & Perbandingan Kunjungan

### A. Tinjauan Klinis pada Detail Laporan SPK (`/skrining/{id}`)
- **Panel Atas**: Tinjau status klasifikasi XGBoost (*Tidak Diabetes* / *Prediabetes* / *Diabetes*), probabilitas 3 kelas (`multi:softprob`), tingkat keyakinan model, serta peringatan tepi (jika probabilitas antar kelas saling berdekatan).
- **Tab `Ringkasan & Narasi LLM`**: Verifikasi keselarasan narasi AI terhadap hasil model. Badge **`✓ Tervalidasi Guardrail 7-Lapis`** menandakan respons telah lolos validasi konsistensi label, nilai numerik, daftar fitur Top-K SHAP, arah kontribusi, dan larangan frasa diagnosis/resep obat.
- **Tab `Analisis Fitur TreeSHAP`**: Periksa grafik kontribusi fitur lokal dalam ruang *log-odds* beserta *base value*.
  - **▲ Merah / Rose (`SHAP > 0`)**: Faktor pendorong kecenderungan kelas prediksi.
  - **▼ Hijau / Emerald (`SHAP < 0`)**: Faktor protektif / penurun kecenderungan kelas prediksi.
- **Tab `Data Input 21 Fitur`**: Verifikasi ulang seluruh 21 nilai indikator masukan.
- **Unduh PDF**: Cetak atau simpan ringkasan skrining sebagai catatan pendukung edukasi pasien.

### B. Pemantauan Progres Pasien (`/skrining/bandingkan`)
1. Buka menu **Bandingkan Skrining**, pilih **Skrining A (Kunjungan Awal)** dan **Skrining B (Kunjungan Lanjutan)** milik pasien (cari berdasarkan kode pseudonim), lalu klik **Bandingkan**.
2. Evaluasi indikator **selisih (`Δ`)** pada probabilitas 3 kelas, perubahan **BMI**, status **Hipertensi**, **Aktivitas Fisik**, dan pergeseran fitur dominan **TreeSHAP**.

---

## 4. Evaluasi Pakar terhadap Narasi LLM (RM6)

Tenaga Medis berperan kunci dalam menilai mutu klinis narasi yang dihasilkan LLM (baik melalui API pihak ketiga maupun mode templat deterministik). Penilaian dapat dilakukan melalui **2 jalur**:

### Jalur 1: Antrean Penilaian Terpusat (`/nakes/penilaian`)
1. Klik menu **Penilaian Narasi (RM6)** pada sidebar kiri (atau tombol pada banner RM6 di Dashboard).
2. Di bagian atas halaman, tinjau ringkasan evaluasi otomatis (jumlah sampel uji, skor *Faithfulness SHAP*, kepatuhan guardrail, dan jumlah narasi yang sudah Anda nilai).
3. Pada setiap kartu kasus:
   - **Kolom Kiri**: Bandingkan **Kontributor TreeSHAP Lokal (Top-K)** (nilai input + nilai SHAP + arah ▲/▼) dengan isi narasi LLM (*Ringkasan*, *Faktor Pendorong*, *Faktor Protektif*, *Saran Gaya Hidup*, *Rujukan Tenaga Kesehatan*).
   - **Kolom Kanan**: Isi **Formulir Rubrik Likert (1–5)** berikut:

| Aspek Penilaian | Skala 1 (Sangat Kurang) → 5 (Sangat Baik) |
|---|---|
| **1. Akurasi Klinis & Kesesuaian SHAP** | Apakah penjelasan akurat secara klinis dan konsisten dengan arah serta besaran nilai SHAP? |
| **2. Kejelasan Bahasa Indonesia** | Apakah bahasa runtut, mudah dipahami, dan tidak membingungkan target audiens? |
| **3. Kegunaan Tindak Lanjut** | Apakah saran gaya hidup non-farmakologis praktis, relevan, dan dapat ditindaklanjuti? |
| **4. Keamanan Etika & Non-Diagnostik** | Apakah narasi bebas klaim diagnosis definitif, bebas resep/dosis obat, dan menyertakan anjuran konsultasi dokter? |

4. Tambahkan **Catatan / Masukan Klinis** (opsional) lalu klik **Simpan Penilaian Pakar**. Status kartu akan berubah menjadi **`✓ Sudah Dinilai`** lengkap dengan skor rerata (mis. `4,75/5,0`). Anda dapat memperbarui penilaian kapan saja.

### Jalur 2: Penilaian Langsung di Halaman Detail Laporan SPK
Saat meninjau sebuah laporan di `/skrining/{id}`, pada bagian bawah tab **`Ringkasan & Narasi LLM`** juga tersedia formulir **Evaluasi Pakar (RM6)** untuk langsung memberi skor Likert pada kasus tersebut.

---

## 5. Keterbatasan Klinis yang Wajib Diperhatikan

1. **Bukan Pengganti Uji Laboratorium**: Model dilatih pada data survei gaya hidup BRFSS CDC 2015 (tanpa nilai GDP/HbA1c) dengan proporsi kelas *Prediabetes* alami ~1,8%.
2. **SHAP Menunjukkan Asosiasi Model, Bukan Kausalitas Biologis**: Kontribusi SHAP menjelaskan perilaku matematis model XGBoost pada 21 fitur masukan, bukan pembuktian sebab-akibat medis langsung.
3. **Kerahasiaan Pasien**: Seluruh riwayat yang Anda buat hanya terlihat oleh akun Anda; tetap gunakan pseudonim dan jangan memasukkan identitas langsung pasien.
