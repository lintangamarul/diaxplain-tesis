<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScreeningRequest;
use App\Jobs\GenerateInterpretationJob;
use App\Models\AuditLog;
use App\Models\NarrativeRating;
use App\Models\Screening;
use App\Services\FeatureMapper;
use App\Services\ReportPdfService;
use App\Services\ScreeningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ScreeningController extends Controller
{
    public function create(Request $request): View
    {
        $schema = FeatureMapper::getSchema();
        return view('skrining.create', [
            'user' => $request->user(),
            'features' => $schema['features'] ?? FeatureMapper::defaultSpecs(),
            'disclaimer' => FeatureMapper::systemDisclaimerText(),
        ]);
    }

    public function store(StoreScreeningRequest $request, ScreeningService $service): RedirectResponse
    {
        $user = $request->user();
        $features = $request->validatedFeatures();
        $physicalMeta = array_filter([
            'age_years' => $request->input('age_years'),
            'weight_kg' => $request->input('weight_kg'),
            'height_cm' => $request->input('height_cm'),
        ], fn ($v) => $v !== null && $v !== '');

        $patientLabel = ($user->isNakes() || $user->isAdmin()) ? $request->input('patient_label') : null;
        $audience = ($user->isNakes() || $user->isAdmin())
            ? ($request->input('audience', 'tenaga_medis'))
            : 'awam';

        $screening = $service->createAndAssess($user, $features, $physicalMeta, $patientLabel, $audience);

        return redirect()
            ->route('skrining.show', $screening->id)
            ->with('success', 'Analisis skrining XGBoost + TreeSHAP berhasil dijalankan!');
    }

    public function show(Request $request, Screening $screening): View
    {
        Gate::authorize('view', $screening);

        $screening->load(['prediction.modelVersion', 'latestInterpretation.ratings']);
        $myRating = null;
        if ($screening->latestInterpretation && ($request->user()->isNakes() || $request->user()->isAdmin())) {
            $myRating = NarrativeRating::where('interpretation_id', $screening->latestInterpretation->id)
                ->where('rater_id', $request->user()->id)
                ->first();
        }

        return view('skrining.show', [
            'screening' => $screening,
            'prediction' => $screening->prediction,
            'interpretation' => $screening->latestInterpretation,
            'myRating' => $myRating,
            'disclaimer' => FeatureMapper::systemDisclaimerText(),
        ]);
    }

    public function pollNarrative(Request $request, Screening $screening): JsonResponse
    {
        Gate::authorize('view', $screening);

        $interpretation = $screening->latestInterpretation;
        if (!$interpretation) {
            return response()->json(['status' => 'not_found'], 404);
        }

        if ($interpretation->status === 'pending') {
            GenerateInterpretationJob::dispatchSync($screening->id, $interpretation->id);
            $interpretation->refresh();
        }

        return response()->json([
            'status' => $interpretation->status,
            'narrative' => $interpretation->narrative,
            'validation' => $interpretation->validation,
            'used_fallback' => $interpretation->used_fallback,
            'provider' => $interpretation->provider,
            'model_name' => $interpretation->model_name,
            'prompt_version' => $interpretation->prompt_version,
            'latency_ms' => $interpretation->latency_ms,
            'disclaimer' => FeatureMapper::systemDisclaimerText(),
        ]);
    }

    public function regenerateNarrative(Request $request, Screening $screening): RedirectResponse
    {
        Gate::authorize('view', $screening);
        $interpretation = $screening->latestInterpretation;
        if ($interpretation) {
            $interpretation->update(['status' => 'pending']);
            GenerateInterpretationJob::dispatchSync($screening->id, $interpretation->id);
        }

        return redirect()
            ->route('skrining.show', $screening->id)
            ->with('success', 'Narasi interpretasi AI berhasil diperbarui dengan konfigurasi LLM aktif.');
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = Screening::with(['prediction', 'latestInterpretation'])
            ->where('user_id', $user->id)
            ->latest();

        if ($request->filled('search')) {
            $s = trim((string) $request->input('search'));
            $query->where('patient_label', 'like', "%{$s}%");
        }

        if ($request->filled('label')) {
            $lbl = (string) $request->input('label');
            $query->whereHas('prediction', fn ($q) => $q->where('label', $lbl));
        }

        $screenings = $query->paginate(12)->withQueryString();

        return view('skrining.index', [
            'screenings' => $screenings,
            'user' => $user,
        ]);
    }

    public function compare(Request $request): View
    {
        $user = $request->user();
        $allScreenings = Screening::with('prediction')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        $leftId = $request->input('left_id') ?: ($allScreenings[0]->id ?? null);
        $rightId = $request->input('right_id') ?: ($allScreenings[1]->id ?? ($allScreenings[0]->id ?? null));

        $left = $leftId ? Screening::with(['prediction', 'latestInterpretation'])->where('user_id', $user->id)->find($leftId) : null;
        $right = $rightId ? Screening::with(['prediction', 'latestInterpretation'])->where('user_id', $user->id)->find($rightId) : null;

        return view('skrining.compare', [
            'allScreenings' => $allScreenings,
            'left' => $left,
            'right' => $right,
            'specs' => FeatureMapper::getSchema()['features'] ?? FeatureMapper::defaultSpecs(),
        ]);
    }

    public function downloadPdf(Request $request, Screening $screening, ReportPdfService $pdfService): Response
    {
        Gate::authorize('view', $screening);
        AuditLog::record('skrining.unduh_pdf', $screening->id);

        $binary = $pdfService->generatePdfBinary($screening);
        $shortId = substr($screening->id, 0, 8);
        $filename = "Laporan_SPK_DiaXplain_{$shortId}.pdf";

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function destroy(Request $request, Screening $screening): RedirectResponse
    {
        Gate::authorize('delete', $screening);
        $id = $screening->id;
        $screening->delete();

        AuditLog::record('skrining.hapus', $id);
        return redirect()->route('skrining.index')->with('success', 'Data skrining berhasil dihapus secara permanen.');
    }

    public function purgeMyData(Request $request): RedirectResponse
    {
        $user = $request->user();
        $count = Screening::where('user_id', $user->id)->count();
        Screening::where('user_id', $user->id)->delete();

        AuditLog::record('privasi.hapus_semua_skrining', $user->email, ['deleted_count' => $count], $user->id);

        return redirect()->route('skrining.index')->with('success', "Seluruh {$count} riwayat skrining pribadi Anda telah dihapus permanen sesuai hak UU PDP.");
    }
}

