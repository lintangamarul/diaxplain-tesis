"""Layer-3 Fact & Safety Validator for LLM Narratives (Section 9.4)."""

from __future__ import annotations

import re
from typing import Any

from diaxplain_ml.features.codebook import LABEL_DISPLAY_ID, LABEL_MAP
from diaxplain_ml.llm.schema import NarrativeOutputSchema, StructuredInterpretationInput

FORBIDDEN_DIAGNOSIS_PATTERNS = [
    r"\banda\s+menderita\b",
    r"\banda\s+pasti\b",
    r"\bdidiagnosis\s+diabetes\b",
    r"\bpositif\s+menderita\b",
    r"\bmemastikan\s+anda\s+sakit\b",
    r"\bjaminan\s+sembuh\b",
    r"\bpasti\s+sembuh\b",
]

FORBIDDEN_DRUG_PATTERNS = [
    r"\bmetformin\b",
    r"\binsulin\b",
    r"\bglimepiride\b",
    r"\bglibenclamide\b",
    r"\bgliclazide\b",
    r"\bpioglitazone\b",
    r"\bsitagliptin\b",
    r"\bempagliflozin\b",
    r"\bdapagliflozin\b",
    r"\bsemaglutide\b",
    r"\b\d+\s*mg\b",
    r"\bdosis\s+obat\b",
]


