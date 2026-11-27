@extends('layouts.guest')

@section('title', 'Beranda — SPK Klasifikasi Status Diabetes')

@section('content')
@php
    $bestCode = $selection['selected_code'] ?? ($activeModel?->code ?? 'E6');
    $rocAuc = $activeModel?->metrics['test_metrics']['roc_auc_ovr_macro'] ?? ($selection['best_test_metrics']['roc_auc_ovr_macro'] ?? 0.82);
    $balAcc = $activeModel?->metrics['test_metrics']['balanced_accuracy'] ?? ($selection['best_test_metrics']['balanced_accuracy'] ?? 0.56);
    $modelVersion = $activeModel?->version_tag ?? 'xgb-v1.0.0';
@endphp

<div class="space-y-12">
    <!-- Proportional 2-Column SaaS Hero Section -->
    <section class="relative rounded-3xl bg-gradient-to-br from-slate-900 via-slate-900 to-teal-950 text-white p-7 sm:p-10 lg:p-12 shadow-xl border border-slate-800 overflow-hidden">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-center relative z-10">
            <!-- Left Column: Value Proposition & Primary Actions -->
            <div class="lg:col-span-7 space-y-6">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-500/15 border border-teal-400/30 text-teal-200 text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-teal-400"></span>
                    <span>Sistem Pendukung Keputusan Klinis · XGBoost + TreeSHAP + LLM</span>
                </div>

                <h1 class="text-3xl sm:text-4xl lg:text-[2.75rem] font-extrabold tracking-tight leading-[1.15]">
                    Skrining Status Diabetes yang <span class="text-transparent bg-clip-text bg-gradient-to-r from-teal-300 to-sky-300">Akurat, Transparan &amp; Dapat Dijelaskan</span>
                </h1>

                <p class="text-slate-300 text-sm sm:text-base leading-relaxed max-w-2xl">
                    <strong>DiaXplain</strong> memadukan klasifikasi 3-kelas <strong>XGBoost</strong> (<em>Tidak Diabetes, Prediabetes, Diabetes</em>) pada 21 indikator BRFSS, atribusi fitur <strong>TreeSHAP</strong>, serta interpretasi naratif <strong>LLM dengan 7 lapis guardrail fakta</strong>.
                </p>

                <div class="flex flex-wrap items-center gap-3 pt-1">
                    @auth
                        <a href="{{ route('skrining.create') }}" class="px-5 py-3 rounded-xl bg-teal-500 hover:bg-teal-400 text-slate-950 font-bold text-sm shadow-md transition inline-flex items-center gap-2 whitespace-nowrap">
                            <span>Mulai Skrining 21 Indikator</span>
                            <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                        <a href="{{ route('dashboard') }}" class="px-5 py-3 rounded-xl bg-white/10 hover:bg-white/15 text-white font-semibold text-sm border border-white/15 transition whitespace-nowrap">
                            Buka Workspace
                        </a>
                    @else
                        <form action="{{ route('demo.login', 'pengguna') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-5 py-3 rounded-xl bg-teal-500 hover:bg-teal-400 text-slate-950 font-bold text-sm shadow-md transition inline-flex items-center gap-2 whitespace-nowrap">
                                <span>Coba Skrining Demo</span>
                                <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </button>
                        </form>
                        <form action="{{ route('demo.login', 'admin') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-5 py-3 rounded-xl bg-white/10 hover:bg-white/15 text-white font-semibold text-sm border border-white/20 transition whitespace-nowrap">
                                Dashboard Riset &amp; API Key AI
                            </button>
                        </form>
                    @endauth
                </div>
            </div>

            <!-- Right Column: Compact Clinical Preview Card -->
            <div class="lg:col-span-5">
                <div class="rounded-2xl bg-slate-950/70 backdrop-blur border border-slate-700/70 p-5 sm:p-6 space-y-5 shadow-2xl">
                    <div class="flex items-center justify-between gap-2 pb-3.5 border-b border-slate-800">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-teal-300">Model Produksi Aktif</p>
                            <p class="text-sm font-bold text-white mt-0.5 font-mono">{{ $modelVersion }} · Skenario {{ $bestCode }}</p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-500/15 text-emerald-300 border border-emerald-400/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            Terverifikasi
                        </span>
                    </div>

                    <!-- Mini Clinical Preview Bars -->
                    <div class="space-y-3 text-xs">
                        <div class="flex items-center justify-between text-slate-300">
                            <span>Distribusi Output 3-Kelas (Softprob)</span>
                            <span class="font-mono text-[11px] text-slate-400">21 Fitur BRFSS</span>
                        </div>
                        <div class="space-y-2">
                            <div>
                                <div class="flex justify-between text-[11px] mb-1">
                                    <span class="text-emerald-300 font-semibold">Tidak Diabetes (0)</span>
                                    <span class="font-mono text-slate-300">Kelas Normal</span>
                                </div>
                                <div class="h-2 rounded-full bg-slate-800 overflow-hidden"><div class="h-full w-4/5 bg-emerald-500 rounded-full"></div></div>
                            </div>
                            <div>
                                <div class="flex justify-between text-[11px] mb-1">
                                    <span class="text-amber-300 font-semibold">Prediabetes (1)</span>
                                    <span class="font-mono text-slate-300">Kelas Kewaspadaan</span>
                                </div>
                                <div class="h-2 rounded-full bg-slate-800 overflow-hidden"><div class="h-full w-1/3 bg-amber-400 rounded-full"></div></div>
                            </div>
                            <div>
                                <div class="flex justify-between text-[11px] mb-1">
                                    <span class="text-rose-300 font-semibold">Diabetes (2)</span>
                                    <span class="font-mono text-slate-300">Kelas Risiko Tinggi</span>
                                </div>
                                <div class="h-2 rounded-full bg-slate-800 overflow-hidden"><div class="h-full w-1/2 bg-rose-500 rounded-full"></div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Key Model Stats -->
                    <div class="grid grid-cols-3 gap-2.5 pt-2 border-t border-slate-800 text-center">
                        <div class="rounded-xl bg-slate-900/90 border border-slate-800 p-2.5">
                            <span class="block text-[10px] text-slate-400 uppercase font-semibold">ROC-AUC OvR</span>
                            <strong class="text-sm font-mono font-bold text-teal-300">{{ number_format((float) $rocAuc * 100, 1, ',', '.') }}%</strong>
                        </div>
                        <div class="rounded-xl bg-slate-900/90 border border-slate-800 p-2.5">
                            <span class="block text-[10px] text-slate-400 uppercase font-semibold">Balanced Acc</span>
                            <strong class="text-sm font-mono font-bold text-sky-300">{{ number_format((float) $balAcc * 100, 1, ',', '.') }}%</strong>
                        </div>
                        <div class="rounded-xl bg-slate-900/90 border border-slate-800 p-2.5">
                            <span class="block text-[10px] text-slate-400 uppercase font-semibold">Guardrail</span>
                            <strong class="text-sm font-mono font-bold text-emerald-300">7 Lapis</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 3-Stage Clinical Workflow Summary -->
    <section aria-labelledby="workflow-heading" class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-teal-700">Alur Kerja Sistem</p>
                <h2 id="workflow-heading" class="text-xl sm:text-2xl font-extrabold text-slate-900 mt-0.5">Tiga Tahap Analisis Terstruktur &amp; Dapat Diaudit</h2>
            </div>
            <a href="{{ route('about') }}" class="text-sm font-semibold text-teal-700 hover:text-teal-800 hover:underline">
                Lihat Codebook 21 Fitur &amp; Keterbatasan →
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs space-y-3">
                <div class="w-10 h-10 rounded-xl bg-teal-50 border border-teal-200/80 text-teal-700 flex items-center justify-center font-mono font-bold text-sm">
                    01
                </div>
                <h3 class="text-base font-bold text-slate-900">Input 21 Indikator BRFSS</h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Formulir bertahap yang ramah pasien &amp; klinisi, dilengkapi kalkulator BMI otomatis, pemetaan kategori usia 1–13, serta kode pseudonim (UU PDP).
                </p>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs space-y-3">
                <div class="w-10 h-10 rounded-xl bg-sky-50 border border-sky-200/80 text-sky-700 flex items-center justify-center font-mono font-bold text-sm">
                    02
                </div>
                <h3 class="text-base font-bold text-slate-900">Prediksi XGBoost + TreeSHAP</h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Model terbaik (<strong>{{ $bestCode }}</strong>) menghitung probabilitas 3 kelas sekaligus mengekstrak kontribusi aditif lokal Top-K SHAP (pendorong &amp; penahan risiko).
                </p>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs space-y-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-200/80 text-indigo-700 flex items-center justify-center font-mono font-bold text-sm">
                    03
                </div>
                <h3 class="text-base font-bold text-slate-900">Narasi LLM Tervalidasi</h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    JSON hasil model + SHAP diterjemahkan menjadi penjelasan bahasa Indonesia yang empatik melalui API Key pihak ketiga, dijaga 7 lapis validator fakta dan fallback deterministik.
                </p>
            </div>
        </div>
    </section>

    <!-- Elegant Clinical Disclaimer Banner -->
    <section aria-label="Pernyataan Batasan Klinis" class="rounded-2xl bg-amber-50/80 border border-amber-200/90 p-5 sm:p-6 flex items-start gap-4 shadow-2xs">
        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center shrink-0">
            <svg aria-hidden="true" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <div class="text-xs sm:text-sm text-amber-950 leading-relaxed">
            <h2 class="font-bold text-amber-900 mb-1">Disclaimer Klinis &amp; Etika Penggunaan</h2>
            <p>{{ $disclaimer }}</p>
        </div>
    </section>
</div>
@endsection
