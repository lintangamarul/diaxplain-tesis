"""Deterministic Template Narrative Generator (Layer-5 Fallback & Baseline Comparator)."""

from __future__ import annotations

from diaxplain_ml.features.codebook import LABEL_DISPLAY_ID
from diaxplain_ml.llm.schema import (
    FactorExplanationItem,
    NarrativeOutputSchema,
    StructuredInterpretationInput,
)


def generate_fallback_narrative(source: StructuredInterpretationInput) -> NarrativeOutputSchema:
    """
    Generate a 100% factually aligned, empathetic Indonesian narrative directly from
    `StructuredInterpretationInput` when the external LLM API key is not yet set or fails validation.
    """
    pred = source.prediction
    label_en = pred.label
    label_id = LABEL_DISPLAY_ID.get(pred.label_code, label_en)
    prob_pct = round(pred.probabilities.get(label_en, 0.0) * 100.0, 1)
    prob_str = str(prob_pct).replace(".", ",")

    conf_map = {
        "tinggi": "tingkat keyakinan model yang tinggi",
        "sedang": "tingkat keyakinan model yang moderat (sedang)",
        "rendah": "tingkat keyakinan model yang relatif rendah (antar-kelas berdekatan)",
    }
    conf_phrase = conf_map.get(pred.confidence_note, "tingkat keyakinan terukur")

    ringkasan = (
        f"Berdasarkan analisis 21 indikator kesehatan yang Anda masukkan, model memperkirakan bahwa profil Anda "
        f"paling mirip dengan kelompok {label_id} ({label_en}) dengan probabilitas sebesar {prob_str}% "
        f"({conf_phrase}). Hasil ini merupakan estimasi pola statistik dari survei kesehatan populasi dan "
        f"bukan merupakan diagnosis medis."
    )

    up_items: list[FactorExplanationItem] = []
    down_items: list[FactorExplanationItem] = []

    for item in source.shap_top:
        shap_str = f"{abs(item.shap):.3f}".replace(".", ",")
        if item.direction == "naik":
            up_items.append(
                FactorExplanationItem(
                    feature=item.feature,
                    penjelasan=(
                        f"Indikator {item.label_id} dengan nilai '{item.display}' berkontribusi positif "
                        f"(nilai SHAP +{shap_str}) dalam meningkatkan kecenderungan prediksi model menuju kelompok {label_id}."
                    ),
                )
            )
        else:
            down_items.append(
                FactorExplanationItem(
                    feature=item.feature,
                    penjelasan=(
                        f"Indikator {item.label_id} dengan nilai '{item.display}' berkontribusi menahan/menurunkan "
                        f"kecenderungan terhadap kelompok {label_id} (nilai SHAP -{shap_str})."
                    ),
                )
            )

    if pred.label_code == 0:
        saran = [
            "Pertahankan pola makan seimbang dengan porsi sayur dan buah segar setiap hari serta batasi konsumsi gula tambahan.",
            "Lakukan aktivitas fisik atau olahraga aerobik intensitas sedang minimal 150 menit per minggu secara teratur.",
            "Jaga berat badan ideal dan lakukan pemeriksaan kesehatan berkala (cek tekanan darah, kolesterol, dan gula darah tahunan).",
        ]
        kapan = (
            "Meskipun model memperkirakan profil Anda berada pada kelompok Tidak Diabetes (No Diabetes), tetap disarankan "
            "melakukan pemeriksaan kesehatan rutin minimal 1 kali setahun di fasilitas kesehatan, atau segera berkonsultasi "
            "bila muncul gejala seperti sering haus, sering buang air kecil, atau penurunan berat badan tanpa sebab jelas."
        )
    elif pred.label_code == 1:
        saran = [
            "Tingkatkan aktivitas fisik terstruktur (seperti jalan cepat, bersepeda, atau berenang) minimal 30 menit sehari selama 5 hari per minggu.",
            "Kurangi asupan karbohidrat sederhana serta minuman manis, dan tingkatkan asupan serat dari sayuran hijau serta buah utuh.",
            "Kelola berat badan secara bertahap dan pantau tekanan darah serta kadar kolesterol secara berkala.",
        ]
        kapan = (
            "Karena profil Anda menunjukkan kemiripan dengan kelompok Prediabetes, sangat disarankan untuk menjadwalkan "
            "konsultasi dengan dokter atau puskesmas/klinik guna melakukan pemeriksaan laboratorium konfirmasi seperti "
            "Gula Darah Puasa (GDP) atau HbA1c."
        )
    else:
        saran = [
            "Terapkan pengaturan pola makan terukur dengan membatasi gula sederhana dan lemak jenuh serta memperbanyak serat pangan.",
            "Lakukan aktivitas fisik yang sesuai dengan kebugaran tubuh secara konsisten dan hindari kebiasaan merokok.",
            "Catat faktor risiko utama Anda (seperti tekanan darah, indeks massa tubuh, dan keluhan fisik) untuk didiskusikan bersama dokter.",
        ]
        kapan = (
            "Karena model mengindikasikan kemiripan profil dengan kelompok Diabetes, segera kunjungi dokter atau fasilitas "
            "layanan kesehatan terdekat untuk menjalani pemeriksaan laboratorium diagnostik (Gula Darah Puasa, TTGO, atau HbA1c) "
            "dan memperoleh evaluasi medis yang tepat."
        )

    return NarrativeOutputSchema(
        ringkasan=ringkasan,
        faktor_yang_meningkatkan=up_items,
        faktor_yang_menurunkan=down_items,
        saran_umum=saran,
        kapan_ke_tenaga_kesehatan=kapan,
    )

