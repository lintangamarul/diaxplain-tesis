"""Single source of truth for the 21 BRFSS 2015 Diabetes Health Indicators features."""

from __future__ import annotations

import json
from pathlib import Path
from typing import Any

TARGET_COLUMN = "Diabetes_012"

LABEL_MAP: dict[int, str] = {
    0: "No Diabetes",
    1: "Prediabetes",
    2: "Diabetes",
}

LABEL_DISPLAY_ID: dict[int, str] = {
    0: "Tidak Diabetes",
    1: "Prediabetes",
    2: "Diabetes",
}

LABEL_REVERSE_MAP: dict[str, int] = {v: k for k, v in LABEL_MAP.items()}

FEATURE_ORDER: list[str] = [
    "HighBP",
    "HighChol",
    "CholCheck",
    "BMI",
    "Smoker",
    "Stroke",
    "HeartDiseaseorAttack",
    "PhysActivity",
    "Fruits",
    "Veggies",
    "HvyAlcoholConsump",
    "AnyHealthcare",
    "NoDocbcCost",
    "GenHlth",
    "MentHlth",
    "PhysHlth",
    "DiffWalk",
    "Sex",
    "Age",
    "Education",
    "Income",
]

CONTINUOUS_FEATURES = {"BMI", "MentHlth", "PhysHlth"}
CONTINUOUS_INDICES = [i for i, f in enumerate(FEATURE_ORDER) if f in CONTINUOUS_FEATURES]
CATEGORICAL_INDICES = [i for i, f in enumerate(FEATURE_ORDER) if f not in CONTINUOUS_FEATURES]

AGE_CATEGORIES: dict[int, str] = {
    1: "18–24 tahun",
    2: "25–29 tahun",
    3: "30–34 tahun",
    4: "35–39 tahun",
    5: "40–44 tahun",
    6: "45–49 tahun",
    7: "50–54 tahun",
    8: "55–59 tahun",
    9: "60–64 tahun",
    10: "65–69 tahun",
    11: "70–74 tahun",
    12: "75–79 tahun",
    13: "≥ 80 tahun",
}

GEN_HLTH_CATEGORIES: dict[int, str] = {
    1: "Sangat Baik (1)",
    2: "Baik Sekali (2)",
    3: "Baik / Cukup (3)",
    4: "Kurang Baik (4)",
    5: "Buruk (5)",
}

EDUCATION_CATEGORIES: dict[int, str] = {
    1: "Tidak sekolah / TK",
    2: "SD / Sederajat (Kelas 1–8)",
    3: "SMP / SMA belum tamat (Kelas 9–11)",
    4: "Tamat SMA / SMK / Sederajat",
    5: "Diploma / Kuliah 1–3 tahun",
    6: "Sarjana (S1/D4) atau lebih tinggi",
}

INCOME_CATEGORIES: dict[int, str] = {
    1: "Tingkat 1 (< $10.000 / terendah)",
    2: "Tingkat 2 ($10.000–$15.000)",
    3: "Tingkat 3 ($15.000–$20.000)",
    4: "Tingkat 4 ($20.000–$25.000)",
    5: "Tingkat 5 ($25.000–$35.000)",
    6: "Tingkat 6 ($35.000–$50.000)",
    7: "Tingkat 7 ($50.000–$75.000)",
    8: "Tingkat 8 (≥ $75.000 / tertinggi)",
}

BINARY_CATEGORIES: dict[int, str] = {
    0: "Tidak",
    1: "Ya",
}

SEX_CATEGORIES: dict[int, str] = {
    0: "Perempuan",
    1: "Laki-laki",
}

