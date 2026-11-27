<?php

namespace Database\Seeders;

use App\Models\NarrativeRating;
use App\Models\Setting;
use App\Models\User;
use App\Services\ExperimentImporter;
use App\Services\ScreeningService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@diaxplain.id'],
            [
                'name' => 'Lintang Amarul Fatah (Admin & Peneliti)',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'consent_at' => now(),
            ]
        );

        $nakes1 = User::updateOrCreate(
            ['email' => 'nakes@diaxplain.id'],
            [
                'name' => 'dr. Ratna Kusuma, Sp.PD (Tenaga Medis)',
                'password' => Hash::make('password'),
                'role' => 'nakes',
                'is_active' => true,
                'consent_at' => now(),
            ]
        );

        $nakes2 = User::updateOrCreate(
            ['email' => 'nakes2@diaxplain.id'],
            [
                'name' => 'dr. Hendra Wijaya, M.Kes (Penilai Klinis 2)',
                'password' => Hash::make('password'),
                'role' => 'nakes',
                'is_active' => true,
                'consent_at' => now(),
            ]
        );

        $nakes3 = User::updateOrCreate(
            ['email' => 'nakes3@diaxplain.id'],
            [
                'name' => 'Ns. Siti Aminah, S.Kep (Penilai Klinis 3)',
                'password' => Hash::make('password'),
                'role' => 'nakes',
                'is_active' => true,
                'consent_at' => now(),
            ]
        );

        $pengguna = User::updateOrCreate(
            ['email' => 'pengguna@diaxplain.id'],
            [
                'name' => 'Budi Santoso (Pengguna Umum)',
                'password' => Hash::make('password'),
                'role' => 'pengguna',
                'is_active' => true,
                'consent_at' => now(),
            ]
        );

        // Default LLM & SHAP Settings
        Setting::setValue('llm_provider', 'openai_compatible');
        Setting::setValue('llm_base_url', 'https://api.openai.com/v1');
        Setting::setValue('llm_model', 'gpt-4o-mini');
        Setting::setValue('llm_temperature', '0.2');
        Setting::setValue('llm_prompt_version', 'p1.0');
        Setting::setValue('shap_top_k', '6');
        if (Setting::getValue('llm_api_key') === null) {
            Setting::setValue('llm_api_key', '');
        }

        // Import real ML artifacts (E0-E6, Weakness Report, Selection Report, SHAP Global, LLM Eval)
        app(ExperimentImporter::class)->importFromArtifacts();

        // Create 3 realistic sample screenings if none exist yet
        if (\App\Models\Screening::count() === 0) {
            $screeningService = app(ScreeningService::class);

            // Case 1: Low-risk profile (No Diabetes)
            $scr1 = $screeningService->createAndAssess(
                $pengguna,
                [
                    'HighBP' => 0, 'HighChol' => 0, 'CholCheck' => 1, 'BMI' => 22.1,
                    'Smoker' => 0, 'Stroke' => 0, 'HeartDiseaseorAttack' => 0, 'PhysActivity' => 1,
                    'Fruits' => 1, 'Veggies' => 1, 'HvyAlcoholConsump' => 0, 'AnyHealthcare' => 1,
                    'NoDocbcCost' => 0, 'GenHlth' => 1, 'MentHlth' => 0, 'PhysHlth' => 0,
                    'DiffWalk' => 0, 'Sex' => 1, 'Age' => 3, 'Education' => 6, 'Income' => 7,
                ],
                ['age_years' => 32, 'weight_kg' => 64, 'height_cm' => 170],
                null,
                'awam'
            );

            // Case 2: Borderline metabolic profile (Prediabetes)
            $scr2 = $screeningService->createAndAssess(
                $nakes1,
                [
                    'HighBP' => 1, 'HighChol' => 1, 'CholCheck' => 1, 'BMI' => 30.8,
                    'Smoker' => 1, 'Stroke' => 0, 'HeartDiseaseorAttack' => 0, 'PhysActivity' => 0,
                    'Fruits' => 0, 'Veggies' => 1, 'HvyAlcoholConsump' => 0, 'AnyHealthcare' => 1,
                    'NoDocbcCost' => 0, 'GenHlth' => 3, 'MentHlth' => 4, 'PhysHlth' => 5,
                    'DiffWalk' => 0, 'Sex' => 0, 'Age' => 8, 'Education' => 5, 'Income' => 5,
                ],
                ['age_years' => 57, 'weight_kg' => 77, 'height_cm' => 158],
                'PASIEN-KLINIK-014',
                'tenaga_medis'
            );

            // Case 3: High cardiometabolic risk profile (Diabetes)
            $scr3 = $screeningService->createAndAssess(
                $nakes1,
                [
                    'HighBP' => 1, 'HighChol' => 1, 'CholCheck' => 1, 'BMI' => 36.2,
                    'Smoker' => 1, 'Stroke' => 0, 'HeartDiseaseorAttack' => 1, 'PhysActivity' => 0,
                    'Fruits' => 0, 'Veggies' => 0, 'HvyAlcoholConsump' => 0, 'AnyHealthcare' => 1,
                    'NoDocbcCost' => 0, 'GenHlth' => 4, 'MentHlth' => 6, 'PhysHlth' => 14,
                    'DiffWalk' => 1, 'Sex' => 1, 'Age' => 10, 'Education' => 4, 'Income' => 4,
                ],
                ['age_years' => 67, 'weight_kg' => 98, 'height_cm' => 164],
                'PASIEN-KLINIK-029',
                'tenaga_medis'
            );

            // Seed multi-rater expert evaluations (RM6)
            foreach ([$scr1, $scr2, $scr3] as $idx => $scr) {
                $interp = $scr->latestInterpretation;
                if (!$interp) {
                    continue;
                }
                foreach ([$nakes1, $nakes2, $nakes3] as $rIdx => $rater) {
                    NarrativeRating::updateOrCreate(
                        ['interpretation_id' => $interp->id, 'rater_id' => $rater->id],
                        [
                            'akurasi' => 5,
                            'kejelasan' => ($idx + $rIdx) % 2 === 0 ? 5 : 4,
                            'kegunaan' => 5,
                            'keamanan' => 5,
                            'komentar' => 'Penjelasan arah kontribusi fitur SHAP sangat konsisten dengan hasil model XGBoost dan tidak mengandung klaim diagnosis medis.',
                        ]
                    );
                }
            }
        }
    }
}
