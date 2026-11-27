<?php

namespace App\Services;

class FeatureMapper
{
    public const FEATURE_ORDER = [
        'HighBP',
        'HighChol',
        'CholCheck',
        'BMI',
        'Smoker',
        'Stroke',
        'HeartDiseaseorAttack',
        'PhysActivity',
        'Fruits',
        'Veggies',
        'HvyAlcoholConsump',
        'AnyHealthcare',
        'NoDocbcCost',
        'GenHlth',
        'MentHlth',
        'PhysHlth',
        'DiffWalk',
        'Sex',
        'Age',
        'Education',
        'Income',
    ];

    public static function ageYearsToCategory(int|float $ageYears): int
    {
        $age = (int) round($ageYears);
        if ($age < 25) return 1;
        if ($age < 30) return 2;
        if ($age < 35) return 3;
        if ($age < 40) return 4;
        if ($age < 45) return 5;
        if ($age < 50) return 6;
        if ($age < 55) return 7;
        if ($age < 60) return 8;
        if ($age < 65) return 9;
        if ($age < 70) return 10;
        if ($age < 75) return 11;
        if ($age < 80) return 12;
        return 13;
    }

    public static function computeBmi(float $weightKg, float $heightCm): float
    {
        if ($weightKg <= 0 || $heightCm <= 0) {
            throw new \InvalidArgumentException('Tinggi dan berat badan harus lebih besar dari 0.');
        }
        $heightM = $heightCm / 100.0;
        return round($weightKg / ($heightM * $heightM), 1);
    }

    public static function bmiCategoryLabel(float $bmi): string
    {
        if ($bmi < 18.5) return 'Berat Badan Kurang (Underweight)';
        if ($bmi < 23.0) return 'Normal (Standar Asia-Pasifik)';
        if ($bmi < 25.0) return 'Normal Batas Atas / Berisiko (WHO Normal)';
        if ($bmi < 30.0) return 'Berat Badan Berlebih (Overweight)';
        if ($bmi < 35.0) return 'Obesitas Kelas I';
        if ($bmi < 40.0) return 'Obesitas Kelas II';
        return 'Obesitas Kelas III (Ekstrem)';
    }

    public static function getSchema(): array
    {
        $artifactsDir = base_path(config('services.diaxplain_ml.artifacts_path', '../ml/artifacts'));
        $schemaPath = $artifactsDir . '/model_v1/feature_schema.json';
        if (is_file($schemaPath)) {
            $decoded = json_decode((string) file_get_contents($schemaPath), true);
            if (is_array($decoded) && isset($decoded['features'])) {
                return $decoded;
            }
        }

        return [
            'schema_version' => '1.0.0',
            'feature_order' => self::FEATURE_ORDER,
            'features' => self::defaultSpecs(),
        ];
    }