FEATURE_SPECS: dict[str, dict[str, Any]] = {
    "HighBP": {
        "index": 0,
        "type": "binary",
        "min": 0,
        "max": 1,
        "group": "kondisi_kesehatan",
        "label_id": "Tekanan Darah Tinggi (Hipertensi)",
        "question_id": "Apakah Anda pernah dinyatakan memiliki tekanan darah tinggi oleh dokter/tenaga kesehatan?",
        "help_id": "Pilih Ya jika pernah didiagnosis hipertensi.",
        "options": BINARY_CATEGORIES,
    },
    "HighChol": {
        "index": 1,
        "type": "binary",
        "min": 0,
        "max": 1,
        "group": "kondisi_kesehatan",
        "label_id": "Kolesterol Tinggi",
        "question_id": "Apakah Anda pernah dinyatakan memiliki kadar kolesterol darah tinggi?",
        "help_id": "Pilih Ya jika dokter pernah menyebutkan kolesterol Anda tinggi.",
        "options": BINARY_CATEGORIES,
    },
    "CholCheck": {
        "index": 2,
        "type": "binary",
        "min": 0,
        "max": 1,
        "group": "akses_layanan",
        "label_id": "Pemeriksaan Kolesterol (5 Tahun Terakhir)",
        "question_id": "Apakah Anda pernah memeriksa kadar kolesterol dalam 5 tahun terakhir?",
        "help_id": "Termasuk pemeriksaan di laboratorium, klinik, atau puskesmas.",
        "options": BINARY_CATEGORIES,
    },
    "BMI": {
        "index": 3,
        "type": "continuous",
        "min": 12.0,
        "max": 98.0,
        "group": "fisik_demografi",
        "label_id": "Indeks Massa Tubuh (BMI)",
        "question_id": "Berapa Indeks Massa Tubuh (BMI) Anda?",
        "help_id": "Dihitung otomatis dari berat badan (kg) dibagi kuadrat tinggi badan (m²).",
        "unit": "kg/m²",
    },
    "Smoker": {
        "index": 4,
        "type": "binary",
        "min": 0,
        "max": 1,
        "group": "gaya_hidup",
        "label_id": "Riwayat Merokok (≥ 100 Batang Seumur Hidup)",
        "question_id": "Apakah Anda pernah merokok minimal 100 batang (sekitar 5–8 bungkus) sepanjang hidup Anda?",
        "help_id": "Definisi standar survei kesehatan BRFSS CDC.",
        "options": BINARY_CATEGORIES,
    },
    "Stroke": {
        "index": 5,
        "type": "binary",
        "min": 0,
        "max": 1,
        "group": "kondisi_kesehatan",
        "label_id": "Riwayat Stroke",
        "question_id": "Apakah Anda pernah dinyatakan mengalami stroke?",
        "help_id": "Pilih Ya jika pernah didiagnosis stroke oleh dokter.",
        "options": BINARY_CATEGORIES,
    },
    "HeartDiseaseorAttack": {
        "index": 6,
        "type": "binary",
        "min": 0,
        "max": 1,
        "group": "kondisi_kesehatan",
        "label_id": "Penyakit Jantung Koroner / Serangan Jantung",
        "question_id": "Apakah Anda pernah dinyatakan memiliki penyakit jantung koroner (PJK) atau infark miokard (serangan jantung)?",
        "help_id": "Pilih Ya jika memiliki riwayat PJK atau serangan jantung.",
        "options": BINARY_CATEGORIES,
    },
    "PhysActivity": {
        "index": 7,
        "type": "binary",
        "min": 0,
        "max": 1,
        "group": "gaya_hidup",
        "label_id": "Aktivitas Fisik / Olahraga (30 Hari Terakhir)",
        "question_id": "Apakah dalam 30 hari terakhir Anda melakukan aktivitas fisik atau olahraga di luar pekerjaan utama?",
        "help_id": "Contoh: jalan cepat, lari, bersepeda, senam, berenang, berkebun.",
        "options": BINARY_CATEGORIES,
    },
    "Fruits": {
        "index": 8,
        "type": "binary",
        "min": 0,
        "max": 1,
        "group": "gaya_hidup",
        "label_id": "Konsumsi Buah Harian (≥ 1 Kali/Hari)",
        "question_id": "Apakah Anda mengonsumsi buah-buahan minimal 1 kali setiap hari?",
        "help_id": "Termasuk buah segar atau jus buah murni tanpa gula tambahan.",
        "options": BINARY_CATEGORIES,
    },
    "Veggies": {
        "index": 9,
        "type": "binary",
        "min": 0,
        "max": 1,
        "group": "gaya_hidup",
        "label_id": "Konsumsi Sayur Harian (≥ 1 Kali/Hari)",
        "question_id": "Apakah Anda mengonsumsi sayuran minimal 1 kali setiap hari?",
        "help_id": "Termasuk sayuran hijau, lalapan, sayur berkuah, atau tumis.",
        "options": BINARY_CATEGORIES,
    },
    "HvyAlcoholConsump": {
        "index": 10,
        "type": "binary",
        "min": 0,
        "max": 1,
        "group": "gaya_hidup",
        "label_id": "Konsumsi Alkohol Berat",
        "question_id": "Apakah Anda mengonsumsi minuman beralkohol dalam jumlah berat (pria ≥14 gelas/minggu, wanita ≥7 gelas/minggu)?",
        "help_id": "Pada populasi Indonesia umumnya pilih Tidak.",
        "options": BINARY_CATEGORIES,
    },
    "AnyHealthcare": {
        "index": 11,
        "type": "binary",
        "min": 0,
        "max": 1,
        "group": "akses_layanan",
        "label_id": "Kepemilikan Jaminan Kesehatan (BPJS / Asuransi)",
        "question_id": "Apakah Anda memiliki jaminan atau perlindungan layanan kesehatan (mis. BPJS Kesehatan, asuransi kesehatan)?",
        "help_id": "Pilih Ya jika memiliki jaminan kesehatan aktif.",
        "options": BINARY_CATEGORIES,
    },
    "NoDocbcCost": {
        "index": 12,
        "type": "binary",
        "min": 0,
        "max": 1,
        "group": "akses_layanan",
        "label_id": "Kendala Biaya Berobat ke Dokter (12 Bulan Terakhir)",
        "question_id": "Dalam 12 bulan terakhir, apakah pernah ada saat Anda perlu ke dokter tetapi tidak jadi karena alasan biaya?",
        "help_id": "Mengukur hambatan finansial terhadap akses layanan medis.",
        "options": BINARY_CATEGORIES,
    },
    "GenHlth": {
        "index": 13,
        "type": "ordinal",
        "min": 1,
        "max": 5,
        "group": "kondisi_kesehatan",
        "label_id": "Penilaian Kesehatan Umum Mandiri",
        "question_id": "Secara umum, bagaimana Anda menilai kondisi kesehatan Anda saat ini?",
        "help_id": "Skala 1 (Sangat Baik) sampai 5 (Buruk).",
        "options": GEN_HLTH_CATEGORIES,
    },
    "MentHlth": {
        "index": 14,
        "type": "continuous_int",
        "min": 0,
        "max": 30,
        "group": "kondisi_kesehatan",
        "label_id": "Hari Kesehatan Mental Kurang Baik (0–30 Hari)",
        "question_id": "Dalam 30 hari terakhir, berapa hari kesehatan mental Anda kurang baik (stres, cemas, atau tertekan)?",
        "help_id": "Isi angka 0 hingga 30 hari.",
        "unit": "hari",
    },
    "PhysHlth": {
        "index": 15,
        "type": "continuous_int",
        "min": 0,
        "max": 30,
        "group": "kondisi_kesehatan",
        "label_id": "Hari Kesehatan Fisik Kurang Baik (0–30 Hari)",
        "question_id": "Dalam 30 hari terakhir, berapa hari kesehatan fisik Anda kurang baik karena sakit atau cedera?",
        "help_id": "Isi angka 0 hingga 30 hari.",
        "unit": "hari",
    },
    "DiffWalk": {
        "index": 16,
        "type": "binary",
        "min": 0,
        "max": 1,
        "group": "kondisi_kesehatan",
        "label_id": "Kesulitan Serius Berjalan / Naik Tangga",
        "question_id": "Apakah Anda mengalami kesulitan serius saat berjalan atau menaiki tangga?",
        "help_id": "Pilih Ya jika memiliki keterbatasan mobilitas fisik yang bermakna.",
        "options": BINARY_CATEGORIES,
    },
    "Sex": {
        "index": 17,
        "type": "binary",
        "min": 0,
        "max": 1,
        "group": "fisik_demografi",
        "label_id": "Jenis Kelamin Biologis",
        "question_id": "Apa jenis kelamin Anda?",
        "help_id": "0 = Perempuan, 1 = Laki-laki (sesuai kode BRFSS 2015).",
        "options": SEX_CATEGORIES,
    },
    "Age": {
        "index": 18,
        "type": "ordinal",
        "min": 1,
        "max": 13,
        "group": "fisik_demografi",
        "label_id": "Kelompok Usia",
        "question_id": "Kelompok usia Anda (dikonversi otomatis dari usia tahun):",
        "help_id": "13 kelompok usia dewasa (≥ 18 tahun) sesuai standar BRFSS.",
        "options": AGE_CATEGORIES,
    },
    "Education": {
        "index": 19,
        "type": "ordinal",
        "min": 1,
        "max": 6,
        "group": "sosial_ekonomi",
        "label_id": "Tingkat Pendidikan Terakhir",
        "question_id": "Apa tingkat pendidikan formal terakhir yang Anda tempuh?",
        "help_id": "Skala 1–6 dipadankan dengan jenjang pendidikan Indonesia.",
        "options": EDUCATION_CATEGORIES,
    },
    "Income": {
        "index": 20,
        "type": "ordinal",
        "min": 1,
        "max": 8,
        "group": "sosial_ekonomi",
        "label_id": "Tingkat Pendapatan Rumah Tangga (Skala Relatif 1–8)",
        "question_id": "Pilih kelompok tingkat pendapatan rumah tangga tahunan (skala survei BRFSS 1–8):",
        "help_id": "Catatan: dataset asli menggunakan bracket USD AS; gunakan sebagai skala ekonomi relatif 1 (terendah) hingga 8 (tertinggi).",
        "options": INCOME_CATEGORIES,
    },
}


