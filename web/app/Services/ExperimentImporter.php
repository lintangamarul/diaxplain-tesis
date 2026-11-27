<?php

namespace App\Services;

use App\Models\ExperimentRun;
use App\Models\ModelVersion;
use App\Models\Setting;

class ExperimentImporter
{
    public function importFromArtifacts(?string $customDir = null): array
    {
        $artifactsDir = $customDir ?: base_path(config('services.diaxplain_ml.artifacts_path', '../ml/artifacts'));
        $expRoot = $artifactsDir . '/experiments';
        $modelDir = $artifactsDir . '/model_v1';

        $activeVersion = null;
        if (is_file($modelDir . '/metrics.json')) {
            $mData = json_decode((string) file_get_contents($modelDir . '/metrics.json'), true) ?: [];
            ModelVersion::query()->update(['is_active' => false]);
            $activeVersion = ModelVersion::updateOrCreate(
                ['version_tag' => $mData['model_version'] ?? 'xgb-v1.0.0'],
                [
                    'name' => $mData['name'] ?? 'XGBoost Best Model (E6)',
                    'algorithm' => $mData['algorithm'] ?? 'XGBoost (multi:softprob, hist)',
                    'technique' => $mData['technique'] ?? 'smote+optuna',
                    'params' => $mData['params'] ?? [],
                    'metrics' => [
                        'cv_metrics' => $mData['cv_metrics'] ?? [],
                        'test_metrics' => $mData['test_metrics'] ?? [],
                        'selection_report' => $mData['selection_report'] ?? [],
                    ],
                    'artifact_path' => 'artifacts/model_v1',
                    'dataset_hash' => trim((string) ($mData['dataset_hash'] ?? '')),
                    'is_active' => true,
                    'trained_at' => !empty($mData['trained_at']) ? $mData['trained_at'] : now(),
                ]
            );
        }

        $importedCodes = [];
        if (is_dir($expRoot)) {
            $dirs = glob($expRoot . '/E*', GLOB_ONLYDIR) ?: [];
            foreach ($dirs as $dir) {
                $metricsFile = $dir . '/metrics.json';
                if (!is_file($metricsFile)) {
                    continue;
                }
                $data = json_decode((string) file_get_contents($metricsFile), true);
                if (!is_array($data) || empty($data['code'])) {
                    continue;
                }
                $tm = $data['test_metrics'] ?? [];
                ExperimentRun::updateOrCreate(
                    ['code' => $data['code']],
                    [
                        'model_version_id' => $activeVersion?->id,
                        'name' => $data['name'] ?? $data['code'],
                        'technique' => $data['technique'] ?? 'baseline',
                        'params' => $data['params'] ?? [],
                        'cv_metrics' => $data['cv_metrics'] ?? [],
                        'test_metrics' => $tm,
                        'per_class' => $tm['per_class'] ?? [],
                        'confusion' => $tm['confusion_matrix'] ?? [],
                        'roc' => $data['roc'] ?? [],
                        'train_seconds' => (float) ($data['train_seconds'] ?? 0),
                        'notes' => isset($data['wilcoxon_vs_baseline']['note']) ? $data['wilcoxon_vs_baseline']['note'] : null,
                        'imported_at' => now(),
                    ]
                );
                $importedCodes[] = $data['code'];
            }
        }

        // Store JSON artifacts in settings for instant dashboard rendering
        if (is_file($expRoot . '/weakness_report.json')) {
            Setting::setValue('weakness_report_json', (string) file_get_contents($expRoot . '/weakness_report.json'));
        }
        if (is_file($expRoot . '/selection_report.json')) {
            Setting::setValue('selection_report_json', (string) file_get_contents($expRoot . '/selection_report.json'));
        }
        if (is_file($artifactsDir . '/shap/shap_global.json')) {
            Setting::setValue('shap_global_json', (string) file_get_contents($artifactsDir . '/shap/shap_global.json'));
        }
        if (is_file($artifactsDir . '/llm_eval/summary.json')) {
            Setting::setValue('llm_eval_summary_json', (string) file_get_contents($artifactsDir . '/llm_eval/summary.json'));
        }
        if (is_file($artifactsDir . '/eda/eda_summary.json')) {
            Setting::setValue('eda_summary_json', (string) file_get_contents($artifactsDir . '/eda/eda_summary.json'));
        }

        // Copy high-DPI PNG artifacts to public/artifacts/ so browser can display them directly
        $pubArtifacts = public_path('artifacts');
        if (!is_dir($pubArtifacts)) {
            @mkdir($pubArtifacts, 0775, true);
        }
        foreach (['eda', 'shap'] as $sub) {
            $srcSub = $artifactsDir . '/' . $sub;
            $dstSub = $pubArtifacts . '/' . $sub;
            if (is_dir($srcSub)) {
                if (!is_dir($dstSub)) {
                    @mkdir($dstSub, 0775, true);
                }
                foreach (glob($srcSub . '/*.png') ?: [] as $png) {
                    @copy($png, $dstSub . '/' . basename($png));
                }
            }
        }

        return [
            'model_version' => $activeVersion?->version_tag,
            'experiments_imported' => $importedCodes,
        ];
    }
}

