<?php

namespace App\Jobs;

use App\Models\Interpretation;
use App\Models\Screening;
use App\Services\FeatureMapper;
use App\Services\MlClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateInterpretationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [2, 5, 10];

    public function __construct(
        public string $screeningId,
        public int $interpretationId
    ) {}

    public function handle(MlClient $mlClient): void
    {
        $screening = Screening::with('prediction')->find($this->screeningId);
        $interpretation = Interpretation::find($this->interpretationId);

        if (!$screening || !$screening->prediction || !$interpretation) {
            return;
        }

        $pred = $screening->prediction;
        $shapLocal = $pred->shap_local ?? [];
        $inputSummary = $pred->input_summary ?: FeatureMapper::buildInputSummary($screening->input ?? []);

        $structuredPayload = [
            'case_id' => 'scr_' . substr(str_replace('-', '', $screening->id), 0, 12),
            'model' => [
                'version' => $pred->model_version_tag ?? 'xgb-v1.0.0',
                'technique' => 'smote+optuna',
            ],
            'prediction' => [
                'label' => $pred->label,
                'label_code' => (int) $pred->label_code,
                'probabilities' => $pred->probabilities,
                'confidence_note' => $pred->confidence_note,
            ],
            'input_summary' => array_map(fn ($item) => [
                'feature' => $item['feature'],
                'label_id' => $item['label_id'],
                'value' => $item['value'],
                'display' => $item['display'],
            ], $inputSummary),
            'shap_top' => array_map(fn ($item) => [
                'rank' => (int) $item['rank'],
                'feature' => $item['feature'],
                'label_id' => $item['label_id'],
                'display' => $item['display'],
                'shap' => (float) $item['shap'],
                'direction' => $item['direction'],
            ], $shapLocal['top'] ?? []),
            'allowed_features' => FeatureMapper::FEATURE_ORDER,
            'language' => 'id',
            'audience' => $screening->audience ?? 'awam',
        ];

        try {
            $result = $mlClient->interpret($structuredPayload);
            $meta = $result['meta'] ?? [];
            $val = $result['validation'] ?? [];

            $interpretation->update([
                'status' => 'done',
                'narrative' => $result['narrative'] ?? null,
                'validation' => $val,
                'used_fallback' => (bool) ($meta['used_fallback'] ?? false),
                'provider' => $meta['provider'] ?? 'template_fallback',
                'model_name' => $meta['model'] ?? 'deterministic-v1',
                'prompt_version' => $meta['prompt_version'] ?? 'p1.0',
                'latency_ms' => (int) ($meta['latency_ms'] ?? 0),
                'retries' => (int) ($val['retries'] ?? 0),
                'tokens_in' => (int) ($meta['tokens_in'] ?? 0),
                'tokens_out' => (int) ($meta['tokens_out'] ?? 0),
            ]);
        } catch (\Throwable $e) {
            Log::error('GenerateInterpretationJob failed: ' . $e->getMessage());
            $fallback = $mlClient->localDeterministicInterpretation($structuredPayload);
            $interpretation->update([
                'status' => 'done',
                'narrative' => $fallback['narrative'],
                'validation' => $fallback['validation'],
                'used_fallback' => true,
                'provider' => 'template_fallback',
                'model_name' => 'deterministic-v1',
                'prompt_version' => 'p1.0',
                'latency_ms' => 15,
            ]);
        }
    }
}