def age_years_to_category(age_years: int | float) -> int:
    """Convert age in years (≥18) to BRFSS 13-level Age category (1..13)."""
    age = int(age_years)
    if age < 25:
        return 1
    if age < 30:
        return 2
    if age < 35:
        return 3
    if age < 40:
        return 4
    if age < 45:
        return 5
    if age < 50:
        return 6
    if age < 55:
        return 7
    if age < 60:
        return 8
    if age < 65:
        return 9
    if age < 70:
        return 10
    if age < 75:
        return 11
    if age < 80:
        return 12
    return 13


def compute_bmi(weight_kg: float, height_cm: float) -> float:
    """Compute BMI from weight (kg) and height (cm), rounded to 1 decimal."""
    if height_cm <= 0 or weight_kg <= 0:
        raise ValueError("Tinggi dan berat badan harus lebih besar dari 0.")
    height_m = height_cm / 100.0
    return round(weight_kg / (height_m * height_m), 1)


def bmi_category_id(bmi: float) -> str:
    """Return Indonesian BMI classification string (WHO / Asia-Pacific context)."""
    if bmi < 18.5:
        return "berat badan kurang (underweight)"
    if bmi < 23.0:
        return "normal (Asia-Pasifik)"
    if bmi < 25.0:
        return "normal batas atas / berisiko (WHO normal)"
    if bmi < 30.0:
        return "berat badan berlebih (overweight)"
    if bmi < 35.0:
        return "obesitas kelas I"
    if bmi < 40.0:
        return "obesitas kelas II"
    return "obesitas kelas III (ekstrem)"


