@extends('layouts.app')

@section('title', 'Eksperimen E0–E6 & SHAP Global (RM1–RM6)')
@section('page_title', 'Eksperimen E0–E6 & SHAP')

@section('content')
@php
    $bestCode = $selection['selected_code'] ?? 'E6';
    $e0Metrics = $baselineExp?->test_metrics ?? [];
    $e0PerClass = $baselineExp?->per_class ?? ($e0Metrics['per_class'] ?? []);
    $bestExp = $experiments->firstWhere('code', $bestCode) ?? $selectedExp;
    $bestMetrics = $bestExp?->test_metrics ?? [];

    $cmRaw = $selectedExp?->confusion ?? [];
    $cm = isset($cmRaw['matrix']) ? $cmRaw['matrix'] : (is_array($cmRaw) && count($cmRaw) === 3 ? $cmRaw : [[0, 0, 0], [0, 0, 0], [0, 0, 0]]);
    $cmLabels = ['Tidak Diabetes (0)', 'Prediabetes (1)', 'Diabetes (2)'];

    $findings = array_values(array_filter($weakness['findings'] ?? [], fn ($f) => !empty($f['triggered'])));
    $faithScore = (((float) ($llmEval['label_consistency_rate'] ?? 1.0)) + ((float) ($llmEval['direction_accuracy_rate'] ?? 1.0))) / 2.0;
    $shapRanking = $shapGlobal['ranking'] ?? [];
@endphp

