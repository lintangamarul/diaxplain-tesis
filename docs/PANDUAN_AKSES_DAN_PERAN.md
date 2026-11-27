# Panduan Akses, Peran (RBAC) & Navigasi — DiaXplain

Dokumen ini merupakan titik masuk utama bagi seluruh pengguna aplikasi **DiaXplain** (*Sistem Pendukung Keputusan Klasifikasi Status Diabetes berbasis XGBoost, TreeSHAP, dan LLM*). Pilih panduan khusus sesuai peran Anda di bawah ini.

> **Disclaimer Klinis:** DiaXplain adalah alat bantu skrining awal berbasis indikator survei populasi (BRFSS CDC 2015) untuk keperluan riset dan edukasi, **bukan alat diagnosis medis definitif** dan tidak menggantikan pemeriksaan dokter maupun uji laboratorium (GDP, TTGO, HbA1c).

---

## 1. Daftar Panduan per Peran

| Peran (Role) | Target Pengguna | Dokumen Panduan Lengkap |
|---|---|---|
| **Pengguna Umum** (`pengguna`) | Masyarakat umum / individu yang melakukan skrining mandiri | [PANDUAN_PENGGUNA_UMUM.md](PANDUAN_PENGGUNA_UMUM.md) |
| **Tenaga Medis / Klinisi** (`nakes`) | Dokter, perawat, analis klinis, dan pakar penilai narasi LLM (RM6) | [PANDUAN_TENAGA_MEDIS.md](PANDUAN_TENAGA_MEDIS.md) |
| **Admin & Peneliti** (`admin`) | Peneliti tesis, pengelola model ML, konfigurasi API Key AI, & administrator sistem | [PANDUAN_ADMIN_PENELITI.md](PANDUAN_ADMIN_PENELITI.md) |

---

## 2. Matriks Hak Akses Fitur (RBAC)

| Modul / Fitur | Publik (Tamu) | Pengguna Umum | Tenaga Medis | Admin & Peneliti |
|---|:---:|:---:|:---:|:---:|
| Beranda & Metodologi Model (`/`, `/tentang-model`) | ✓ | ✓ | ✓ | ✓ |
| Daftar & Masuk Akun (`/register`, `/login`) | ✓ | — | — | — |
| Dashboard Personal & KPI Skrining (`/dashboard`) | — | ✓ | ✓ | ✓ (Tab Workspace) |
| Skrining Baru 21 Indikator (`/skrining/baru`) | — | ✓ (Mandiri) | ✓ (+ Pseudonim & Gaya Bahasa) | ✓ (+ Pseudonim & Gaya Bahasa) |
| Laporan Detail SPK: Probabilitas, TreeSHAP, Narasi LLM, PDF (`/skrining/{id}`) | — | ✓ (Milik Sendiri) | ✓ (Milik Sendiri) | ✓ (Semua / Milik Sendiri) |
| Riwayat Skrining, Filter & Pencarian (`/skrining/riwayat`) | — | ✓ | ✓ | ✓ |
| Perbandingan Antar-Skrining + Delta Δ (`/skrining/bandingkan`) | — | ✓ | ✓ | ✓ |
| Hapus Permanen Seluruh Data Pribadi — UU PDP (`/skrining/riwayat#privasi-uupdp`) | — | ✓ | ✓ | ✓ |
| Penilaian Kualitas Narasi LLM Skala Likert 1–5 — RM6 (`/nakes/penilaian`) | — | — | ✓ | ✓ |
| Dashboard Eksperimen E0–E6, Weakness Gate & SHAP Global (`/admin/eksperimen`) | — | — | — | ✓ |
| Konfigurasi API Key Layanan AI Pihak Ketiga (`/admin/llm-config`) | — | — | — | ✓ |
| Registri Model XGBoost, Manajemen Pengguna & Log Audit (`/admin/models`, `/admin/users`, `/admin/audit-logs`) | — | — | — | ✓ |
| Ekspor CSV Penilaian Pakar LLM (`/admin/ratings/export`) | — | — | — | ✓ |

---

## 3. Akun Demo & Mode Demo Sidang

Untuk keperluan demonstrasi, pengujian, atau sidang tesis, aplikasi menyediakan **3 akun demo bawaan** (hasil *seeder*) dengan kata sandi default **`password`**:

| Peran | Email | Password |
|---|---|---|
| **Admin & Peneliti** | `admin@diaxplain.id` | `password` |
| **Tenaga Medis / Klinisi** | `nakes@diaxplain.id` | `password` |
| **Pengguna Umum** | `pengguna@diaxplain.id` | `password` |

> **Cara Berpindah Peran 1-Klik:** Klik tombol mengambang **"Mode Demo Sidang"** di pojok kanan bawah layar, lalu pilih peran yang ingin disimulasikan. Anda akan langsung masuk ke *workspace* peran tersebut.

---

## 4. Memahami Antarmuka Aplikasi (App Shell)

Setelah masuk ke sistem, seluruh peran menggunakan tata letak **Clinical SaaS** yang seragam:
1. **Sidebar Kiri (Collapsible)**: Menampilkan logo DiaXplain, tag versi model aktif (mis. `xgb-v1.0.0`), dan menu yang dikelompokkan sesuai hak akses (`UTAMA`, `KLINIS & EVALUASI`, `RISET & SISTEM`). Klik ikon panah ganda `«` pada topbar untuk menciutkan sidebar menjadi mode ikon di desktop, atau ikon garis tiga `☰` di perangkat seluler.
2. **Topbar Atas (Sticky)**: Menampilkan *breadcrumb* halaman aktif, tombol cepat **`+ Skrining Baru`**, serta **Menu Profil Pengguna** (klik avatar inisial di pojok kanan atas untuk melihat profil, pintasan **Privasi & Data Saya (UU PDP)**, dan tombol **Keluar dari Sesi**).
3. **Notifikasi & Validasi**: Setiap aksi penting (simpan skrining, perbarui penilaian, simpan API key, atau kesalahan isian) akan ditampilkan secara otomatis pada banner notifikasi di bagian atas halaman.
