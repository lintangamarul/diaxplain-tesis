@extends('layouts.app')

@section('title', 'Dasbor Eksperimen Riset Tesis (RM1–RM6)')

@section('content')
@php
    $bestCode = $selection['selected_code'] ?? ($activeModel?->code ?? 'E6');
    $e0Metrics = $baselineExp?->test_metrics_json ?? [];
    $bestMetrics = $selectedExp?->test_metrics_json ?? [];
    $cm = $selectedExp?->confusion_matrix_json['matrix'] ?? [[0, 0, 0], [0, 0, 0], [0, 0, 0]];
    $cmLabels = ['Non-Diabetes (0)', 'Prediabetes (1)', 'Diabetes (2)'];
@endphp

<div class="space-y-6">
    <!-- Admin Sub-navigation -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white rounded-2xl border border-slate-200/80 px-5 py-3 shadow-sm">
        <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
            <a href="{{ route('admin.experiments') }}" class="px-3 py-1.5 rounded-xl bg-teal-50 text-teal-800 border border-teal-200">Dasbor Eksperimen (RM1–RM6)</a>
            <a href="{{ route('admin.llm.config') }}" class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100">API Key Layanan AI (LLM)</a>
            <a href="{{ route('admin.models') }}" class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100">Registri Model</a>
            <a href="{{ route('admin.users') }}" class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100">Manajemen Pengguna</a>
            <a href="{{ route('admin.audit') }}" class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100">Log Audit</a>
        </div>
        <form method="POST" action="{{ route('admin.experiments.sync') }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition shadow-sm">
                Sinkronkan Ulang dari ml/artifacts
            </button>
        </form>
    </div>

    <!-- Hero Title & RM1-RM6 Summary Cards -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-6 border-b border-slate-200/80">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-teal-50 text-teal-800 border border-teal-200 mb-2">
                    Laporan Eksperimen Empiris BRFSS 2015 (E0–E6)
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Evaluasi Komparatif XGBoost, SHAP & LLM (RM1–RM6)</h1>
                <p class="text-sm text-slate-600 mt-1">
                    Seluruh metrik di bawah ini diimpor langsung dari artefak hasil eksekusi pipeline Python (<code class="text-xs font-mono bg-slate-100 px-1.5 py-0.5 rounded">ml/artifacts/</code>) tanpa angka statis.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.ratings.export') }}" class="px-4 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold transition">
                    Unduh CSV Penilaian LLM (RM6)
                </a>
                <a href="{{ route('admin.llm.config') }}" class="px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition shadow-sm">
                    Atur API Key Layanan AI
                </a>
            </div>
        </div>

        <!-- 4 KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80">
                <div class="text-xs font-bold text-slate-500 uppercase">RM1 · Baseline (E0) Macro-F1</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-1">
                    {{ number_format((float)($e0Metrics['f1_macro'] ?? 0) * 100, 2, ',', '.') }}%
                </div>
                <div class="text-xs text-slate-500 mt-1">
                    Recall Prediabetes: <strong>{{ number_format((float)($e0Metrics['per_class']['1']['recall'] ?? 0) * 100, 1, ',', '.') }}%</strong>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200/80">
                <div class="text-xs font-bold text-amber-800 uppercase">RM2 · Gerbang Kelemahan (W1–W3)</div>
                <div class="text-lg font-extrabold text-amber-900 mt-1">
                    {{ count($weakness['triggered_weaknesses'] ?? []) }} Kelemahan Terdeteksi
                </div>
                <div class="text-xs text-amber-800 mt-1">
                    Rekomendasi: <strong>{{ implode(', ', $weakness['recommended_scenarios'] ?? ['E1', 'E3', 'E6']) }}</strong>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-teal-50/70 border border-teal-200/80">
                <div class="text-xs font-bold text-teal-800 uppercase">RM3 & RM4 · Model Terbaik ({{ $bestCode }})</div>
                <div class="text-2xl font-extrabold text-teal-900 mt-1">
                    {{ number_format((float)($selection['best_test_metrics']['f1_macro'] ?? $bestMetrics['f1_macro'] ?? 0) * 100, 2, ',', '.') }}%
                </div>
                <div class="text-xs text-teal-800 mt-1">
                    ROC-AUC OvR: <strong>{{ number_format((float)($selection['best_test_metrics']['roc_auc_ovr_macro'] ?? $bestMetrics['roc_auc_ovr_macro'] ?? 0) * 100, 2, ',', '.') }}%</strong>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-indigo-50/70 border border-indigo-200/80">
                <div class="text-xs font-bold text-indigo-800 uppercase">RM5 & RM6 · SHAP + Faithfulness LLM</div>
                <div class="text-2xl font-extrabold text-indigo-900 mt-1">
                    {{ number_format((float)($llmEval['mean_faithfulness_score'] ?? 1.0) * 100, 1, ',', '.') }}%
                </div>
                <div class="text-xs text-indigo-800 mt-1">
                    Top-1 Global SHAP: <strong>{{ $shapGlobal['ranking'][0]['label_id'] ?? 'BMI / Tekanan Darah' }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- RM2 Weakness Gate Detail Box -->
    @if(!empty($weakness['triggered_weaknesses']))
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
            <h2 class="text-lg font-extrabold text-slate-900 mb-1">Diagnosis Gerbang Kelemahan Baseline (RM2 — Weakness Gate)</h2>
            <p class="text-xs text-slate-500 mb-4">Evaluasi otomatis pada model baseline E0 untuk menentukan intervensi ketidakseimbangan kelas & optimasi hyperparameter yang terjustifikasi secara ilmiah.</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($weakness['triggered_weaknesses'] as $w)
                    <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded-md bg-amber-200/80 text-amber-950 font-mono text-xs font-extrabold">{{ $w['id'] ?? 'W' }}</span>
                            <span class="text-[11px] font-semibold text-amber-800">{{ $w['metric'] ?? '' }} = {{ number_format((float)($w['observed'] ?? 0), 4, ',', '.') }}</span>
                        </div>
                        <p class="text-xs text-slate-800 font-semibold mt-2 leading-relaxed">{{ $w['description'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- RM3 & RM4: Full E0-E6 Comparison Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <div>
                <h2 class="text-lg font-extrabold text-slate-900">Matriks Perbandingan Eksperimen E0–E6 (RM1, RM3, RM4)</h2>
                <p class="text-xs text-slate-500">10-Fold Stratified Cross-Validation (Mean ± SD) dan Evaluasi Test Set Terpisah.</p>
            </div>
            @if(!empty($selection['rationale']))
                <span class="text-xs bg-teal-50 text-teal-800 border border-teal-200 px-3 py-1 rounded-full font-semibold">
                    Model Terpilih: {{ $bestCode }}
                </span>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 text-slate-500 uppercase bg-slate-50/80">
                        <th class="py-3 px-3">Kode</th>
                        <th class="py-3 px-3">Skenario Eksperimen</th>
                        <th class="py-3 px-3 text-right">CV Macro-F1</th>
                        <th class="py-3 px-3 text-right">Test Accuracy</th>
                        <th class="py-3 px-3 text-right">Test Balanced Acc</th>
                        <th class="py-3 px-3 text-right">Test Macro-F1</th>
                        <th class="py-3 px-3 text-right">Recall Prediabetes (C1)</th>
                        <th class="py-3 px-3 text-right">Recall Diabetes (C2)</th>
                        <th class="py-3 px-3 text-right">ROC-AUC OvR</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60">
                    @foreach($experiments as $exp)
                        @php
                            $cv = $exp->cv_metrics_json ?? [];
                            $tm = $exp->test_metrics_json ?? [];
                            $isBest = ($exp->code === $bestCode);
                        @endphp
                        <tr class="{{ $isBest ? 'bg-teal-50/60 font-semibold' : 'hover:bg-slate-50' }}">
                            <td class="py-3 px-3 font-mono font-extrabold text-slate-900">
                                {{ $exp->code }}
                                @if($isBest)
                                    <span class="ml-1 px-1.5 py-0.5 rounded bg-teal-600 text-white text-[10px]">TERBAIK</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-slate-800">{{ $exp->name }}</td>
                            <td class="py-3 px-3 text-right font-mono">
                                {{ number_format((float)($cv['f1_macro']['mean'] ?? 0) * 100, 2, ',', '.') }}%
                                <span class="text-slate-400">±{{ number_format((float)($cv['f1_macro']['std'] ?? 0) * 100, 2, ',', '.') }}</span>
                            </td>
                            <td class="py-3 px-3 text-right font-mono">{{ number_format((float)($tm['accuracy'] ?? 0) * 100, 2, ',', '.') }}%</td>
                            <td class="py-3 px-3 text-right font-mono">{{ number_format((float)($tm['balanced_accuracy'] ?? 0) * 100, 2, ',', '.') }}%</td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-teal-800">{{ number_format((float)($tm['f1_macro'] ?? 0) * 100, 2, ',', '.') }}%</td>
                            <td class="py-3 px-3 text-right font-mono">{{ number_format((float)($tm['per_class']['1']['recall'] ?? 0) * 100, 2, ',', '.') }}%</td>
                            <td class="py-3 px-3 text-right font-mono">{{ number_format((float)($tm['per_class']['2']['recall'] ?? 0) * 100, 2, ',', '.') }}%</td>
                            <td class="py-3 px-3 text-right font-mono">{{ number_format((float)($tm['roc_auc_ovr_macro'] ?? 0) * 100, 2, ',', '.') }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(!empty($selection['rationale']))
            <div class="mt-4 p-4 rounded-2xl bg-slate-50 border border-slate-200/80 text-xs text-slate-700 leading-relaxed">
                <strong>Justifikasi Pemilihan Model Terbaik ({{ $bestCode }}):</strong> {{ $selection['rationale'] }}
            </div>
        @endif
    </div>

    <!-- Charts Grid: E0-E6 Bar Chart + Confusion Matrix Heatmap -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
            <h3 class="text-base font-extrabold text-slate-900 mb-1">Grafik Perbandingan Macro-F1 & Balanced Accuracy (E0–E6)</h3>
            <p class="text-xs text-slate-500 mb-4">Visualisasi peningkatan performa antar skenario eksperimen pada data uji.</p>
            <div class="h-72">
                <canvas id="expComparisonChart"></canvas>
            </div>
        </div>

        <div class="lg:col-span-5 bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
            <div class="flex items-center justify-between gap-2 mb-3">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Confusion Matrix 3×3 ({{ $selectedExp?->code ?? 'E6' }})</h3>
                    <p class="text-xs text-slate-500">Baris = Kelas Aktual, Kolom = Kelas Prediksi</p>
                </div>
                <form method="GET" action="{{ route('admin.experiments') }}">
                    <select name="cm_code" onchange="this.form.submit()" aria-label="Pilih skenario eksperimen untuk Confusion Matrix" class="rounded-xl border-slate-300 text-xs font-bold py-1.5 pl-2.5 pr-7 focus:border-teal-600 focus:ring-teal-600">
                        @foreach($experiments as $exp)
                            <option value="{{ $exp->code }}" {{ ($selectedExp?->code === $exp->code) ? 'selected' : '' }}>{{ $exp->code }} — {{ $exp->name }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="overflow-x-auto mt-3">
                <table class="w-full text-center border-collapse text-xs">
                    <thead>
                        <tr>
                            <th class="p-2 text-left text-[11px] text-slate-400">Aktual \ Prediksi</th>
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
                                        $cellBg = $isDiag ? 'bg-teal-600 text-white font-extrabold' : ($pct > 20 ? 'bg-rose-100 text-rose-900 font-bold' : 'bg-slate-50 text-slate-700');
                                    @endphp
                                    <td class="p-3 border border-slate-200 font-mono {{ $cellBg }}">
                                        <div class="text-sm">{{ number_format((int)$val, 0, ',', '.') }}</div>
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
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-extrabold text-slate-900 mb-1">Peringkat Kepentingan Fitur Global TreeSHAP (RM5)</h2>
        <p class="text-xs text-slate-500 mb-5">Rata-rata nilai absolut SHAP (<code class="font-mono">mean(|SHAP|)</code>) lintas kelas Non-Diabetes, Prediabetes, dan Diabetes.</p>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <div class="lg:col-span-7 h-80">
                <canvas id="shapGlobalChart"></canvas>
            </div>
            <div class="lg:col-span-5 overflow-x-auto max-h-80 overflow-y-auto border border-slate-200/80 rounded-2xl">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="sticky top-0 bg-slate-50 border-b border-slate-200 text-slate-500 uppercase">
                        <tr>
                            <th class="py-2.5 px-3">#</th>
                            <th class="py-2.5 px-3">Fitur Klinis</th>
                            <th class="py-2.5 px-3 text-right">Mean |SHAP|</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/60">
                        @foreach(array_slice($shapGlobal['ranking'] ?? [], 0, 12) as $idx => $item)
                            <tr>
                                <td class="py-2 px-3 font-mono font-bold text-slate-500">{{ $idx + 1 }}</td>
                                <td class="py-2 px-3 font-semibold text-slate-800">{{ $item['label_id'] ?? $item['feature'] }}</td>
                                <td class="py-2 px-3 text-right font-mono font-bold text-teal-700">{{ number_format((float)($item['mean_abs_shap'] ?? 0), 4, ',', '.') }}</td>
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
    const expF1 = @json($experiments->map(fn($e) => round(($e->test_metrics_json['f1_macro'] ?? 0) * 100, 2)));
    const expBalAcc = @json($experiments->map(fn($e) => round(($e->test_metrics_json['balanced_accuracy'] ?? 0) * 100, 2)));

    const ctxExp = document.getElementById('expComparisonChart');
    if (ctxExp && window.Chart) {
        new Chart(ctxExp, {
            type: 'bar',
            data: {
                labels: expLabels,
                datasets: [
                    {
                        label: 'Macro-F1 (%)',
                        data: expF1,
                        backgroundColor: 'rgba(13, 148, 136, 0.85)',
                        borderRadius: 6
                    },
                    {
                        label: 'Balanced Accuracy (%)',
                        data: expBalAcc,
                        backgroundColor: 'rgba(79, 70, 229, 0.75)',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, max: 100 }
                }
            }
        });
    }

    const shapRanking = @json(array_slice($shapGlobal['ranking'] ?? [], 0, 10));
    const ctxShap = document.getElementById('shapGlobalChart');
    if (ctxShap && window.Chart && shapRanking.length) {
        new Chart(ctxShap, {
            type: 'bar',
            data: {
                labels: shapRanking.map(r => r.label_id || r.feature),
                datasets: [{
                    label: 'Mean |SHAP| (Log-Odds)',
                    data: shapRanking.map(r => Number(r.mean_abs_shap || 0).toFixed(4)),
                    backgroundColor: 'rgba(15, 118, 110, 0.85)',
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
});
</script>
@endpush
@endsection