def format_feature_display(feature: str, raw_value: float | int) -> str:
    """Format a raw BRFSS feature value into human-readable Indonesian display string."""
    spec = FEATURE_SPECS.get(feature)
    if not spec:
        return str(raw_value)

    if feature == "BMI":
        val = round(float(raw_value), 1)
        val_str = str(val).replace(".", ",")
        return f"{val_str} kg/m² ({bmi_category_id(val)})"

    if feature in ("MentHlth", "PhysHlth"):
        days = int(round(float(raw_value)))
        return f"{days} hari"

    options = spec.get("options")
    if options:
        int_key = int(round(float(raw_value)))
        return options.get(int_key, str(int_key))

    return str(raw_value)


def get_feature_schema_dict() -> dict[str, Any]:
    """Return serializable feature schema dictionary."""
    return {
        "schema_version": "1.0.0",
        "target_column": TARGET_COLUMN,
        "feature_order": FEATURE_ORDER,
        "continuous_indices": CONTINUOUS_INDICES,
        "categorical_indices": CATEGORICAL_INDICES,
        "features": FEATURE_SPECS,
    }


def export_feature_schema(path: str | Path) -> None:
    path = Path(path)
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", encoding="utf-8") as f:
        json.dump(get_feature_schema_dict(), f, indent=2, ensure_ascii=False)


def export_label_map(path: str | Path) -> None:
    path = Path(path)
    path.parent.mkdir(parents=True, exist_ok=True)
    payload = {
        "code_to_label": {str(k): v for k, v in LABEL_MAP.items()},
        "code_to_display_id": {str(k): v for k, v in LABEL_DISPLAY_ID.items()},
        "label_to_code": LABEL_REVERSE_MAP,
    }
    with path.open("w", encoding="utf-8") as f:
        json.dump(payload, f, indent=2, ensure_ascii=False)

