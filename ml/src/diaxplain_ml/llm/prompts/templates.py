"""System & User Prompt templates (Appendix B, v1.0 & v1.1)."""

from __future__ import annotations

import json
from typing import Any

from diaxplain_ml.llm.schema import StructuredInterpretationInput

SYSTEM_PROMPT_P1_0 = """Anda adalah asisten penerjemah hasil model machine learning untuk sistem skrining status diabetes bernama DiaXplain.
PERAN: menjelaskan hasil model dengan bahasa Indonesia yang sederhana, empatik, dan tidak menghakimi.
ATURAN KETAT:
1. Gunakan HANYA data pada JSON masukan. Dilarang menambah fitur, angka, atau fakta di luar JSON.
2. Label status dan probabilitas berasal dari model; jangan mengubah, meragukan, atau menghitung ulang.
3. Jangan mendiagnosis. Gunakan frasa "model memperkirakan", "profil Anda mirip dengan kelompok…".
   Dilarang keras menggunakan frasa: "Anda menderita", "Anda pasti", "didiagnosis", "positif menderita".
4. Faktor dengan arah "naik" wajib dimasukkan ke `faktor_yang_meningkatkan` (meningkatkan kecenderungan menuju kelas prediksi); faktor dengan arah "turun" wajib dimasukkan ke `faktor_yang_menurunkan` (menurunkan kecenderungan terhadap kelas prediksi).
   Jelaskan sebagai pola statistik populasi survei, bukan hubungan sebab-akibat medis langsung.
5. Dilarang menyebut nama obat medis, merek obat, dosis (mis. metformin, insulin, glimepiride, mg/hari), atau terapi klinis. Saran terbatas pada gaya hidup sehat umum (aktivitas fisik, pola makan seimbang, pengelolaan berat badan, istirahat, dan pemeriksaan laboratorium rutin).
6. Selalu sarankan konfirmasi ke dokter atau tenaga kesehatan melalui pemeriksaan laboratorium (seperti gula darah puasa / HbA1c).
7. Keluarkan HANYA objek JSON valid sesuai skema keluaran; tanpa teks pembuka/penutup dan tanpa blok kode markdown."""


OUTPUT_SCHEMA_DESCRIPTION = {
    "ringkasan": "Paragraf 2-3 kalimat yang menyebutkan label hasil prediksi model (No Diabetes / Prediabetes / Diabetes), probabilitasnya, dan tingkat keyakinan model dengan bahasa non-diagnostik.",
    "faktor_yang_meningkatkan": [
        {
            "feature": "Nama kode fitur persis dari shap_top yang memiliki direction='naik'",
            "penjelasan": "Penjelasan 1-2 kalimat dalam bahasa Indonesia tentang nilai fitur pengguna dan bagaimana faktor tersebut meningkatkan kecenderungan ke kelas prediksi.",
        }
    ],
    "faktor_yang_menurunkan": [
        {
            "feature": "Nama kode fitur persis dari shap_top yang memiliki direction='turun'",
            "penjelasan": "Penjelasan 1-2 kalimat dalam bahasa Indonesia tentang nilai fitur pengguna dan bagaimana faktor tersebut menurunkan kecenderungan terhadap kelas prediksi.",
        }
    ],
    "saran_umum": [
        "Saran gaya hidup sehat umum 1 (tanpa nama obat/dosis)",
        "Saran gaya hidup sehat umum 2",
        "Saran gaya hidup sehat umum 3",
    ],
    "kapan_ke_tenaga_kesehatan": "Anjuran jelas kapan perlu berkonsultasi ke dokter/fasilitas kesehatan untuk pemeriksaan laboratorium konfirmasi.",
}


def build_prompts(
    payload: StructuredInterpretationInput,
    prompt_version: str = "p1.0",
    retry_errors: list[str] | None = None,
) -> tuple[str, str]:
    """Construct `(system_prompt, user_prompt)` for the LLM provider."""
    clean_dict: dict[str, Any] = payload.model_dump(exclude={"llm_config"})
    audience_desc = (
        "Tenaga Medis / Klinisi (gunakan istilah kesehatan yang presisi namun tetap ringkas)"
        if payload.audience == "tenaga_medis"
        else "Masyarakat Umum / Awam (gunakan bahasa sehari-hari yang hangat, mudah dipahami, dan tidak menimbulkan kepanikan)"
    )

    user_prompt = (
        f"Berikut hasil terstruktur dari model XGBoost + SHAP:\n"
        f"{json.dumps(clean_dict, ensure_ascii=False, indent=2)}\n\n"
        f"Audiens sasaran: {audience_desc}.\n"
        f"Susun narasi penjelasan dalam format JSON murni sesuai skema keluaran berikut:\n"
        f"{json.dumps(OUTPUT_SCHEMA_DESCRIPTION, ensure_ascii=False, indent=2)}"
    )

    if retry_errors:
        err_text = "; ".join(retry_errors)
        user_prompt += (
            f"\n\nPERINGATAN PERBAIKAN (Percobaan sebelumnya gagal validasi fakta): {err_text}. "
            "Harap perbaiki keluaran JSON agar mematuhi seluruh aturan di atas tanpa pelanggaran!"
        )

    return SYSTEM_PROMPT_P1_0, user_prompt

