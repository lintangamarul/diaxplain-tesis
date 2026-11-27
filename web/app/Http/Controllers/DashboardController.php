<?php

namespace App\Http\Controllers;

use App\Models\ExperimentRun;
use App\Models\Interpretation;
use App\Models\ModelVersion;
use App\Models\NarrativeRating;
use App\Models\Prediction;
use App\Models\Screening;
use App\Models\Setting;
use App\Services\FeatureMapper;
use App\Services\MlClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function landing(): View
    {
        $activeModel = ModelVersion::where('is_active', true)->first();
        $selection = json_decode((string) Setting::getValue('selection_report_json', '{}'), true) ?: [];
        $eda = json_decode((string) Setting::getValue('eda_summary_json', '{}'), true) ?: [];

        return view('welcome', [
            'activeModel' => $activeModel,
            'selection' => $selection,
            'eda' => $eda,
            'disclaimer' => FeatureMapper::systemDisclaimerText(),
        ]);
    }

    public function aboutModel(): View
    {
        $activeModel = ModelVersion::where('is_active', true)->first();
        $experiments = ExperimentRun::orderBy('code')->get();
        $weakness = json_decode((string) Setting::getValue('weakness_report_json', '{}'), true) ?: [];
        $selection = json_decode((string) Setting::getValue('selection_report_json', '{}'), true) ?: [];
        $shapGlobal = json_decode((string) Setting::getValue('shap_global_json', '{}'), true) ?: [];
        $eda = json_decode((string) Setting::getValue('eda_summary_json', '{}'), true) ?: [];

        return view('about', [
            'activeModel' => $activeModel,
            'experiments' => $experiments,
            'weakness' => $weakness,
            'selection' => $selection,
            'shapGlobal' => $shapGlobal,
            'eda' => $eda,
            'specs' => FeatureMapper::getSchema()['features'] ?? FeatureMapper::defaultSpecs(),
        ]);
    }

    public function index(Request $request, MlClient $mlClient): View
    {
        $user = $request->user();
        $activeModel = ModelVersion::where('is_active', true)->first();

        $myScreenings = Screening::with(['prediction', 'latestInterpretation'])
            ->where('user_id', $user->id)
            ->latest()
            ->take(8)
            ->get();

        $myTotalScreenings = Screening::where('user_id', $user->id)->count();
        $classDistribution = Prediction::whereHas('screening', fn ($q) => $q->where('user_id', $user->id))
            ->selectRaw('label, count(*) as total')
            ->groupBy('label')
            ->pluck('total', 'label')
            ->toArray();

        $adminStats = null;
        if ($user->isAdmin()) {
            $adminStats = [
                'total_users' => \App\Models\User::count(),
                'total_screenings' => Screening::count(),
                'total_experiments' => ExperimentRun::count(),
                'total_ratings' => NarrativeRating::count(),
                'llm_provider' => Setting::getValue('llm_provider', 'openai_compatible'),
                'llm_model' => Setting::getValue('llm_model', 'gpt-4o-mini'),
                'llm_key_configured' => !empty(Setting::getValue('llm_api_key', '')),
                'ml_health' => $mlClient->health(),
            ];
        }

        $nakesStats = null;
        if ($user->isNakes() || $user->isAdmin()) {
            $ratedIds = NarrativeRating::where('rater_id', $user->id)->pluck('interpretation_id');
            $unratedCount = Interpretation::where('status', 'done')->whereNotIn('id', $ratedIds)->count();
            $nakesStats = [
                'rated_by_me' => $ratedIds->count(),
                'pending_rating' => $unratedCount,
            ];
        }

        return view('dashboard', [
            'user' => $user,
            'activeModel' => $activeModel,
            'myScreenings' => $myScreenings,
            'myTotalScreenings' => $myTotalScreenings,
            'classDistribution' => $classDistribution,
            'adminStats' => $adminStats,
            'nakesStats' => $nakesStats,
        ]);
    }
}