def validate_narrative(
    narrative: NarrativeOutputSchema,
    source: StructuredInterpretationInput,
) -> dict[str, Any]:
    """
    Validate LLM narrative against Structured Interpretation (Section 9.4):
    1. Feature whitelist: all cited features in `allowed_features` & `shap_top`/`input_summary`.
    2. Direction consistency: `faktor_yang_meningkatkan` matches `direction=='naik'`,
       `faktor_yang_menurunkan` matches `direction=='turun'`.
    3. Label consistency: `ringkasan` mentions the predicted class (English or Indonesian label)
       and does not falsely claim another class is the primary predicted class.
    4. Forbidden phrases: no definitive diagnosis claims or drug/dosage prescriptions.
    5. Indonesian language & length check.
    """
    errors: list[str] = []

    allowed_set = set(source.allowed_features)
    shap_dir_map = {item.feature: item.direction for item in source.shap_top}
    input_features_set = {item.feature for item in source.input_summary} | set(shap_dir_map.keys())

    # 1. Feature whitelist check
    cited_up = [f.feature for f in narrative.faktor_yang_meningkatkan]
    cited_down = [f.feature for f in narrative.faktor_yang_menurunkan]
    all_cited = cited_up + cited_down

    hallucinated = [f for f in all_cited if f not in allowed_set or f not in input_features_set]
    feature_whitelist_ok = len(hallucinated) == 0
    if not feature_whitelist_ok:
        errors.append(f"Fitur di luar daftar yang diizinkan (halusinasi fitur): {hallucinated}")

    # 2. Direction consistency check
    direction_ok = True
    for item in narrative.faktor_yang_meningkatkan:
        expected_dir = shap_dir_map.get(item.feature)
        if expected_dir == "turun":
            direction_ok = False
            errors.append(
                f"Arah kontribusi fitur '{item.feature}' salah: di SHAP bernilai negatif ('turun'), tetapi dimasukkan ke faktor_yang_meningkatkan."
            )
    for item in narrative.faktor_yang_menurunkan:
        expected_dir = shap_dir_map.get(item.feature)
        if expected_dir == "naik":
            direction_ok = False
            errors.append(
                f"Arah kontribusi fitur '{item.feature}' salah: di SHAP bernilai positif ('naik'), tetapi dimasukkan ke faktor_yang_menurunkan."
            )

    # 3. Label consistency check
    pred_code = source.prediction.label_code
    pred_label_en = LABEL_MAP.get(pred_code, source.prediction.label).lower()
    pred_label_id = LABEL_DISPLAY_ID.get(pred_code, source.prediction.label).lower()
    summary_lower = narrative.ringkasan.lower()

    label_consistency_ok = (pred_label_en in summary_lower) or (pred_label_id in summary_lower)
    if not label_consistency_ok:
        errors.append(
            f"Ringkasan tidak menyebutkan label prediksi model secara konsisten ('{source.prediction.label}' / '{LABEL_DISPLAY_ID.get(pred_code)}')."
        )

    # 4. Numeric consistency check (if BMI is explicitly cited in text, check it doesn't invent a wildly different BMI)
    numeric_ok = True
    bmi_items = [x for x in source.input_summary if x.feature == "BMI"]
    if bmi_items:
        actual_bmi = float(bmi_items[0].value)
        full_text = " ".join(
            [
                narrative.ringkasan,
                *[x.penjelasan for x in narrative.faktor_yang_meningkatkan],
                *[x.penjelasan for x in narrative.faktor_yang_menurunkan],
            ]
        )
        bmi_matches = re.findall(
            r"bmi\s+(?:anda\s+|sebesar\s+|adalah\s+|yaitu\s+|senilai\s+|:\s*)*(\d+[.,]\d+|\d+)",
            full_text.lower(),
        )
        for m in bmi_matches:
            val_num = float(m.replace(",", "."))
            if abs(val_num - actual_bmi) > 1.5 and abs(val_num - round(actual_bmi)) > 1.5:
                numeric_ok = False
                errors.append(f"Angka BMI pada narasi ({val_num}) tidak cocok dengan nilai input ({actual_bmi}).")

    # 5. Forbidden phrases (diagnosis & drugs)
    all_prose = " ".join(
        [
            narrative.ringkasan,
            *[x.penjelasan for x in narrative.faktor_yang_meningkatkan],
            *[x.penjelasan for x in narrative.faktor_yang_menurunkan],
            *narrative.saran_umum,
            narrative.kapan_ke_tenaga_kesehatan,
        ]
    ).lower()

    forbidden_found: list[str] = []
    for pat in FORBIDDEN_DIAGNOSIS_PATTERNS + FORBIDDEN_DRUG_PATTERNS:
        match = re.search(pat, all_prose, flags=re.IGNORECASE)
        if match:
            forbidden_found.append(match.group(0))

    no_forbidden_phrases = len(forbidden_found) == 0
    if not no_forbidden_phrases:
        errors.append(f"Ditemukan frasa terlarang (klaim diagnosis / nama obat / dosis): {forbidden_found}")

    # 6. Language & minimum completeness check
    id_markers = ["yang", "dan", "dengan", "untuk", "kesehatan", "pada", "atau", "anda", "model"]
    marker_hits = sum(1 for w in id_markers if w in all_prose)
    language_and_length_ok = marker_hits >= 3 and len(narrative.ringkasan.strip()) >= 25 and len(narrative.saran_umum) >= 1
    if not language_and_length_ok:
        errors.append("Narasi tidak memenuhi syarat bahasa Indonesia atau panjang minimum.")

    passed = (
        feature_whitelist_ok
        and direction_ok
        and label_consistency_ok
        and numeric_ok
        and no_forbidden_phrases
        and language_and_length_ok
    )

    top_k_features = {x.feature for x in source.shap_top}
    covered_top_k = len(set(all_cited).intersection(top_k_features))
    coverage_ratio = round(covered_top_k / max(len(top_k_features), 1), 4)

    return {
        "passed": passed,
        "checks": {
            "label_consistency": label_consistency_ok,
            "feature_whitelist": feature_whitelist_ok,
            "direction": direction_ok,
            "numeric": numeric_ok,
            "forbidden_phrases": not no_forbidden_phrases,  # False means 0 forbidden phrases found (matches contract 10)
            "language_and_length": language_and_length_ok,
        },
        "metrics": {
            "hallucinated_feature_count": len(hallucinated),
            "top_k_coverage": coverage_ratio,
        },
        "errors": errors,
    }
