@extends('layouts.app')

@section('title', 'Beranda — SPK Klasifikasi Status Diabetes')

@section('content')
<div class="space-y-10">
    <!-- Hero Section -->
    <div class="relative rounded-3xl bg-gradient-to-br from-slate-900 via-teal-950 to-slate-900 text-white p-8 sm:p-12 shadow-xl overflow-hidden border border-teal-800/40">
        <div class="relative z-10 max-w-3xl space-y-6">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-teal-500/15 border border-teal-400/30 text-teal-300 text-xs font-semibold">
                <span class="w-2 h-2 rounded-full bg-teal-400 animate-pulse"></span>
                <span>Purwarupa Tesis Magister — Explainable AI (XAI) & Guardrailed LLM</span>
            </div>

            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight leading-tight">
                Deteksi Dini Status Diabetes yang <span class="text-transparent bg-clip-text bg-gradient-to-r from-teal-300 to-sky-300">Transparan & Dapat Dijelaskan</span>
            </h1>

            <p class="text-slate-300 text-base sm:text-lg leading-relaxed">
                <strong>DiaXplain</strong> mengintegrasikan klasifikasi multikelas <strong>XGBoost</strong> (<em>No Diabetes, Prediabetes, Diabetes</em>) pada 21 indikator kesehatan BRFSS, penjelasan kontribusi fitur <strong>TreeSHAP</strong>, serta interpretasi bahasa alami berbasis <strong>Large Language Model (LLM)</strong> dengan validator fakta berlapis.
            </p>

            <div class="flex flex-wrap items-center gap-3 pt-2">
                @auth
                    <a href="{{ route('skrining.create') }}" class="px-6 py-3.5 rounded-xl bg-teal-500 hover:bg-teal-400 text-slate-950 font-bold text-sm shadow-lg transition flex items-center gap-2">
                        <span>Mulai Skrining 21 Indikator</span>
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                    <a href="{{ route('dashboard') }}" class="px-5 py-3.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-sm border border-white/15 transition">
                        Buka Dashboard
                    </a>
                @else
                    <form action="{{ route('demo.login', 'pengguna') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-6 py-3.5 rounded-xl bg-teal-500 hover:bg-teal-400 text-slate-950 font-bold text-sm shadow-lg transition">
                            Coba Skrining Sekarang (Akun Demo)
                        </button>
                    </form>
                    <form action="{{ route('demo.login', 'admin') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-5 py-3.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-sm border border-white/20 transition">
                            Buka Dashboard Eksperimen & Input API Key
                        </button>
                    </form>
                @endauth
            </div>
        </div>
    </div>

    <!-- System Disclaimer Banner -->
    <div class="rounded-2xl bg-amber-50 border border-amber-200/90 p-5 flex items-start gap-4">
        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
            <svg aria-hidden="true" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <div class="text-xs sm:text-sm text-amber-950 leading-relaxed">
            <h2 class="font-bold text-amber-900 mb-0.5">Pernyataan Batasan Klinis (Bukan Alat Diagnosis Medis)</h2>
            <p>{{ $disclaimer }}</p>
        </div>
    </div>

    <!-- 4 Pillars of Research Pipeline -->
    <section aria-labelledby="pillars-heading" class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2">
            <div>
                <h2 id="pillars-heading" class="text-xl font-extrabold text-slate-900">Alur Metodologi Ilmiah DiaXplain (RM1 – RM6)</h2>
                <p class="text-sm text-slate-600">Dirancang sesuai kerangka penelitian tanpa membalik alur eksperimen dan bebas kebocoran data (anti-leakage).</p>
            </div>
            <a href="{{ route('about') }}" class="text-sm font-semibold text-teal-700 hover:text-teal-800">Lihat detail 21 fitur & keterbatasan →</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-2xs space-y-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-slate-100 text-slate-800">Tahap 1 · RM1 & RM2</span>
                <h3 class="font-bold text-base text-slate-900">Baseline XGBoost & Weakness Gate</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Melatih model baseline default (E0), mengevaluasi metrik per kelas, dan menerapkan <em>Weakness Gate</em> terukur untuk mendeteksi bias kelas minoritas <strong>Prediabetes</strong>.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-2xs space-y-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-teal-50 text-teal-800">Tahap 2 · RM3 & RM4</span>
                <h3 class="font-bold text-base text-slate-900">Matriks E1–E6 & Best Model</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Menguji Class Weight, SMOTE, SMOTENC, Hybrid SMOTETomek, dan Optuna HPO secara sistematis di dalam lipatan 5-Fold CV hingga terpilih <strong>Best Model ({{ $selection['selected_code'] ?? 'E6' }})</strong>.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-2xs space-y-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-sky-50 text-sky-800">Tahap 3 · RM5</span>
                <h3 class="font-bold text-base text-slate-900">Explainable AI (TreeSHAP)</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Menghitung kontribusi aditif fitur global dan lokal (Top-K fitur pendorong ▲ maupun penahan ▼) dalam ruang <em>log-odds</em> pada model terbaik.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-2xs space-y-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-purple-50 text-purple-800">Tahap 4 · RM6</span>
                <h3 class="font-bold text-base text-slate-900">Interpretasi LLM + 7 Guardrail</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Menerjemahkan JSON hasil model + SHAP menjadi narasi bahasa Indonesia yang empatik melalui API Key pihak ketiga, divalidasi ketat terhadap halusinasi & frasa diagnosis.
                </p>
            </div>
        </div>
    </section>
</div>
@endsection
