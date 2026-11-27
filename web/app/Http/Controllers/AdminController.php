<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ExperimentRun;
use App\Models\Interpretation;
use App\Models\ModelVersion;
use App\Models\NarrativeRating;
use App\Models\Setting;
use App\Models\User;
use App\Services\ExperimentImporter;
use App\Services\MlClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function experiments(Request $request): View
    {
        $experiments = ExperimentRun::orderBy('code')->get();
        $activeModel = ModelVersion::where('is_active', true)->first();
        $weakness = json_decode((string) Setting::getValue('weakness_report_json', '{}'), true) ?: [];
        $selection = json_decode((string) Setting::getValue('selection_report_json', '{}'), true) ?: [];
        $shapGlobal = json_decode((string) Setting::getValue('shap_global_json', '{}'), true) ?: [];
        $llmEval = json_decode((string) Setting::getValue('llm_eval_summary_json', '{}'), true) ?: [];
        $eda = json_decode((string) Setting::getValue('eda_summary_json', '{}'), true) ?: [];

        $selectedCode = $request->input('cm_code', $selection['selected_code'] ?? 'E6');
        $selectedExp = $experiments->firstWhere('code', $selectedCode) ?? $experiments->first();
        $baselineExp = $experiments->firstWhere('code', 'E0') ?? $experiments->first();

        return view('admin.experiments', [
            'experiments' => $experiments,
            'activeModel' => $activeModel,
            'weakness' => $weakness,
            'selection' => $selection,
            'shapGlobal' => $shapGlobal,
            'llmEval' => $llmEval,
            'eda' => $eda,
            'selectedExp' => $selectedExp,
            'baselineExp' => $baselineExp,
        ]);
    }

    public function syncExperiments(ExperimentImporter $importer): RedirectResponse
    {
        $res = $importer->importFromArtifacts();
        AuditLog::record('admin.impor_eksperimen', implode(',', $res['experiments_imported'] ?? []));

        return back()->with('success', 'Artefak eksperimen (E0–E6, Weakness Gate, SHAP Global, dan Evaluasi LLM) berhasil disinkronkan dari ml/artifacts!');
    }

    public function models(): View
    {
        $models = ModelVersion::withCount('predictions')->latest()->get();
        return view('admin.models', [
            'models' => $models,
        ]);
    }

    public function activateModel(ModelVersion $modelVersion): RedirectResponse
    {
        ModelVersion::query()->update(['is_active' => false]);
        $modelVersion->update(['is_active' => true]);

        AuditLog::record('admin.aktifkan_model', $modelVersion->version_tag);
        return back()->with('success', "Model {$modelVersion->name} ({$modelVersion->version_tag}) berhasil diaktifkan.");
    }

    public function llmConfig(MlClient $mlClient): View
    {
        $health = $mlClient->health();
        $recentLogs = Interpretation::with(['screening', 'prediction'])
            ->latest()
            ->take(20)
            ->get();

        $rawKey = (string) Setting::getValue('llm_api_key', '');
        $maskedKey = strlen($rawKey) > 8
            ? substr($rawKey, 0, 6) . str_repeat('*', max(8, strlen($rawKey) - 10)) . substr($rawKey, -4)
            : '';

        return view('admin.llm-config', [
            'provider' => Setting::getValue('llm_provider', 'openai_compatible'),
            'baseUrl' => Setting::getValue('llm_base_url', 'https://api.openai.com/v1'),
            'modelName' => Setting::getValue('llm_model', 'gpt-4o-mini'),
            'temperature' => Setting::getValue('llm_temperature', '0.2'),
            'promptVersion' => Setting::getValue('llm_prompt_version', 'p1.0'),
            'topK' => Setting::getValue('shap_top_k', '6'),
            'hasApiKey' => !empty($rawKey),
            'maskedKey' => $maskedKey,
            'mlHealth' => $health,
            'recentLogs' => $recentLogs,
        ]);
    }

    public function updateLlmConfig(Request $request, MlClient $mlClient): RedirectResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'string', 'in:openai_compatible,gemini,anthropic,template_fallback'],
            'api_key' => ['nullable', 'string', 'max:300'],
            'clear_api_key' => ['nullable', 'boolean'],
            'base_url' => ['nullable', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:100'],
            'temperature' => ['required', 'numeric', 'min:0', 'max:1.5'],
            'prompt_version' => ['required', 'string', 'max:20'],
            'shap_top_k' => ['required', 'integer', 'min:3', 'max:15'],
            'test_connection' => ['nullable', 'boolean'],
        ]);

        Setting::setValue('llm_provider', $validated['provider']);
        Setting::setValue('llm_base_url', $validated['base_url'] ?? 'https://api.openai.com/v1');
        Setting::setValue('llm_model', $validated['model']);
        Setting::setValue('llm_temperature', (string) $validated['temperature']);
        Setting::setValue('llm_prompt_version', $validated['prompt_version']);
        Setting::setValue('shap_top_k', (string) $validated['shap_top_k']);

        $newKey = trim((string) ($validated['api_key'] ?? ''));
        if ($request->boolean('clear_api_key')) {
            Setting::setValue('llm_api_key', '');
        } elseif ($newKey !== '' && !str_starts_with($newKey, '****')) {
            Setting::setValue('llm_api_key', $newKey);
        }

        $effectiveKey = (string) Setting::getValue('llm_api_key', '');

        $syncRes = $mlClient->updateLlmConfig([
            'provider' => $validated['provider'],
            'api_key' => $effectiveKey,
            'base_url' => $validated['base_url'] ?? 'https://api.openai.com/v1',
            'model' => $validated['model'],
            'temperature' => (float) $validated['temperature'],
            'prompt_version' => $validated['prompt_version'],
            'test_connection' => $request->boolean('test_connection'),
        ]);

        AuditLog::record('admin.ubah_konfigurasi_llm', $validated['provider'], [
            'model' => $validated['model'],
            'prompt_version' => $validated['prompt_version'],
            'key_configured' => !empty($effectiveKey),
        ]);

        $msg = 'Konfigurasi Layanan AI / API Key berhasil disimpan dan disinkronkan ke ML Service.';
        if (!empty($syncRes['test_result'])) {
            $tr = $syncRes['test_result'];
            $msg .= ' Hasil Uji Koneksi: ' . ($tr['message'] ?? '');
            if (empty($tr['ok'])) {
                return back()->with('warning', $msg);
            }
        }

        return back()->with('success', $msg);
    }

    public function users(): View
    {
        $users = User::withCount(['screenings', 'narrativeRatings'])->latest()->get();
        return view('admin.users', [
            'users' => $users,
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,nakes,pengguna'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
            'consent_at' => now(),
        ]);

        AuditLog::record('admin.tambah_pengguna', $user->email, ['role' => $user->role]);
        return back()->with('success', "Pengguna {$user->name} ({$user->role}) berhasil ditambahkan.");
    }

    public function updateUserRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:admin,nakes,pengguna'],
            'is_active' => ['required', 'boolean'],
        ]);

        $user->update($validated);
        AuditLog::record('admin.ubah_role_pengguna', $user->email, $validated);

        return back()->with('success', "Peran dan status akun {$user->name} berhasil diperbarui.");
    }

    public function exportRatingsCsv(): StreamedResponse
    {
        AuditLog::record('admin.ekspor_csv_penilaian', 'narrative_ratings.csv');

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'id_rating',
                'id_interpretasi',
                'id_skrining',
                'label_prediksi',
                'provider_llm',
                'model_llm',
                'versi_prompt',
                'used_fallback',
                'penilai_nama',
                'penilai_role',
                'skor_akurasi',
                'skor_kejelasan',
                'skor_kegunaan',
                'skor_keamanan',
                'skor_rerata',
                'komentar',
                'tanggal_penilaian',
            ]);

            $ratings = NarrativeRating::with(['interpretation.prediction', 'rater'])->latest()->get();
            foreach ($ratings as $r) {
                $interp = $r->interpretation;
                $pred = $interp?->prediction;
                fputcsv($out, [
                    $r->id,
                    $r->interpretation_id,
                    $interp?->screening_id,
                    $pred?->label,
                    $interp?->provider,
                    $interp?->model_name,
                    $interp?->prompt_version,
                    $interp?->used_fallback ? 'Ya' : 'Tidak',
                    $r->rater?->name,
                    $r->rater?->role,
                    $r->akurasi,
                    $r->kejelasan,
                    $r->kegunaan,
                    $r->keamanan,
                    $r->averageScore(),
                    $r->komentar,
                    $r->created_at?->toIso8601String(),
                ]);
            }
            fclose($out);
        }, 'DiaXplain_Evaluasi_Narasi_LLM_' . now()->format('Ymd_His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function auditLogs(): View
    {
        $logs = AuditLog::with('user')->latest()->paginate(25);
        return view('admin.audit-logs', [
            'logs' => $logs,
        ]);
    }
}

