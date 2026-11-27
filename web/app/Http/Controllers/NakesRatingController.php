<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Interpretation;
use App\Models\NarrativeRating;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NakesRatingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $interpretations = Interpretation::with(['screening', 'prediction', 'ratings'])
            ->where('status', 'done')
            ->latest()
            ->paginate(10);

        $myRatingsMap = NarrativeRating::where('rater_id', $user->id)
            ->get()
            ->keyBy('interpretation_id');

        $llmEvalSummary = json_decode((string) \App\Models\Setting::getValue('llm_eval_summary_json', '{}'), true) ?: [];

        return view('nakes.ratings', [
            'interpretations' => $interpretations,
            'myRatingsMap' => $myRatingsMap,
            'llmEvalSummary' => $llmEvalSummary,
        ]);
    }

    public function store(Request $request, Interpretation $interpretation): RedirectResponse
    {
        $validated = $request->validate([
            'akurasi' => ['required', 'integer', 'min:1', 'max:5'],
            'kejelasan' => ['required', 'integer', 'min:1', 'max:5'],
            'kegunaan' => ['required', 'integer', 'min:1', 'max:5'],
            'keamanan' => ['required', 'integer', 'min:1', 'max:5'],
            'komentar' => ['nullable', 'string', 'max:1000'],
        ]);

        $rating = NarrativeRating::updateOrCreate(
            [
                'interpretation_id' => $interpretation->id,
                'rater_id' => $request->user()->id,
            ],
            $validated
        );

        AuditLog::record(
            'nakes.nilai_narasi',
            (string) $interpretation->id,
            [
                'avg' => $rating->averageScore(),
                'scores' => [
                    'akurasi' => $rating->akurasi,
                    'kejelasan' => $rating->kejelasan,
                    'kegunaan' => $rating->kegunaan,
                    'keamanan' => $rating->keamanan,
                ],
            ]
        );

        return back()->with('success', 'Penilaian pakar terhadap narasi LLM berhasil disimpan (Skor rata-rata: ' . $rating->averageScore() . '/5,0).');
    }
}

