@extends('layouts.app')

@section('title', 'Laporan Hasil SPK Klasifikasi Status Diabetes')

@section('content')
@php
    $probs = $prediction->probabilities ?? ['No Diabetes' => 0, 'Prediabetes' => 0, 'Diabetes' => 0];
    $shapTop = $prediction->shap_local['top'] ?? [];
    $inputSummary = $prediction->input_summary ?? [];
    $statusTheme = match($prediction->label_code) {
        0 => ['bg' => 'bg-emerald-50 border-emerald-300', 'badge' => 'bg-emerald-600 text-white', 'text' => 'text-emerald-950'],
        1 => ['bg' => 'bg-amber-50 border-amber-300', 'badge' => 'bg-amber-500 text-slate-950', 'text' => 'text-amber-950'],
        default => ['bg' => 'bg-rose-50 border-rose-300', 'badge' => 'bg-rose-600 text-white', 'text' => 'text-rose-950'],
    };
@endphp

<div class="space-y-6">
    <!-- 1. System Disclaimer Banner (Deterministic Layer 6) -->
    <div class="rounded-2xl bg-amber-50 border border-amber-300 p-4 sm:p-5 flex items-start gap-3.5 shadow-2xs">
        <svg aria-hidden="true" class="w-6 h-6 text-amber-700 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div class="text-xs sm:text-sm text-amber-950 leading-relaxed">
            <strong class="font-extrabold block mb-0.5">Disclaimer Sistem Pendukung Keputusan (Deterministik):</strong>
            {{ $disclaimer }}
        </div>
    </div>

    <!-- Header Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono">
                <span>ID Kasus: {{ $screening->patient_label ?: ('SCR-' . strtoupper(substr($screening->id, 0, 8))) }}</span>
                <span>•</span>
                <span>{{ $screening->created_at?->format('d M Y, H:i') }} WIB</span>
                <span>•</span>
                <span>Model: {{ $prediction->model_version_tag }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 mt-0.5">Laporan Hasil Skrining Status Diabetes (SPK)</h1>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('skrining.pdf', $screening->id) }}" class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center gap-2 shadow-xs transition">
                <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Unduh Laporan PDF</span>
            </a>
            <a href="{{ route('skrining.create') }}" class="px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs transition">
                + Skrining Ulang
            </a>
        </div>
    </div>

    <!-- 2. Classification Status Card & 3-Class Probabilities -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-5 rounded-2xl border p-6 flex flex-col justify-between space-y-4 {{ $statusTheme['bg'] }}">
            <div class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Hasil Klasifikasi Model Terbaik (XGBoost)</span>
                <p class="text-xs text-slate-700">Model memperkirakan profil indikator kesehatan Anda paling mirip dengan kelompok:</p>
                <div class="pt-1 flex items-center gap-3">
                    <span class="px-4 py-2 rounded-xl text-lg sm:text-xl font-extrabold shadow-xs {{ $statusTheme['badge'] }}">
                        {{ $prediction->label_id ?? $prediction->label }} ({{ $prediction->label }})
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2 border-t border-black/10 text-xs">
                <div>
                    <span class="text-slate-600 block">Probabilitas Kelas Teratas</span>
                    <strong class="text-lg font-extrabold {{ $statusTheme['text'] }}">
                        {{ number_format(((float) ($probs[$prediction->label] ?? 0)) * 100, 1, ',', '.') }}%
                    </strong>
                </div>
                <div>
                    <span class="text-slate-600 block">Tingkat Keyakinan Model</span>
                    <strong class="text-base font-extrabold capitalize {{ $statusTheme['text'] }}">
                        {{ $prediction->confidence_note }}
                    </strong>
                </div>
            </div>

            @if(!empty($prediction->warnings))
                <div class="p-3 rounded-xl bg-white/80 border border-amber-300 text-xs text-amber-900">
                    @foreach($prediction->warnings as $w)
                        <p>• {{ $w }}</p>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- 3-Class Probability Chart -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">Distribusi Probabilitas 3 Kelas Status Diabetes</h2>
                    <p class="text-xs text-slate-500">Keluaran fungsi <code>multi:softprob</code> pada model XGBoost.</p>
                </div>
            </div>
            <div class="h-44">
                <canvas id="probChart" aria-label="Grafik batang probabilitas tiga kelas status diabetes"></canvas>
            </div>
            <div class="grid grid-cols-3 gap-2 text-center text-xs pt-1">
                <div class="p-2.5 rounded-xl bg-emerald-50 border border-emerald-200">
                    <span class="block text-emerald-800 font-semibold">Tidak Diabetes (0)</span>
                    <strong class="text-sm font-extrabold text-emerald-950">{{ number_format(((float) ($probs['No Diabetes'] ?? 0)) * 100, 1, ',', '.') }}%</strong>
                </div>
                <div class="p-2.5 rounded-xl bg-amber-50 border border-amber-200">
                    <span class="block text-amber-900 font-semibold">Prediabetes (1)</span>
                    <strong class="text-sm font-extrabold text-amber-950">{{ number_format(((float) ($probs['Prediabetes'] ?? 0)) * 100, 1, ',', '.') }}%</strong>
                </div>
                <div class="p-2.5 rounded-xl bg-rose-50 border border-rose-200">
                    <span class="block text-rose-800 font-semibold">Diabetes (2)</span>
                    <strong class="text-sm font-extrabold text-rose-950">{{ number_format(((float) ($probs['Diabetes'] ?? 0)) * 100, 1, ',', '.') }}%</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Local SHAP Feature Contributions (RM5) -->
    <section aria-labelledby="shap-local-heading" class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-2xs space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 pb-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-sky-700">Explainable AI (TreeSHAP Lokal — RM5)</span>
                <h2 id="shap-local-heading" class="text-lg sm:text-xl font-extrabold text-slate-900">Faktor Indikator yang Memengaruhi Prediksi ({{ $prediction->label_id ?? $prediction->label }})</h2>
                <p class="text-xs text-slate-600">
                    Menampilkan Top-{{ count($shapTop) }} fitur dengan nilai mutlak <code>|SHAP|</code> terbesar terhadap kelas <strong>{{ $prediction->label }}</strong> (Nilai dasar / <em>base value</em> log-odds: <code class="font-mono">{{ $prediction->base_value }}</code>).
                </p>
            </div>
            <div class="flex items-center gap-3 text-xs font-bold">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-rose-50 text-rose-800 border border-rose-200">▲ Meningkatkan Kecenderungan (SHAP &gt; 0)</span>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-teal-50 text-teal-800 border border-teal-200">▼ Menurunkan Kecenderungan (SHAP &lt; 0)</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
            <div class="lg:col-span-6 h-64">
                <canvas id="shapChart" aria-label="Grafik kontribusi fitur SHAP lokal"></canvas>
            </div>

            <div class="lg:col-span-6 space-y-2.5">
                @foreach($shapTop as $item)
                    @php $isUp = ($item['direction'] ?? 'naik') === 'naik'; @endphp
                    <div class="p-3.5 rounded-xl border flex items-center justify-between gap-3 {{ $isUp ? 'bg-rose-50/50 border-rose-200' : 'bg-teal-50/50 border-teal-200' }}">
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-mono font-bold px-1.5 py-0.5 rounded bg-white border border-slate-200 text-slate-700">#{{ $item['rank'] }}</span>
                                <strong class="text-xs sm:text-sm font-bold text-slate-900">{{ $item['label_id'] }}</strong>
                            </div>
                            <p class="text-xs text-slate-600">Nilai input Anda: <strong>{{ $item['display'] }}</strong></p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg font-mono text-xs font-extrabold {{ $isUp ? 'bg-rose-100 text-rose-900' : 'bg-teal-100 text-teal-900' }}">
                                {{ $isUp ? '▲ +' : '▼ ' }}{{ number_format((float) $item['shap'], 4) }}
                            </span>
                            <span class="block text-[11px] font-semibold mt-0.5 {{ $isUp ? 'text-rose-700' : 'text-teal-700' }}">
                                {{ $isUp ? 'Mendorong ke ' . ($prediction->label_id ?? $prediction->label) : 'Menahan / Menurunkan' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- 4. Guardrailed LLM Natural Language Interpretation (RM6) -->
    <section
        aria-labelledby="llm-narrative-heading"
        x-data="{
            status: '{{ $interpretation?->status ?? 'done' }}',
            narrative: @js($interpretation?->narrative ?? []),
            validation: @js($interpretation?->validation ?? []),
            used_fallback: @js((bool) ($interpretation?->used_fallback ?? true)),
            provider: '{{ $interpretation?->provider ?? 'template_fallback' }}',
            model_name: '{{ $interpretation?->model_name ?? 'deterministic-v1' }}',
            prompt_version: '{{ $interpretation?->prompt_version ?? 'p1.0' }}',
            latency_ms: {{ (int) ($interpretation?->latency_ms ?? 0) }},
            init() {
                if (this.status === 'pending') {
                    const timer = setInterval(async () => {
                        const res = await fetch('{{ route('skrining.narasi', $screening->id) }}');
                        if (res.ok) {
                            const data = await res.json();
                            if (data.status === 'done') {
                                this.status = data.status;
                                this.narrative = data.narrative;
                                this.validation = data.validation;
                                this.used_fallback = data.used_fallback;
                                this.provider = data.provider;
                                this.model_name = data.model_name;
                                this.latency_ms = data.latency_ms;
                                clearInterval(timer);
                            }
                        }
                    }, 2000);
                }
            }
        }"
        class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-2xs space-y-6"
    >
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-purple-700">Interpretasi Bahasa Alami (LLM + Guardrail — RM6)</span>
                    <span
                        class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border"
                        :class="used_fallback ? 'bg-slate-100 text-slate-700 border-slate-300' : 'bg-purple-100 text-purple-900 border-purple-300'"
                        x-text="used_fallback ? 'Narasi Otomatis (Templat Deterministik)' : ('LLM Aktif: ' + provider + ' / ' + model_name)"
                    ></span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                        ✓ Tervalidasi Guardrail Fakta (0 Halusinasi)
                    </span>
                </div>
                <h2 id="llm-narrative-heading" class="text-lg sm:text-xl font-extrabold text-slate-900 mt-1">Penjelasan Naratif Hasil Skrining & Saran Gaya Hidup</h2>
            </div>

            <form action="{{ route('skrining.regenerate', $screening->id) }}" method="POST">
                @csrf
                <button type="submit" class="px-3.5 py-2 rounded-xl border border-slate-300 hover:bg-slate-50 text-xs font-bold text-slate-700 transition">
                    ↻ Susun Ulang Narasi AI
                </button>
            </form>
        </div>

        <!-- Skeleton Loading State -->
        <div x-show="status === 'pending'" class="space-y-3 animate-pulse py-4">
            <div class="h-4 bg-slate-200 rounded w-3/4"></div>
            <div class="h-4 bg-slate-200 rounded w-full"></div>
            <div class="h-4 bg-slate-200 rounded w-5/6"></div>
            <p class="text-xs text-slate-500 pt-2">Sedang menyusun dan memvalidasi narasi penjelasan dari hasil SHAP...</p>
        </div>

        <!-- Rendered Narrative -->
        <div x-show="status === 'done'" class="space-y-6">
            <!-- Ringkasan -->
            <div class="p-4 sm:p-5 rounded-2xl bg-slate-50 border border-slate-200/90 text-sm sm:text-base text-slate-800 leading-relaxed font-medium" x-text="narrative?.ringkasan"></div>

            <!-- Faktor yang Meningkatkan & Menurunkan -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="p-5 rounded-2xl bg-rose-50/40 border border-rose-200 space-y-3">
                    <h3 class="text-sm font-extrabold text-rose-950 flex items-center gap-2">
                        <span>▲ Faktor yang Meningkatkan Kecenderungan Kelas Prediksi</span>
                    </h3>
                    <ul class="space-y-2.5 text-xs sm:text-sm text-slate-700 leading-relaxed">
                        <template x-for="item in (narrative?.faktor_yang_meningkatkan || [])" :key="item.feature">
                            <li class="flex items-start gap-2">
                                <span class="font-mono font-bold text-rose-700 shrink-0" x-text="'[' + item.feature + ']'"></span>
                                <span x-text="item.penjelasan"></span>
                            </li>
                        </template>
                        <li x-show="!(narrative?.faktor_yang_meningkatkan?.length)" class="text-slate-500 italic">Tidak ada faktor pendorong utama pada Top-K SHAP.</li>
                    </ul>
                </div>

                <div class="p-5 rounded-2xl bg-teal-50/40 border border-teal-200 space-y-3">
                    <h3 class="text-sm font-extrabold text-teal-950 flex items-center gap-2">
                        <span>▼ Faktor yang Menahan / Menurunkan Kecenderungan</span>
                    </h3>
                    <ul class="space-y-2.5 text-xs sm:text-sm text-slate-700 leading-relaxed">
                        <template x-for="item in (narrative?.faktor_yang_menurunkan || [])" :key="item.feature">
                            <li class="flex items-start gap-2">
                                <span class="font-mono font-bold text-teal-700 shrink-0" x-text="'[' + item.feature + ']'"></span>
                                <span x-text="item.penjelasan"></span>
                            </li>
                        </template>
                        <li x-show="!(narrative?.faktor_yang_menurunkan?.length)" class="text-slate-500 italic">Tidak ada faktor penahan pada Top-K SHAP.</li>
                    </ul>
                </div>
            </div>

            <!-- Saran Umum & Kapan ke Tenaga Kesehatan -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                <div class="md:col-span-7 p-5 rounded-2xl bg-sky-50/50 border border-sky-200 space-y-2.5">
                    <h3 class="text-sm font-extrabold text-sky-950">Saran Gaya Hidup Sehat Umum (Non-Medikamentosa)</h3>
                    <ul class="list-disc list-inside space-y-1.5 text-xs sm:text-sm text-slate-700 leading-relaxed">
                        <template x-for="(saran, idx) in (narrative?.saran_umum || [])" :key="idx">
                            <li x-text="saran"></li>
                        </template>
                    </ul>
                </div>

                <div class="md:col-span-5 p-5 rounded-2xl bg-indigo-50/50 border border-indigo-200 space-y-2">
                    <h3 class="text-sm font-extrabold text-indigo-950">Kapan Perlu ke Dokter / Fasilitas Kesehatan?</h3>
                    <p class="text-xs sm:text-sm text-indigo-950 leading-relaxed" x-text="narrative?.kapan_ke_tenaga_kesehatan"></p>
                </div>
            </div>

            <!-- Guardrail Checks Metadata Footer -->
            <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500">
                <div class="flex flex-wrap items-center gap-3">
                    <span>Prompt: <strong class="font-mono" x-text="prompt_version"></strong></span>
                    <span>•</span>
                    <span>Konsistensi Label: <strong>Lulus</strong></span>
                    <span>•</span>
                    <span>Whitelist 21 Fitur: <strong>Lulus</strong></span>
                    <span>•</span>
                    <span>Bebas Frasa Diagnosis & Obat: <strong>Lulus</strong></span>
                </div>
                <span class="font-mono" x-text="'Waktu proses: ' + latency_ms + ' ms'"></span>
            </div>
        </div>
    </section>

    <!-- Nakes Inline Rating Form (FR-N3 — RM6) -->
    @if($interpretation && (auth()->user()->isNakes() || auth()->user()->isAdmin()))
        <section aria-labelledby="rating-form-heading" class="bg-indigo-50/60 rounded-2xl border border-indigo-200 p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-700">Evaluasi Pakar / Tenaga Medis (RM6)</span>
                    <h2 id="rating-form-heading" class="text-base font-extrabold text-slate-900">Beri Penilaian Kualitas Narasi LLM pada Kasus Ini (Skala Likert 1–5)</h2>
                </div>
                @if($myRating)
                    <span class="px-3 py-1 rounded-full bg-indigo-600 text-white text-xs font-bold">
                        Skor Anda: {{ $myRating->averageScore() }} / 5,0
                    </span>
                @endif
            </div>

            <form action="{{ route('nakes.ratings.store', $interpretation->id) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                @csrf
                @foreach([
                    'akurasi' => '1. Akurasi thd Model & SHAP',
                    'kejelasan' => '2. Kejelasan Bahasa',
                    'kegunaan' => '3. Kegunaan Praktis SPK',
                    'keamanan' => '4. Keamanan (Tanpa Klaim Diagnosis)',
                ] as $field => $label)
                    <div>
                        <label for="rate_{{ $field }}" class="block text-xs font-bold text-slate-800 mb-1">{{ $label }}</label>
                        <select id="rate_{{ $field }}" name="{{ $field }}" class="w-full rounded-xl border border-indigo-300 bg-white px-3 py-2 text-xs font-bold">
                            @for($s = 5; $s >= 1; $s--)
                                <option value="{{ $s }}" {{ ($myRating?->$field ?? 5) == $s ? 'selected' : '' }}>{{ $s }} — {{ $s == 5 ? 'Sangat Baik' : ($s == 4 ? 'Baik' : ($s == 3 ? 'Cukup' : ($s == 2 ? 'Kurang' : 'Sangat Kurang'))) }}</option>
                            @endfor
                        </select>
                    </div>
                @endforeach
                <div class="sm:col-span-3">
                    <label for="komentar" class="block text-xs font-bold text-slate-800 mb-1">Catatan / Masukan Klinis (Opsional)</label>
                    <input type="text" id="komentar" name="komentar" value="{{ $myRating?->komentar }}" placeholder="Contoh: Penjelasan arah nilai SHAP sudah akurat dan aman bagi pasien." class="w-full rounded-xl border border-indigo-300 bg-white px-3.5 py-2 text-xs">
                </div>
                <div>
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs transition tap-target">
                        Simpan Penilaian Pakar
                    </button>
                </div>
            </form>
        </section>
    @endif

    <!-- 5. Collapsible 21 Input Features Summary -->
    <section x-data="{ open: false }" class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
        <button
            type="button"
            @click="open = !open"
            :aria-expanded="open.toString()"
            aria-controls="input-21-panel"
            class="w-full p-5 text-left flex items-center justify-between hover:bg-slate-50 transition"
        >
            <div>
                <h2 class="text-base font-extrabold text-slate-900">Ringkasan 21 Indikator Masukan Pengguna</h2>
                <p class="text-xs text-slate-500">Klik untuk melihat seluruh nilai fitur BRFSS yang digunakan pada prediksi ini.</p>
            </div>
            <span class="text-xs font-bold text-teal-700" x-text="open ? 'Sembunyikan ▲' : 'Tampilkan 21 Fitur ▼'"></span>
        </button>

        <div id="input-21-panel" x-show="open" x-cloak class="p-5 pt-0 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 text-xs">
            @foreach($inputSummary as $inp)
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex flex-col justify-between">
                    <span class="text-slate-500 font-medium">{{ $inp['label_id'] }} (<code class="font-mono">{{ $inp['feature'] }}</code>)</span>
                    <strong class="text-slate-900 text-sm mt-1">{{ $inp['display'] }}</strong>
                </div>
            @endforeach
        </div>
    </section>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const probs = @js($probs);
    const shapTop = @js($shapTop);

    // 1. Probabilities Bar Chart
    const probCtx = document.getElementById('probChart');
    if (probCtx) {
        new Chart(probCtx, {
            type: 'bar',
            data: {
                labels: ['Tidak Diabetes (0)', 'Prediabetes (1)', 'Diabetes (2)'],
                datasets: [{
                    label: 'Probabilitas (%)',
                    data: [
                        ((probs['No Diabetes'] || 0) * 100).toFixed(1),
                        ((probs['Prediabetes'] || 0) * 100).toFixed(1),
                        ((probs['Diabetes'] || 0) * 100).toFixed(1)
                    ],
                    backgroundColor: ['#10b981', '#f59e0b', '#f43f5e'],
                    borderRadius: 8,
                    barThickness: 36
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { min: 0, max: 100, ticks: { callback: v => v + '%' } }
                }
            }
        });
    }

    // 2. Local SHAP Horizontal Bar Chart
    const shapCtx = document.getElementById('shapChart');
    if (shapCtx && shapTop.length) {
        new Chart(shapCtx, {
            type: 'bar',
            data: {
                labels: shapTop.map(i => (i.direction === 'naik' ? '▲ ' : '▼ ') + i.feature + ' (' + i.raw_value + ')'),
                datasets: [{
                    label: 'Nilai SHAP (Log-Odds)',
                    data: shapTop.map(i => i.shap),
                    backgroundColor: shapTop.map(i => i.direction === 'naik' ? '#f43f5e' : '#0d9488'),
                    borderRadius: 6,
                    barThickness: 22
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { title: { display: true, text: 'Kontribusi Nilai SHAP terhadap Kelas Prediksi' } }
                }
            }
        });
    }
});
</script>
@endpush
@endsection