    public static function defaultSpecs(): array
    {
        $binary = ['0' => 'Tidak', '1' => 'Ya'];
        return [
            'HighBP' => ['label_id' => 'Tekanan Darah Tinggi (Hipertensi)', 'type' => 'binary', 'min' => 0, 'max' => 1, 'options' => $binary],
            'HighChol' => ['label_id' => 'Kolesterol Tinggi', 'type' => 'binary', 'min' => 0, 'max' => 1, 'options' => $binary],
            'CholCheck' => ['label_id' => 'Pemeriksaan Kolesterol (5 Tahun Terakhir)', 'type' => 'binary', 'min' => 0, 'max' => 1, 'options' => $binary],
            'BMI' => ['label_id' => 'Indeks Massa Tubuh (BMI)', 'type' => 'continuous', 'min' => 12, 'max' => 98],
            'Smoker' => ['label_id' => 'Riwayat Merokok (≥ 100 Batang Seumur Hidup)', 'type' => 'binary', 'min' => 0, 'max' => 1, 'options' => $binary],
            'Stroke' => ['label_id' => 'Riwayat Stroke', 'type' => 'binary', 'min' => 0, 'max' => 1, 'options' => $binary],
            'HeartDiseaseorAttack' => ['label_id' => 'Penyakit Jantung Koroner / Serangan Jantung', 'type' => 'binary', 'min' => 0, 'max' => 1, 'options' => $binary],
            'PhysActivity' => ['label_id' => 'Aktivitas Fisik / Olahraga (30 Hari Terakhir)', 'type' => 'binary', 'min' => 0, 'max' => 1, 'options' => $binary],
            'Fruits' => ['label_id' => 'Konsumsi Buah Harian (≥ 1 Kali/Hari)', 'type' => 'binary', 'min' => 0, 'max' => 1, 'options' => $binary],
            'Veggies' => ['label_id' => 'Konsumsi Sayur Harian (≥ 1 Kali/Hari)', 'type' => 'binary', 'min' => 0, 'max' => 1, 'options' => $binary],
            'HvyAlcoholConsump' => ['label_id' => 'Konsumsi Alkohol Berat', 'type' => 'binary', 'min' => 0, 'max' => 1, 'options' => $binary],
            'AnyHealthcare' => ['label_id' => 'Kepemilikan Jaminan Kesehatan (BPJS / Asuransi)', 'type' => 'binary', 'min' => 0, 'max' => 1, 'options' => $binary],
            'NoDocbcCost' => ['label_id' => 'Kendala Biaya Berobat ke Dokter (12 Bulan Terakhir)', 'type' => 'binary', 'min' => 0, 'max' => 1, 'options' => $binary],
            'GenHlth' => [
                'label_id' => 'Penilaian Kesehatan Umum Mandiri',
                'type' => 'ordinal',
                'min' => 1,
                'max' => 5,
                'options' => [
                    '1' => 'Sangat Baik (1)',
                    '2' => 'Baik Sekali (2)',
                    '3' => 'Baik / Cukup (3)',
                    '4' => 'Kurang Baik (4)',
                    '5' => 'Buruk (5)',
                ],
            ],
            'MentHlth' => ['label_id' => 'Hari Kesehatan Mental Kurang Baik (0–30 Hari)', 'type' => 'continuous_int', 'min' => 0, 'max' => 30],
            'PhysHlth' => ['label_id' => 'Hari Kesehatan Fisik Kurang Baik (0–30 Hari)', 'type' => 'continuous_int', 'min' => 0, 'max' => 30],
            'DiffWalk' => ['label_id' => 'Kesulitan Serius Berjalan / Naik Tangga', 'type' => 'binary', 'min' => 0, 'max' => 1, 'options' => $binary],
            'Sex' => ['label_id' => 'Jenis Kelamin Biologis', 'type' => 'binary', 'min' => 0, 'max' => 1, 'options' => ['0' => 'Perempuan', '1' => 'Laki-laki']],
            'Age' => [
                'label_id' => 'Kelompok Usia',
                'type' => 'ordinal',
                'min' => 1,
                'max' => 13,
                'options' => [
                    '1' => '18–24 tahun', '2' => '25–29 tahun', '3' => '30–34 tahun',
                    '4' => '35–39 tahun', '5' => '40–44 tahun', '6' => '45–49 tahun',
                    '7' => '50–54 tahun', '8' => '55–59 tahun', '9' => '60–64 tahun',
                    '10' => '65–69 tahun', '11' => '70–74 tahun', '12' => '75–79 tahun',
                    '13' => '≥ 80 tahun',
                ],
            ],
            'Education' => [
                'label_id' => 'Tingkat Pendidikan Terakhir',
                'type' => 'ordinal',
                'min' => 1,
                'max' => 6,
                'options' => [
                    '1' => 'Tidak sekolah / TK',
                    '2' => 'SD / Sederajat (Kelas 1–8)',
                    '3' => 'SMP / SMA belum tamat (Kelas 9–11)',
                    '4' => 'Tamat SMA / SMK / Sederajat',
                    '5' => 'Diploma / Kuliah 1–3 tahun',
                    '6' => 'Sarjana (S1/D4) atau lebih tinggi',
                ],
            ],
            'Income' => [
                'label_id' => 'Tingkat Pendapatan Rumah Tangga (Skala Relatif 1–8)',
                'type' => 'ordinal',
                'min' => 1,
                'max' => 8,
                'options' => [
                    '1' => 'Tingkat 1 (< $10.000 / terendah)',
                    '2' => 'Tingkat 2 ($10.000–$15.000)',
                    '3' => 'Tingkat 3 ($15.000–$20.000)',
                    '4' => 'Tingkat 4 ($20.000–$25.000)',
                    '5' => 'Tingkat 5 ($25.000–$35.000)',
                    '6' => 'Tingkat 6 ($35.000–$50.000)',
                    '7' => 'Tingkat 7 ($50.000–$75.000)',
                    '8' => 'Tingkat 8 (≥ $75.000 / tertinggi)',
                ],
            ],
        ];
    }

    public static function formatDisplay(string $feature, float|int $value): string
    {
        $specs = self::getSchema()['features'] ?? self::defaultSpecs();
        $spec = $specs[$feature] ?? null;
        if (!$spec) {
            return (string) $value;
        }
        if ($feature === 'BMI') {
            $v = round((float) $value, 1);
            return str_replace('.', ',', (string) $v) . ' kg/m² (' . self::bmiCategoryLabel($v) . ')';
        }
        if (in_array($feature, ['MentHlth', 'PhysHlth'], true)) {
            return ((int) round((float) $value)) . ' hari';
        }
        $options = $spec['options'] ?? [];
        $key = (string) ((int) round((float) $value));
        return $options[$key] ?? $key;
    }

    public static function buildInputSummary(array $features): array
    {
        $specs = self::getSchema()['features'] ?? self::defaultSpecs();
        $summary = [];
        foreach (self::FEATURE_ORDER as $fname) {
            $spec = $specs[$fname] ?? ['label_id' => $fname];
            $raw = $features[$fname] ?? 0;
            $val = $fname === 'BMI' ? round((float) $raw, 1) : (int) round((float) $raw);
            $summary[] = [
                'feature' => $fname,
                'label_id' => $spec['label_id'] ?? $fname,
                'value' => $val,
                'display' => self::formatDisplay($fname, $val),
            ];
        }
        return $summary;
    }

    public static function systemDisclaimerText(): string
    {
        return 'PERINGATAN MEDIS & ETIKA: Hasil analisis DiaXplain ini dihasilkan oleh model Machine Learning (XGBoost + SHAP) sebagai alat bantu skrining awal berbasis indikator survei populasi (BRFSS CDC 2015), dan BUKAN merupakan diagnosis medis definitif. Hasil ini tidak menggantikan konsultasi dokter maupun pemeriksaan laboratorium klinis (seperti Gula Darah Puasa, TTGO, atau HbA1c).';
    }
}

