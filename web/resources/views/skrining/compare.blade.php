@extends('layouts.app')

@section('title', 'Perbandingan Skrining Antar-Waktu')
@section('page_title', 'Bandingkan Skrining')

@section('content')
@php
    $isSameScreening = ($left && $right && $left->id === $right->id);
    $classMeta = [
        'No Diabetes' => ['label' => 'Tidak Diabetes', 'bar' => 'bg-emerald-500', 'text' => 'text-emerald-700', 'bg' => 'bg-emerald-50 border-emerald-200'],
        'Prediabetes' => ['label' => 'Prediabetes', 'bar' => 'bg-amber-500', 'text' => 'text-amber-800', 'bg' => 'bg-amber-50 border-amber-200'],
        'Diabetes' => ['label' => 'Diabetes', 'bar' => 'bg-rose-500', 'text' => 'text-rose-700', 'bg' => 'bg-rose-50 border-rose-200'],
    ];
@endphp

<div class="space-y-6">
    <!-- Header & Proportional 1-Row Selector Toolbar -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs space-y-5">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900">Perbandingan Skrining Antar-Waktu</h1>
            <p class="text-xs sm:text-sm text-slate-600 mt-0.5">Evaluasi pergeseran probabilitas 3 kelas, indikator klinis utama, dan faktor kontribusi TreeSHAP antara dua waktu skrining.</p>
        </div>

        <form action="{{ route('skrining.compare') }}" method="GET" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-3 items-end">
            <div class="min-w-0">
                <label for="left_id" class="block text-xs font-bold text-slate-700 mb-1.5">Skrining A (Acuan)</label>
                <select id="left_id" name="left_id" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs sm:text-sm font-medium text-slate-800">
                    @foreach($allScreenings as $s)
                        <option value="{{ $s->id }}" {{ ($left?->id === $s->id) ? 'selected' : '' }}>
                            {{ $s->patient_label ?: ('SCR-' . strtoupper(substr($s->id, 0, 8))) }} — {{ $s->created_at?->format('d M Y H:i') }} ({{ $s->prediction?->label_id ?? $s->prediction?->label }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-0">
                <label for="right_id" class="block text-xs font-bold text-slate-700 mb-1.5">Skrining B (Pembanding)</label>
                <select id="right_id" name="right_id" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs sm:text-sm font-medium text-slate-800">
                    @foreach($allScreenings as $s)
                        <option value="{{ $s->id }}" {{ ($right?->id === $s->id) ? 'selected' : '' }}>
                            {{ $s->patient_label ?: ('SCR-' . strtoupper(substr($s->id, 0, 8))) }} — {{ $s->created_at?->format('d M Y H:i') }} ({{ $s->prediction?->label_id ?? $s->prediction?->label }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="w-full md:w-auto px-5 py-2.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-semibold text-sm whitespace-nowrap shadow-xs transition tap-target">
                    Bandingkan
                </button>
            </div>
        </form>
    </div>

    <!-- Same ID Warning Info Banner -->
    @if($isSameScreening)
        <div role="status" class="rounded-2xl bg-sky-50 border border-sky-200 px-4 py-3.5 flex items-start gap-3 text-sky-900">
            <svg aria-hidden="true" class="w-5 h-5 text-sky-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-xs sm:text-sm leading-relaxed">
                <strong>Catatan Perbandingan:</strong> Anda memilih dua ID skrining yang sama persis (<code class="font-mono font-semibold">{{ $left->patient_label ?: ('SCR-' . strtoupper(substr($left->id, 0, 8))) }}</code>). Pilih dua skrining berbeda untuk melihat indikator selisih (Δ) probabilitas dan perubahan faktor risiko.
            </div>
        </div>
    @endif

    @if($left && $right)
        <!-- Clear SHAP Legend Bar -->
        <div class="bg-white rounded-2xl px-5 py-3.5 border border-slate-200/90 shadow-2xs flex flex-wrap items-center justify-between gap-3 text-xs">
            <span class="font-bold text-slate-700">Legenda Kontribusi Fitur TreeSHAP:</span>
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-50 text-rose-800 border border-rose-200 font-semibold">
                    <span class="font-mono">▲ +</span> Faktor Pendorong Risiko / Kelas Prediksi
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold">
                    <span class="font-mono">▼ −</span> Faktor Protektif / Penurun Risiko
                </span>
            </div>
        </div>

        <!-- Side-by-Side Comparison Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach([['tag' => 'Skrining A (Acuan)', 'item' => $left, 'other' => null], ['tag' => 'Skrining B (Pembanding)', 'item' => $right, 'other' => $isSameScreening ? null : $left]] as $col)
                @php
                    $item = $col['item'];
                    $other = $col['other'];
                    $p = $item->prediction;
                    $otherP = $other?->prediction;
                    $statusBadge = match($p?->label_code) {
                        0 => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                        1 => 'bg-amber-50 text-amber-900 border-amber-200',
                        default => 'bg-rose-50 text-rose-800 border-rose-200',
                    };
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-2xs flex flex-col justify-between space-y-5">
                    <div class="space-y-5">
                        <!-- Card Header -->
                        <div class="flex items-start justify-between gap-3 pb-4 border-b border-slate-100">
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-teal-700">{{ $col['tag'] }}</span>
                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                    <span class="font-mono text-xs font-bold text-slate-600">{{ $item->patient_label ?: ('SCR-' . strtoupper(substr($item->id, 0, 8))) }}</span>
                                    <span class="text-slate-300">·</span>
                                    <span class="text-xs text-slate-500">{{ $item->created_at?->format('d M Y, H:i') }}</span>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border whitespace-nowrap {{ $statusBadge }}">
                                {{ $p?->label_id ?? $p?->label }}
                            </span>
                        </div>

                        <!-- Visual Progress Bars for 3-Class Probabilities + Delta Indicator -->
                        <div class="space-y-3">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Probabilitas 3 Kelas Status Diabetes</h2>
                            @foreach($classMeta as $k => $meta)
                                @php
                                    $val = ((float) ($p?->probabilities[$k] ?? 0)) * 100;
                                    $otherVal = $otherP ? (((float) ($otherP->probabilities[$k] ?? 0)) * 100) : null;
                                    $delta = ($otherVal !== null) ? round($val - $otherVal, 1) : null;
                                @endphp
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between gap-2 text-xs">
                                        <span class="font-semibold text-slate-700">{{ $meta['label'] }}</span>
                                        <div class="flex items-center gap-2">
                                            <strong class="font-mono font-bold text-slate-900">{{ number_format($val, 1, ',', '.') }}%</strong>
                                            @if($delta !== null)
                                                @php
                                                    $deltaClass = $delta > 0.05
                                                        ? ($k === 'No Diabetes' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200')
                                                        : ($delta < -0.05
                                                            ? ($k === 'No Diabetes' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200')
                                                            : 'bg-slate-100 text-slate-600 border-slate-200');
                                                @endphp
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-md border text-[11px] font-mono font-bold {{ $deltaClass }}">
                                                    Δ {{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 1, ',', '.') }}%
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="h-2.5 w-full rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full rounded-full transition-all {{ $meta['bar'] }}" style="width: {{ min(100, max(2, $val)) }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Key Biometric Summary + BMI Delta -->
                        @php
                            $bmiVal = (float) ($item->input['BMI'] ?? 0);
                            $otherBmi = $other ? (float) ($other->input['BMI'] ?? 0) : null;
                            $bmiDelta = ($otherBmi !== null) ? round($bmiVal - $otherBmi, 1) : null;
                        @endphp
                        <div class="grid grid-cols-3 gap-2.5 text-xs">
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                                <span class="text-slate-500 block">BMI</span>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <strong class="font-mono text-sm font-bold text-slate-900">{{ number_format($bmiVal, 1, ',', '.') }}</strong>
                                    @if($bmiDelta !== null && abs($bmiDelta) >= 0.1)
                                        <span class="font-mono text-[10px] font-bold {{ $bmiDelta > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                            ({{ $bmiDelta > 0 ? '+' : '' }}{{ number_format($bmiDelta, 1, ',', '.') }})
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                                <span class="text-slate-500 block">Hipertensi</span>
                                <strong class="text-sm font-bold text-slate-900 mt-0.5 block">{{ !empty($item->input['HighBP']) ? 'Ya' : 'Tidak' }}</strong>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                                <span class="text-slate-500 block">Aktivitas Fisik</span>
                                <strong class="text-sm font-bold text-slate-900 mt-0.5 block">{{ !empty($item->input['PhysActivity']) ? 'Aktif' : 'Kurang' }}</strong>
                            </div>
                        </div>

                        <!-- Top-4 Local SHAP Contributions -->
                        <div class="space-y-2 text-xs">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Top Kontribusi TreeSHAP Lokal</h3>
                            @foreach(array_slice($p?->shap_local['top'] ?? [], 0, 4) as $sh)
                                @php $isRiskDriver = ($sh['direction'] ?? 'naik') === 'naik'; @endphp
                                <div class="p-3 rounded-xl border flex items-center justify-between gap-2 {{ $isRiskDriver ? 'bg-rose-50/40 border-rose-200/80' : 'bg-emerald-50/40 border-emerald-200/80' }}">
                                    <div class="min-w-0">
                                        <span class="font-bold text-slate-900 block truncate">{{ $sh['label_id'] }}</span>
                                        <span class="text-[11px] text-slate-600">{{ $sh['display'] }}</span>
                                    </div>
                                    <span class="font-mono font-bold px-2 py-0.5 rounded-lg shrink-0 {{ $isRiskDriver ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800' }}">
                                        {{ $isRiskDriver ? '▲ +' : '▼ ' }}{{ number_format((float) $sh['shap'], 3) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                        <a href="{{ route('skrining.show', $item->id) }}" class="text-xs font-bold text-teal-700 hover:underline">
                            Buka Laporan SPK Lengkap →
                        </a>
                        <a href="{{ route('skrining.pdf', $item->id) }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
                            Unduh PDF
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white rounded-2xl border border-slate-200/90 p-10 text-center space-y-3">
            <p class="text-sm font-semibold text-slate-700">Belum ada cukup riwayat skrining untuk dibandingkan.</p>
            <a href="{{ route('skrining.create') }}" class="inline-flex px-4 py-2.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-semibold text-xs transition">
                + Buat Skrining Baru
            </a>
        </div>
    @endif
</div>
@endsection
