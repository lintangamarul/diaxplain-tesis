@extends('layouts.app')

@section('title', 'Laporan Hasil SPK Klasifikasi Status Diabetes')
@section('page_title', 'Detail Laporan SPK')

@section('content')
@php
    $probs = $prediction->probabilities ?? ['No Diabetes' => 0, 'Prediabetes' => 0, 'Diabetes' => 0];
    $shapTop = $prediction->shap_local['top'] ?? [];
    $inputSummary = $prediction->input_summary ?? [];
    $statusTheme = match($prediction->label_code) {
        0 => ['bg' => 'bg-emerald-50/70 border-emerald-200', 'badge' => 'bg-emerald-600 text-white', 'text' => 'text-emerald-950'],
        1 => ['bg' => 'bg-amber-50/80 border-amber-200', 'badge' => 'bg-amber-500 text-slate-950', 'text' => 'text-amber-950'],
        default => ['bg' => 'bg-rose-50/70 border-rose-200', 'badge' => 'bg-rose-600 text-white', 'text' => 'text-rose-950'],
    };
@endphp

<div class="space-y-6">
    <!-- Top Header Action Bar -->
    <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/90 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 font-mono">
                <span class="font-bold text-slate-800">{{ $screening->patient_label ?: ('SCR-' . strtoupper(substr($screening->id, 0, 8))) }}</span>
                <span>·</span>
                <span>{{ $screening->created_at?->format('d M Y, H:i') }} WIB</span>
                <span>·</span>
                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700">{{ $prediction->model_version_tag }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 mt-1">Laporan Hasil Skrining Status Diabetes (SPK)</h1>
        </div>
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <a href="{{ route('skrining.pdf', $screening->id) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs sm:text-sm whitespace-nowrap shadow-xs transition">
                <svg aria-hidden="true" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Unduh PDF</span>
            </a>
            <a href="{{ route('skrining.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-semibold text-xs sm:text-sm whitespace-nowrap transition">
                + Skrining Ulang
            </a>
        </div>
    </div>

    <!-- Top Summary Band: Prediction Status Card + 3-Class Probabilities -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-5 rounded-2xl border p-6 flex flex-col justify-between space-y-5 shadow-2xs {{ $statusTheme['bg'] }}">
            <div class="space-y-2.5">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Hasil Klasifikasi Model Terbaik (XGBoost)</span>
                <p class="text-xs text-slate-700">Profil 21 indikator kesehatan paling menyerupai kelompok:</p>
                <div class="pt-1">
                    <span class="inline-flex items-center px-4 py-2 rounded-xl text-lg sm:text-xl font-extrabold shadow-xs {{ $statusTheme['badge'] }}">
                        {{ $prediction->label_id ?? $prediction->label }} ({{ $prediction->label }})
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-4 border-t border-black/10 text-xs">
                <div>
                    <span class="text-slate-600 block">Probabilitas Teratas</span>
                    <strong class="text-xl font-mono font-extrabold {{ $statusTheme['text'] }}">
                        {{ number_format(((float) ($probs[$prediction->label] ?? 0)) * 100, 1, ',', '.') }}%
                    </strong>
                </div>
                <div>
                    <span class="text-slate-600 block">Keyakinan Model</span>
                    <strong class="text-base font-extrabold capitalize {{ $statusTheme['text'] }}">
                        {{ $prediction->confidence_note }}
                    </strong>
                </div>
            </div>

            @if(!empty($prediction->warnings))
                <div class="p-3 rounded-xl bg-white/85 border border-amber-300 text-xs text-amber-900 space-y-1">
                    @foreach($prediction->warnings as $w)
                        <p>• {{ $w }}</p>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/90 p-6 shadow-2xs space-y-4">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Distribusi Probabilitas 3 Kelas Status Diabetes</h2>
                    <p class="text-xs text-slate-500">Keluaran probabilitas <code class="font-mono">multi:softprob</code> XGBoost.</p>
                </div>
            </div>
            <div class="h-40">
                <canvas id="probChart" aria-label="Grafik batang probabilitas tiga kelas status diabetes"></canvas>
            </div>
            <div class="grid grid-cols-3 gap-2.5 text-center text-xs">
                <div class="p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-200/80">
                    <span class="block text-emerald-800 font-semibold">Tidak Diabetes (0)</span>
                    <strong class="text-sm font-mono font-extrabold text-emerald-950">{{ number_format(((float) ($probs['No Diabetes'] ?? 0)) * 100, 1, ',', '.') }}%</strong>
                </div>
                <div class="p-2.5 rounded-xl bg-amber-50/70 border border-amber-200/80">
                    <span class="block text-amber-900 font-semibold">Prediabetes (1)</span>
                    <strong class="text-sm font-mono font-extrabold text-amber-950">{{ number_format(((float) ($probs['Prediabetes'] ?? 0)) * 100, 1, ',', '.') }}%</strong>
                </div>
                <div class="p-2.5 rounded-xl bg-rose-50/70 border border-rose-200/80">
                    <span class="block text-rose-800 font-semibold">Diabetes (2)</span>
                    <strong class="text-sm font-mono font-extrabold text-rose-950">{{ number_format(((float) ($probs['Diabetes'] ?? 0)) * 100, 1, ',', '.') }}%</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Clinical Disclaimer Strip (Deterministic Layer 6) -->
    <div class="rounded-2xl bg-amber-50/80 border border-amber-200/90 px-5 py-3.5 flex items-start gap-3 text-xs text-amber-950 leading-relaxed">
        <svg aria-hidden="true" class="w-5 h-5 text-amber-700 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <p><strong class="font-bold">Disclaimer Klinis:</strong> {{ $disclaimer }}</p>
    </div>

    <!-- Accessible Tabbed Report Detail Workspace -->
    <div id="spk-report-tabs" data-dx-tabs>
        <div data-dx-tablist focusgroup="tablist nomemory" aria-label="Bagian Laporan Detail SPK" class="dx-tablist bg-white rounded-t-2xl px-4 border-x border-t border-slate-200/90">
            <button type="button" id="tab-spk-narrative" data-dx-tab="narrative" aria-selected="true" focusgroupstart class="dx-tab-btn">
                Ringkasan &amp; Narasi LLM
            </button>
            <button type="button" id="tab-spk-shap" data-dx-tab="shap" aria-selected="false" class="dx-tab-btn">
                Analisis Fitur TreeSHAP (Top-{{ count($shapTop) }})
            </button>
            <button type="button" id="tab-spk-inputs" data-dx-tab="inputs" aria-selected="false" class="dx-tab-btn">
                Data Input 21 Fitur
            </button>
        </div>

        <!-- TAB 1: Ringkasan & Narasi LLM (RM6) + Nakes Rating -->
        <div role="tabpanel" aria-labelledby="tab-spk-narrative" data-dx-panel="narrative">
            <div class="bg-white rounded-b-2xl border-x border-b border-slate-200/90 p-6 sm:p-8 shadow-2xs space-y-6">
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
                    class="space-y-6"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-200/80">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="px-2.5 py-0.5 rounded-md text-[11px] font-semibold border"
                                    :class="used_fallback ? 'bg-slate-100 text-slate-700 border-slate-200' : 'bg-indigo-50 text-indigo-800 border-indigo-200'"
                                    x-text="used_fallback ? 'Narasi Templat Deterministik' : ('LLM Aktif · ' + model_name)"
                                ></span>
                                <span class="px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    ✓ Tervalidasi Guardrail 7-Lapis
                                </span>
                            </div>
                            <h2 id="llm-narrative-heading" class="text-lg font-extrabold text-slate-900 mt-1.5">Interpretasi Bahasa Alami &amp; Saran Tindak Lanjut</h2>
                        </div>

                        <form action="{{ route('skrining.regenerate', $screening->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3.5 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 whitespace-nowrap transition">
                                ↻ Susun Ulang Narasi AI
                            </button>
                        </form>
                    </div>

                    <!-- Skeleton Loading -->
                    <div x-show="status === 'pending'" class="space-y-3 animate-pulse py-4">
                        <div class="h-4 bg-slate-200 rounded w-3/4"></div>
                        <div class="h-4 bg-slate-200 rounded w-full"></div>
                        <div class="h-4 bg-slate-200 rounded w-5/6"></div>
                        <p class="text-xs text-slate-500 pt-2">Sedang menyusun dan memvalidasi narasi penjelasan dari kontribusi SHAP…</p>
                    </div>

                    <!-- Narrative Content -->
                    <div x-show="status === 'done'" class="space-y-5">
                        <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 text-sm sm:text-base text-slate-800 leading-relaxed font-medium" x-text="narrative?.ringkasan"></div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="p-5 rounded-2xl bg-rose-50/40 border border-rose-200/80 space-y-3">
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-rose-900">▲ Faktor Pendorong Risiko / Kelas Prediksi</h3>
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

                            <div class="p-5 rounded-2xl bg-emerald-50/40 border border-emerald-200/80 space-y-3">
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-emerald-900">▼ Faktor Protektif / Penurun Kecenderungan</h3>
                                <ul class="space-y-2.5 text-xs sm:text-sm text-slate-700 leading-relaxed">
                                    <template x-for="item in (narrative?.faktor_yang_menurunkan || [])" :key="item.feature">
                                        <li class="flex items-start gap-2">
                                            <span class="font-mono font-bold text-emerald-700 shrink-0" x-text="'[' + item.feature + ']'"></span>
                                            <span x-text="item.penjelasan"></span>
                                        </li>
                                    </template>
                                    <li x-show="!(narrative?.faktor_yang_menurunkan?.length)" class="text-slate-500 italic">Tidak ada faktor protektif pada Top-K SHAP.</li>
                                </ul>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                            <div class="md:col-span-7 p-5 rounded-2xl bg-teal-50/40 border border-teal-200/80 space-y-2.5">
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-teal-900">Saran Gaya Hidup Sehat Umum (Non-Farmakologis)</h3>
                                <ul class="list-disc list-inside space-y-1.5 text-xs sm:text-sm text-slate-700 leading-relaxed">
                                    <template x-for="(saran, idx) in (narrative?.saran_umum || [])" :key="idx">
                                        <li x-text="saran"></li>
                                    </template>
                                </ul>
                            </div>

                            <div class="md:col-span-5 p-5 rounded-2xl bg-indigo-50/40 border border-indigo-200/80 space-y-2">
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-indigo-900">Kapan Perlu ke Dokter / Faskes?</h3>
                                <p class="text-xs sm:text-sm text-indigo-950 leading-relaxed" x-text="narrative?.kapan_ke_tenaga_kesehatan"></p>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500">
                            <div class="flex flex-wrap items-center gap-3">
                                <span>Prompt: <strong class="font-mono" x-text="prompt_version"></strong></span>
                                <span>·</span>
                                <span>Konsistensi Label &amp; Top-K: <strong>Lulus</strong></span>
                                <span>·</span>
                                <span>Bebas Klaim Diagnosis &amp; Obat: <strong>Lulus</strong></span>
                            </div>
                            <span class="font-mono" x-text="'Latensi: ' + latency_ms + ' ms'"></span>
                        </div>
                    </div>
                </section>

                <!-- Inline Nakes Rating Form (RM6) -->
                @if($interpretation && (auth()->user()->isNakes() || auth()->user()->isAdmin()))
                    <section aria-labelledby="rating-form-heading" class="rounded-2xl bg-indigo-50/50 border border-indigo-200/90 p-5 sm:p-6 space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-700">Evaluasi Pakar / Tenaga Medis (RM6)</span>
                                <h3 id="rating-form-heading" class="text-sm sm:text-base font-extrabold text-slate-900">Penilaian Kualitas Narasi LLM (Skala Likert 1–5)</h3>
                            </div>
                            @if($myRating)
                                <span class="px-3 py-1 rounded-full bg-indigo-600 text-white text-xs font-mono font-bold">
                                    Skor Anda: {{ $myRating->averageScore() }} / 5,0
                                </span>
                            @endif
                        </div>

                        <form action="{{ route('nakes.ratings.store', $interpretation->id) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 items-end">
                            @csrf
                            @foreach([
                                'akurasi' => '1. Akurasi thd SHAP',
                                'kejelasan' => '2. Kejelasan Bahasa',
                                'kegunaan' => '3. Kegunaan Praktis',
                                'keamanan' => '4. Keamanan Etika',
                            ] as $field => $label)
                                <div>
                                    <label for="rate_{{ $field }}" class="block text-xs font-bold text-slate-800 mb-1">{{ $label }}</label>
                                    <select id="rate_{{ $field }}" name="{{ $field }}" class="w-full rounded-xl border border-indigo-300 bg-white px-3 py-2 text-xs font-semibold">
                                        @for($s = 5; $s >= 1; $s--)
                                            <option value="{{ $s }}" {{ ($myRating?->$field ?? 5) == $s ? 'selected' : '' }}>{{ $s }} — {{ $s == 5 ? 'Sangat Baik' : ($s == 4 ? 'Baik' : ($s == 3 ? 'Cukup' : ($s == 2 ? 'Kurang' : 'Sangat Kurang'))) }}</option>
                                        @endfor
                                    </select>
                                </div>
                            @endforeach
                            <div class="sm:col-span-2 lg:col-span-3">
                                <label for="komentar" class="block text-xs font-bold text-slate-800 mb-1">Catatan Klinis (Opsional)</label>
                                <input type="text" id="komentar" name="komentar" value="{{ $myRating?->komentar }}" placeholder="Contoh: Penjelasan arah nilai SHAP sudah akurat dan aman bagi pasien." class="w-full rounded-xl border border-indigo-300 bg-white px-3.5 py-2 text-xs">
                            </div>
                            <div>
                                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-xs transition tap-target">
                                    Simpan Penilaian
                                </button>
                            </div>
                        </form>
                    </section>
                @endif
            </div>
        </div>

        <!-- TAB 2: Analisis Fitur TreeSHAP (RM5) -->
        <div role="tabpanel" aria-labelledby="tab-spk-shap" data-dx-panel="shap" hidden="until-found">
            <div class="bg-white rounded-b-2xl border-x border-b border-slate-200/90 p-6 sm:p-8 shadow-2xs space-y-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-4 border-b border-slate-200/80">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-teal-700">Explainable AI (TreeSHAP Lokal — RM5)</span>
                        <h2 class="text-lg font-extrabold text-slate-900 mt-0.5">Kontribusi Fitur terhadap Kelas {{ $prediction->label_id ?? $prediction->label }}</h2>
                        <p class="text-xs text-slate-600 mt-0.5">
                            Top-{{ count($shapTop) }} fitur dengan <code class="font-mono">|SHAP|</code> terbesar (Nilai dasar / <em>base value</em> log-odds: <code class="font-mono font-bold text-slate-800">{{ $prediction->base_value }}</code>).
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-rose-50 text-rose-800 border border-rose-200">▲ Pendorong Risiko (SHAP &gt; 0)</span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200">▼ Protektif / Penurun (SHAP &lt; 0)</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                    <div class="lg:col-span-6 h-72">
                        <canvas id="shapChart" aria-label="Grafik kontribusi fitur SHAP lokal"></canvas>
                    </div>

                    <div class="lg:col-span-6 space-y-2.5">
                        @foreach($shapTop as $item)
                            @php $isUp = ($item['direction'] ?? 'naik') === 'naik'; @endphp
                            <div class="p-3.5 rounded-xl border flex items-center justify-between gap-3 {{ $isUp ? 'bg-rose-50/40 border-rose-200/80' : 'bg-emerald-50/40 border-emerald-200/80' }}">
                                <div class="min-w-0 space-y-0.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[11px] font-mono font-bold px-1.5 py-0.5 rounded bg-white border border-slate-200 text-slate-600">#{{ $item['rank'] }}</span>
                                        <strong class="text-xs sm:text-sm font-bold text-slate-900 truncate">{{ $item['label_id'] }}</strong>
                                    </div>
                                    <p class="text-xs text-slate-600">Nilai input: <strong>{{ $item['display'] }}</strong></p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg font-mono text-xs font-bold {{ $isUp ? 'bg-rose-100 text-rose-900' : 'bg-emerald-100 text-emerald-900' }}">
                                        {{ $isUp ? '▲ +' : '▼ ' }}{{ number_format((float) $item['shap'], 4) }}
                                    </span>
                                    <span class="block text-[11px] font-semibold mt-0.5 {{ $isUp ? 'text-rose-700' : 'text-emerald-700' }}">
                                        {{ $isUp ? 'Mendorong Risiko' : 'Protektif / Menahan' }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: Data Input 21 Fitur -->
        <div role="tabpanel" aria-labelledby="tab-spk-inputs" data-dx-panel="inputs" hidden="until-found">
            <div class="bg-white rounded-b-2xl border-x border-b border-slate-200/90 p-6 sm:p-8 shadow-2xs space-y-4">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900">Ringkasan 21 Indikator Masukan (BRFSS 2015)</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Seluruh vektor fitur yang diproses oleh model XGBoost pada skrining ini.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 text-xs">
                    @foreach($inputSummary as $inp)
                        <div class="p-3.5 rounded-xl bg-slate-50/80 border border-slate-200/80 flex flex-col justify-between gap-1">
                            <span class="text-slate-500 font-medium">{{ $inp['label_id'] }} (<code class="font-mono text-teal-700">{{ $inp['feature'] }}</code>)</span>
                            <strong class="text-slate-900 text-sm font-semibold">{{ $inp['display'] }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const probs = @js($probs);
    const shapTop = @js($shapTop);

    const probCtx = document.getElementById('probChart');
    if (probCtx && window.Chart) {
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
                    barThickness: 28
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

    let shapChartInstance = null;
    const shapCtx = document.getElementById('shapChart');
    if (shapCtx && window.Chart && shapTop.length) {
        shapChartInstance = new Chart(shapCtx, {
            type: 'bar',
            data: {
                labels: shapTop.map(i => (i.direction === 'naik' ? '▲ ' : '▼ ') + i.feature + ' (' + i.raw_value + ')'),
                datasets: [{
                    label: 'Nilai SHAP (Log-Odds)',
                    data: shapTop.map(i => i.shap),
                    backgroundColor: shapTop.map(i => i.direction === 'naik' ? '#f43f5e' : '#10b981'),
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
                    x: { title: { display: true, text: 'Kontribusi Nilai SHAP (Log-Odds)' } }
                }
            }
        });
    }

    const tabsEl = document.getElementById('spk-report-tabs');
    if (tabsEl && shapChartInstance) {
        tabsEl.addEventListener('dx:tab-changed', (e) => {
            if (e.detail?.tab === 'shap') {
                requestAnimationFrame(() => shapChartInstance.resize());
            }
        });
    }
});
</script>
@endpush
@endsection
