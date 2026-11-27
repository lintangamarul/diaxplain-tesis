<?php

namespace App\Services;

use App\Jobs\GenerateInterpretationJob;
use App\Models\AuditLog;
use App\Models\Interpretation;
use App\Models\ModelVersion;
use App\Models\Prediction;
use App\Models\Screening;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ScreeningService
{
    public function __construct(
        private MlClient $mlClient
    ) {}

    public function createAndAssess(
        User $user,
        array $features,
        array $physicalMeta = [],
        ?string $patientLabel = null,
        string $audience = 'awam'
    ): Screening {
        return DB::transaction(function () use ($user, $features, $physicalMeta, $patientLabel, $audience) {
            $screeningId = (string) Str::uuid();
            $caseId = 'scr_' . substr(str_replace('-', '', $screeningId), 0, 12);
            $topK = (int) Setting::getValue('shap_top_k', 6);

            $screening = Screening::create([
                'id' => $screeningId,
                'user_id' => $user->id,
                'patient_label' => $patientLabel ? trim($patientLabel) : null,
                'audience' => $audience,
                'input' => $features,
                'physical_meta' => $physicalMeta,
                'consent_data' => true,
                'source' => 'web',
            ]);

            if (!$user->consent_at) {
                $user->update(['consent_at' => now()]);
            }

            $activeModel = ModelVersion::where('is_active', true)->first();
            $assessResult = $this->mlClient->assess($caseId, $features, $topK);

            $predData = $assessResult['prediction'] ?? [];
            $shapData = $assessResult['shap'] ?? [];

            $prediction = Prediction::create([
                'screening_id' => $screening->id,
                'model_version_id' => $activeModel?->id,
                'model_version_tag' => $assessResult['model_version'] ?? ($activeModel?->version_tag ?? 'xgb-v1.0.0'),
                'label' => $predData['label'] ?? 'No Diabetes',
                'label_id' => $predData['label_id'] ?? 'Tidak Diabetes',
                'label_code' => (int) ($predData['label_code'] ?? 0),
                'probabilities' => $predData['probabilities'] ?? ['No Diabetes' => 1.0, 'Prediabetes' => 0.0, 'Diabetes' => 0.0],
                'confidence_note' => $predData['confidence_note'] ?? 'sedang',
                'shap_local' => $shapData,
                'input_summary' => $assessResult['input_summary'] ?? FeatureMapper::buildInputSummary($features),
                'base_value' => (float) ($shapData['base_value'] ?? 0.0),
                'warnings' => $assessResult['warnings'] ?? [],
                'status' => 'done',
                'predicted_at' => now(),
            ]);

            $interpretation = Interpretation::create([
                'screening_id' => $screening->id,
                'prediction_id' => $prediction->id,
                'status' => 'pending',
                'prompt_version' => Setting::getValue('llm_prompt_version', 'p1.0'),
            ]);

            // Run synchronously so the result page has immediate narrative or when polled via /narasi
            GenerateInterpretationJob::dispatchSync($screening->id, $interpretation->id);

            AuditLog::record(
                'skrining.dibuat',
                $screening->id,
                [
                    'label' => $prediction->label,
                    'confidence' => $prediction->confidence_note,
                    'role' => $user->role,
                ],
                $user->id
            );

            return $screening->fresh(['prediction', 'latestInterpretation']);
        });
    }
}

