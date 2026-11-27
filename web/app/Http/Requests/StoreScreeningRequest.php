<?php

namespace App\Http\Requests;

use App\Services\FeatureMapper;
use Illuminate\Foundation\Http\FormRequest;

class StoreScreeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        // Auto-convert age_years -> Age (1..13) if provided
        if ($this->filled('age_years') && !$this->filled('Age')) {
            $this->merge([
                'Age' => FeatureMapper::ageYearsToCategory((int) $this->input('age_years')),
            ]);
        } elseif ($this->filled('age_years')) {
            $this->merge([
                'Age' => FeatureMapper::ageYearsToCategory((int) $this->input('age_years')),
            ]);
        }

        // Auto-compute BMI from weight_kg & height_cm if provided
        if ($this->filled('weight_kg') && $this->filled('height_cm')) {
            $w = (float) $this->input('weight_kg');
            $h = (float) $this->input('height_cm');
            if ($w > 0 && $h > 0 && !$this->filled('BMI')) {
                $this->merge([
                    'BMI' => FeatureMapper::computeBmi($w, $h),
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'consent_data' => ['required', 'accepted'],
            'patient_label' => ['nullable', 'string', 'max:80'],
            'audience' => ['nullable', 'in:awam,tenaga_medis'],
            'age_years' => ['nullable', 'integer', 'min:18', 'max:120'],
            'weight_kg' => ['nullable', 'numeric', 'min:25', 'max:300'],
            'height_cm' => ['nullable', 'numeric', 'min:100', 'max:250'],

            // 21 BRFSS 2015 Indicators
            'HighBP' => ['required', 'integer', 'in:0,1'],
            'HighChol' => ['required', 'integer', 'in:0,1'],
            'CholCheck' => ['required', 'integer', 'in:0,1'],
            'BMI' => ['required', 'numeric', 'min:12', 'max:98'],
            'Smoker' => ['required', 'integer', 'in:0,1'],
            'Stroke' => ['required', 'integer', 'in:0,1'],
            'HeartDiseaseorAttack' => ['required', 'integer', 'in:0,1'],
            'PhysActivity' => ['required', 'integer', 'in:0,1'],
            'Fruits' => ['required', 'integer', 'in:0,1'],
            'Veggies' => ['required', 'integer', 'in:0,1'],
            'HvyAlcoholConsump' => ['required', 'integer', 'in:0,1'],
            'AnyHealthcare' => ['required', 'integer', 'in:0,1'],
            'NoDocbcCost' => ['required', 'integer', 'in:0,1'],
            'GenHlth' => ['required', 'integer', 'min:1', 'max:5'],
            'MentHlth' => ['required', 'integer', 'min:0', 'max:30'],
            'PhysHlth' => ['required', 'integer', 'min:0', 'max:30'],
            'DiffWalk' => ['required', 'integer', 'in:0,1'],
            'Sex' => ['required', 'integer', 'in:0,1'],
            'Age' => ['required', 'integer', 'min:1', 'max:13'],
            'Education' => ['required', 'integer', 'min:1', 'max:6'],
            'Income' => ['required', 'integer', 'min:1', 'max:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'consent_data.accepted' => 'Anda wajib menyetujui pernyataan penggunaan data dan pemahaman bahwa sistem ini bukan alat diagnosis medis.',
            'BMI.min' => 'Nilai BMI minimal adalah 12,0 kg/m².',
            'BMI.max' => 'Nilai BMI maksimal adalah 98,0 kg/m².',
            'age_years.min' => 'Skrining ini dikhususkan untuk responden dewasa berusia minimal 18 tahun (sesuai cakupan BRFSS).',
        ];
    }

    public function validatedFeatures(): array
    {
        $data = $this->validated();
        $features = [];
        foreach (FeatureMapper::FEATURE_ORDER as $fname) {
            $features[$fname] = $fname === 'BMI'
                ? round((float) $data[$fname], 1)
                : (int) $data[$fname];
        }
        return $features;
    }
}