<div class="space-y-6">
    <!-- Header & Primary Actions -->
    <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-2xs space-y-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 border-b border-slate-200/80">
            <div class="space-y-1">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-200/80">
                    Laporan Eksperimen Empiris BRFSS 2015 (E0–E6)
                </span>
                <h1 class="text-2xl font-extrabold text-slate-900">Evaluasi Komparatif XGBoost, TreeSHAP &amp; LLM (RM1–RM6)</h1>
                <p class="text-xs sm:text-sm text-slate-600">
                    Seluruh metrik diimpor langsung dari artefak eksekusi pipeline Python (<code class="font-mono text-xs bg-slate-100 px-1.5 py-0.5 rounded">ml/artifacts/</code>).
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <form method="POST" action="{{ route('admin.experiments.sync') }}">
                    @csrf
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold whitespace-nowrap shadow-xs transition">
                        ↻ Sinkronkan Artefak ML
                    </button>
                </form>
                <a href="{{ route('admin.ratings.export') }}" class="px-4 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold whitespace-nowrap transition">
                    Unduh CSV RM6
                </a>
            </div>
        </div>

        <!-- 4 Research KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80">
                <div class="text-[11px] font-bold text-slate-500 uppercase">RM1 · Baseline (E0) Macro-F1</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">
                    {{ number_format((float) ($e0Metrics['f1_macro'] ?? 0) * 100, 2, ',', '.') }}%
                </div>
                <div class="text-xs text-slate-500 mt-1">
                    Recall Prediabetes: <strong class="font-mono">{{ number_format((float) ($e0PerClass['Prediabetes']['recall'] ?? $e0PerClass['1']['recall'] ?? 0) * 100, 2, ',', '.') }}%</strong>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200/80">
                <div class="text-[11px] font-bold text-amber-800 uppercase">RM2 · Gerbang Kelemahan</div>
                <div class="text-2xl font-extrabold text-amber-950 mt-1 font-mono">
                    {{ count($findings) }} Temuan
                </div>
                <div class="text-xs text-amber-800 mt-1">
                    Bias kelas minoritas &amp; HPO terdeteksi
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-teal-50/70 border border-teal-200/80">
                <div class="text-[11px] font-bold text-teal-800 uppercase">RM3–RM4 · Terbaik ({{ $bestCode }})</div>
                <div class="text-2xl font-extrabold text-teal-950 mt-1 font-mono">
                    {{ number_format((float) ($selection['baseline_vs_best_summary']['best_test_macro_f1'] ?? $bestMetrics['f1_macro'] ?? 0) * 100, 2, ',', '.') }}%
                </div>
                <div class="text-xs text-teal-800 mt-1">
                    ROC-AUC OvR: <strong class="font-mono">{{ number_format((float) ($selection['baseline_vs_best_summary']['best_test_roc_auc_macro'] ?? $bestMetrics['roc_auc_ovr_macro'] ?? 0) * 100, 2, ',', '.') }}%</strong>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-indigo-50/70 border border-indigo-200/80">
                <div class="text-[11px] font-bold text-indigo-800 uppercase">RM5–RM6 · SHAP + LLM</div>
                <div class="text-2xl font-extrabold text-indigo-950 mt-1 font-mono">
                    {{ number_format($faithScore * 100, 1, ',', '.') }}%
                </div>
                <div class="text-xs text-indigo-800 mt-1 truncate">
                    Top-1 SHAP: <strong>{{ $shapRanking[0]['feature'] ?? 'GenHlth' }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- RM2 Weakness Gate Findings -->
    @if(!empty($findings))
        <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-2xs space-y-4">
            <div>
                <h2 class="text-base font-extrabold text-slate-900">Diagnosis Gerbang Kelemahan Baseline (RM2 — Weakness Gate)</h2>
                <p class="text-xs text-slate-500 mt-0.5">Justifikasi ilmiah mengapa baseline E0 memerlukan penanganan class imbalance dan optimasi hyperparameter.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($findings as $w)
                    <div class="p-4 rounded-xl bg-amber-50/50 border border-amber-200/90 space-y-2">
                        <span class="inline-flex px-2 py-0.5 rounded-md bg-amber-100 text-amber-950 font-mono text-[11px] font-bold">{{ $w['rule_id'] ?? 'W' }}</span>
                        <p class="text-xs text-slate-800 font-semibold leading-relaxed">{{ $w['diagnosis'] ?? '' }}</p>
                        @if(!empty($w['recommended_action']))
                            <p class="text-[11px] text-amber-900 pt-1 border-t border-amber-200/60">
                                <strong>Aksi:</strong> {{ $w['recommended_action'] }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- RM3 & RM4: Full E0-E6 Comparison Table -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-base font-extrabold text-slate-900">Matriks Perbandingan Eksperimen E0–E6 (RM1, RM3, RM4)</h2>
                <p class="text-xs text-slate-500 mt-0.5">Stratified Cross-Validation (Mean ± SD) dan Evaluasi Test Set Terpisah.</p>
            </div>
            <span class="text-xs bg-teal-50 text-teal-800 border border-teal-200 px-3 py-1 rounded-full font-semibold">
                Model Terbaik: {{ $bestCode }}
            </span>
        </div>

        <div class="table-wrapper">
            <table class="text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                    <tr>
                        <th scope="col" class="py-3 px-4">Kode</th>
                        <th scope="col" class="py-3 px-4">Skenario Eksperimen</th>
                        <th scope="col" class="py-3 px-4 text-right">CV Macro-F1</th>
                        <th scope="col" class="py-3 px-4 text-right">Accuracy</th>
                        <th scope="col" class="py-3 px-4 text-right">Balanced Acc</th>
                        <th scope="col" class="py-3 px-4 text-right">Test Macro-F1</th>
                        <th scope="col" class="py-3 px-4 text-right">Recall C1</th>
                        <th scope="col" class="py-3 px-4 text-right">Recall C2</th>
                        <th scope="col" class="py-3 px-4 text-right">ROC-AUC OvR</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/70">
                    @foreach($experiments as $exp)
                        @php
                            $cv = $exp->cv_metrics ?? [];
                            $tm = $exp->test_metrics ?? [];
                            $pc = $exp->per_class ?? ($tm['per_class'] ?? []);
                            $isBest = ($exp->code === $bestCode);
                            $cvMean = $cv['f1_macro']['mean'] ?? ($cv['macro_f1_mean'] ?? 0);
                            $cvStd = $cv['f1_macro']['std'] ?? ($cv['macro_f1_std'] ?? 0);
                            $recC1 = $pc['Prediabetes']['recall'] ?? ($pc['1']['recall'] ?? 0);
                            $recC2 = $pc['Diabetes']['recall'] ?? ($pc['2']['recall'] ?? 0);
                        @endphp
                        <tr class="{{ $isBest ? 'bg-teal-50/50 font-semibold' : 'hover:bg-slate-50/80' }}">
                            <th scope="row" class="py-3 px-4 font-mono font-extrabold text-slate-900 whitespace-nowrap">
                                {{ $exp->code }}
                                @if($isBest)
                                    <span class="ml-1 px-1.5 py-0.5 rounded bg-teal-700 text-white text-[10px]">BEST</span>
                                @endif
                            </th>
                            <td class="py-3 px-4 text-slate-800 whitespace-nowrap">{{ $exp->name }}</td>
                            <td class="py-3 px-4 text-right font-mono whitespace-nowrap">
                                {{ number_format((float) $cvMean * 100, 2, ',', '.') }}%
                                <span class="text-slate-400">±{{ number_format((float) $cvStd * 100, 2, ',', '.') }}</span>
                            </td>
                            <td class="py-3 px-4 text-right font-mono">{{ number_format((float) ($tm['accuracy'] ?? 0) * 100, 2, ',', '.') }}%</td>
                            <td class="py-3 px-4 text-right font-mono">{{ number_format((float) ($tm['balanced_accuracy'] ?? 0) * 100, 2, ',', '.') }}%</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-teal-800">{{ number_format((float) ($tm['f1_macro'] ?? 0) * 100, 2, ',', '.') }}%</td>
                            <td class="py-3 px-4 text-right font-mono">{{ number_format((float) $recC1 * 100, 2, ',', '.') }}%</td>
                            <td class="py-3 px-4 text-right font-mono">{{ number_format((float) $recC2 * 100, 2, ',', '.') }}%</td>
                            <td class="py-3 px-4 text-right font-mono">{{ number_format((float) ($tm['roc_auc_ovr_macro'] ?? 0) * 100, 2, ',', '.') }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(!empty($selection['empirical_justification']))
            <div class="px-6 py-4 bg-slate-50/70 border-t border-slate-200/80 text-xs text-slate-700 leading-relaxed">
                <strong>Justifikasi Empiris Model Terbaik ({{ $bestCode }}):</strong> {{ $selection['empirical_justification'] }}
            </div>
        @endif
    </div>

    <!-- Charts Grid: E0-E6 Bar Chart + Confusion Matrix Heatmap -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/90 p-6 shadow-2xs">
            <h3 class="text-base font-bold text-slate-900">Grafik Perbandingan Macro-F1 &amp; Balanced Accuracy (E0–E6)</h3>
            <p class="text-xs text-slate-500 mt-0.5 mb-4">Performa uji antar skenario eksperimen pada data uji terpisah.</p>
            <div class="h-72">
                <canvas id="expComparisonChart"></canvas>
            </div>
        </div>

        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200/90 p-6 shadow-2xs flex flex-col justify-between">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Confusion Matrix 3×3 ({{ $selectedExp?->code ?? 'E6' }})</h3>
                    <p class="text-xs text-slate-500">Baris = Aktual · Kolom = Prediksi</p>
                </div>
                <form method="GET" action="{{ route('admin.experiments') }}">
                    <select name="cm_code" onchange="this.form.submit()" aria-label="Pilih skenario untuk Confusion Matrix" class="compact-input rounded-xl border border-slate-300 bg-white text-xs font-semibold py-1.5 pl-2.5 pr-7">
                        @foreach($experiments as $exp)
                            <option value="{{ $exp->code }}" {{ ($selectedExp?->code === $exp->code) ? 'selected' : '' }}>{{ $exp->code }} — {{ $exp->name }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="table-wrapper">
                <table class="text-center text-xs">
                    <thead>
                        <tr>
                            <th class="p-2 text-left text-[11px] text-slate-400">Aktual \ Pred</th>
                            @foreach($cmLabels as $lbl)
                                <th class="p-2 font-bold text-slate-700 bg-slate-50 border border-slate-200">{{ $lbl }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cm as $rIdx => $row)
                            @php $rowSum = max(1, array_sum($row)); @endphp
                            <tr>
                                <th class="p-2 text-left font-bold text-slate-700 bg-slate-50 border border-slate-200">{{ $cmLabels[$rIdx] ?? $rIdx }}</th>
                                @foreach($row as $cIdx => $val)
                                    @php
                                        $pct = ($val / $rowSum) * 100;
                                        $isDiag = ($rIdx === $cIdx);
                                        $cellBg = $isDiag ? 'bg-teal-700 text-white font-bold' : ($pct > 20 ? 'bg-rose-50 text-rose-900 font-bold' : 'bg-white text-slate-700');
                                    @endphp
                                    <td class="p-3 border border-slate-200 font-mono {{ $cellBg }}">
                                        <div class="text-sm">{{ number_format((int) $val, 0, ',', '.') }}</div>
                                        <div class="text-[10px] opacity-80">{{ number_format($pct, 1, ',', '.') }}%</div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- RM5: Global SHAP Feature Importance Ranking -->
    <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-2xs space-y-5">
        <div>
            <h2 class="text-base font-extrabold text-slate-900">Peringkat Kepentingan Fitur Global TreeSHAP (RM5)</h2>
            <p class="text-xs text-slate-500 mt-0.5">Rata-rata nilai absolut SHAP (<code class="font-mono">mean(|SHAP|)</code>) lintas kelas Tidak Diabetes, Prediabetes, dan Diabetes.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <div class="lg:col-span-7 h-80">
                <canvas id="shapGlobalChart"></canvas>
            </div>
            <div class="lg:col-span-5 table-wrapper max-h-80 border border-slate-200/80 rounded-xl">
                <table class="text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                        <tr>
                            <th scope="col" class="py-2.5 px-3 w-10">#</th>
                            <th scope="col" class="py-2.5 px-3">Fitur Klinis</th>
                            <th scope="col" class="py-2.5 px-3 text-right">Mean |SHAP|</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/70">
                        @foreach(array_slice($shapRanking, 0, 12) as $idx => $item)
                            @php $meanAbs = $item['mean_abs_shap_overall'] ?? ($item['mean_abs_shap'] ?? 0); @endphp
                            <tr class="hover:bg-slate-50/80">
                                <td class="py-2 px-3 font-mono font-bold text-slate-500">{{ $idx + 1 }}</td>
                                <th scope="row" class="py-2 px-3 font-semibold text-slate-800">{{ $item['label_id'] ?? $item['feature'] }}</th>
                                <td class="py-2 px-3 text-right font-mono font-bold text-teal-700">{{ number_format((float) $meanAbs, 4, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const expLabels = @json($experiments->pluck('code'));
    const expF1 = @json($experiments->map(fn($e) => round(($e->test_metrics['f1_macro'] ?? 0) * 100, 2)));
    const expBalAcc = @json($experiments->map(fn($e) => round(($e->test_metrics['balanced_accuracy'] ?? 0) * 100, 2)));

    const ctxExp = document.getElementById('expComparisonChart');
    if (ctxExp && window.Chart) {
        new Chart(ctxExp, {
            type: 'bar',
            data: {
                labels: expLabels,
                datasets: [
                    { label: 'Macro-F1 (%)', data: expF1, backgroundColor: '#0f766e', borderRadius: 6 },
                    { label: 'Balanced Accuracy (%)', data: expBalAcc, backgroundColor: '#4f46e5', borderRadius: 6 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, max: 100 } }
            }
        });
    }

    const shapRanking = @json(array_slice($shapRanking, 0, 10));
    const ctxShap = document.getElementById('shapGlobalChart');
    if (ctxShap && window.Chart && shapRanking.length) {
        new Chart(ctxShap, {
            type: 'bar',
            data: {
                labels: shapRanking.map(r => r.label_id || r.feature),
                datasets: [{
                    label: 'Mean |SHAP| (Log-Odds)',
                    data: shapRanking.map(r => Number(r.mean_abs_shap_overall ?? r.mean_abs_shap ?? 0).toFixed(4)),
                    backgroundColor: '#0d9488',
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    }
});
</script>
@endpush
@endsection
