# Etika Penelitian, Privasi Data Kesehatan, dan Batasan Sistem

1. **Bukan Alat Diagnosis Medis:** DiaXplain adalah purwarupa *Sistem Pendukung Keputusan (Decision Support System)* untuk skrining awal risiko diabetes berbasis indikator kesehatan survei BRFSS 2015, bukan pengganti pemeriksaan laboratorium klinis (Gula Darah Puasa, TTGO, atau HbA1c).
2. **Pelindungan Data Pribadi (UU No. 27 Tahun 2022):**
   - Sistem hanya mengirimkan **21 fitur numerik/ordinal** dan `case_id` anonim ke layanan ML dan API LLM pihak ketiga.
   - Nama pengguna, alamat email, maupun kode pseudonim pasien (`patient_label`) **tidak pernah** dikirimkan ke layanan eksternal.
   - Pengguna memiliki kendali penuh untuk menghapus seluruh riwayat skrining pribadinya kapan saja.
3. **Keterbatasan Populasi Dataset:**
   - Dataset *Diabetes Health Indicators* berasal dari survei telepon BRFSS CDC di Amerika Serikat (2015) dengan 253.680 responden dewasa.
   - Variabel sosial-ekonomi seperti `Income` menggunakan skala pendapatan tahunan AS dan disajikan sebagai skala ekonomi relatif (1–8).

