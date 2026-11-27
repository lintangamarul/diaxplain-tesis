@extends('layouts.app')

@section('title', 'Dashboard Utama')
@section('page_title', 'Dashboard')

@section('content')
@php
    $providerLabels = [
        'openai_compatible' => 'OpenAI Compatible',
        'gemini' => 'Google Gemini',
        'anthropic' => 'Anthropic Claude',
        'template_fallback' => 'Templat Deterministik',
    ];
    $prettyProvider = $adminStats
        ? ($providerLabels[$adminStats['llm_provider']] ?? ucfirst(str_replace('_', ' ', (string) $adminStats['llm_provider'])))
        : null;
    $mlOk = ($adminStats['ml_health']['status'] ?? '') === 'ok';
@endphp

<div class="space-y-6">
    <!-- Compact Page Header -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1.5 min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold border {{ $user->roleBadgeClasses() }}">
                    {{ $user->roleBadgeLabel() }}
                </span>
                @if($activeModel)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-mono font-medium bg-slate-100 text-slate-700 border border-slate-200/80">
                        <span class="w-1.5 h-1.5 rounded-full bg-teal-600"></span>
                        <span>{{ $activeModel->version_tag }}</span>
                    </span>
                @endif
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 truncate">
                Selamat datang, {{ $user->displayName() }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-600">
                Sistem Pendukung Keputusan Klasifikasi Status Diabetes berbasis XGBoost, TreeSHAP, dan Interpretasi LLM.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <a href="{{ route('skrining.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-semibold text-xs sm:text-sm whitespace-nowrap shadow-xs transition">
                <svg aria-hidden="true" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Skrining Baru</span>
            </a>
            @if($user->isAdmin())
                <a href="{{ route('admin.experiments') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs sm:text-sm whitespace-nowrap transition">
                    <span>Eksperimen E0–E6</span>
                </a>
            @elseif($user->isNakes())
                <a href="{{ route('nakes.ratings.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs sm:text-sm whitespace-nowrap transition">
                    <span>Penilaian Narasi (RM6)</span>
                </a>
            @else
                <a href="{{ route('skrining.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 font-semibold text-xs sm:text-sm whitespace-nowrap transition">
                    <span>Riwayat Skrining</span>
                </a>
            @endif
        </div>
    </div>

    @if($adminStats)
        <!-- ADMIN & PENELITI: Tabbed Workspace -->
        <div data-dx-tabs class="space-y-0">
            <div data-dx-tablist focusgroup="tablist nomemory" aria-label="Workspace Dashboard Administrator" class="dx-tablist bg-white rounded-t-2xl px-4 border-x border-t border-slate-200/90">
                <button type="button" id="tab-admin-system" data-dx-tab="system" aria-selected="true" focusgroupstart class="dx-tab-btn">
                    Ringkasan Sistem &amp; AI
                </button>
                <button type="button" id="tab-admin-personal" data-dx-tab="personal" aria-selected="false" class="dx-tab-btn">
                    Aktivitas Skrining Pribadi ({{ $myTotalScreenings }})
                </button>
                <button type="button" id="tab-admin-rm6" data-dx-tab="rm6" aria-selected="false" class="dx-tab-btn">
                    Status Evaluasi RM6
                    @if(($nakesStats['pending_rating'] ?? 0) > 0)
                        <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-indigo-100 text-indigo-800">
                            {{ $nakesStats['pending_rating'] }}
                        </span>
                    @endif
                </button>
            </div>

            <!-- Panel 1: Ringkasan Sistem & AI (NO padding/border/margin directly on role="tabpanel") -->
            <div role="tabpanel" aria-labelledby="tab-admin-system" data-dx-panel="system">
                <div class="bg-white rounded-b-2xl border-x border-b border-slate-200/90 p-6 shadow-2xs space-y-6">
                    <!-- 4 Clean KPI Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                        <!-- Card 1: Eksperimen -->
                        <div class="rounded-2xl p-5 border border-slate-200/90 bg-slate-50/40 flex flex-col justify-between gap-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <span class="text-xs font-semibold text-slate-500">Skenario Eksperimen</span>
                                    <div class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">{{ $adminStats['total_experiments'] }}</div>
                                    <p class="text-xs text-slate-500 mt-0.5">Matriks E0–E6 &amp; TreeSHAP</p>
                                </div>
                                <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 border border-teal-200/70 flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                </div>
                            </div>
                            <div class="pt-3 border-t border-slate-200/70 flex items-center justify-between text-xs">
                                <span class="text-slate-500 font-medium">Artefak tersinkron</span>
                                <a href="{{ route('admin.experiments') }}" class="font-bold text-teal-700 hover:underline whitespace-nowrap">Lihat Riset →</a>
                            </div>
                        </div>

                        <!-- Card 2: Provider AI / LLM (Formatted Cleanly, No Overflow) -->
                        <div class="rounded-2xl p-5 border border-slate-200/90 bg-slate-50/40 flex flex-col justify-between gap-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <span class="text-xs font-semibold text-slate-500">Layanan AI / LLM</span>
                                    <div class="text-sm font-extrabold text-slate-900 mt-1.5 truncate" title="{{ $prettyProvider }} · {{ $adminStats['llm_model'] }}">
                                        {{ $prettyProvider }}
                                    </div>
                                    <p class="text-xs font-mono text-slate-600 truncate mt-0.5">{{ $adminStats['llm_model'] }}</p>
                                </div>
                                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 border border-amber-200/70 flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                </div>
                            </div>
                            <div class="pt-3 border-t border-slate-200/70 flex items-center justify-between gap-2 text-xs">
                                @if($adminStats['llm_key_configured'])
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold whitespace-nowrap">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        API Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200 font-semibold whitespace-nowrap">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Mode Templat
                                    </span>
                                @endif
                                <a href="{{ route('admin.llm.config') }}" class="font-bold text-teal-700 hover:underline whitespace-nowrap">Atur Key →</a>
                            </div>
                        </div>

                        <!-- Card 3: Evaluasi Pakar RM6 -->
                        <div class="rounded-2xl p-5 border border-slate-200/90 bg-slate-50/40 flex flex-col justify-between gap-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <span class="text-xs font-semibold text-slate-500">Penilaian Pakar (RM6)</span>
                                    <div class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">{{ $adminStats['total_ratings'] }}</div>
                                    <p class="text-xs text-slate-500 mt-0.5">Skor rubrik Likert 1–5</p>
                                </div>
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200/70 flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                            </div>
                            <div class="pt-3 border-t border-slate-200/70 flex items-center justify-between text-xs">
                                <span class="text-slate-500 font-medium">Siap lampiran tesis</span>
                                <a href="{{ route('admin.ratings.export') }}" class="font-bold text-indigo-700 hover:underline whitespace-nowrap">Ekspor CSV →</a>
                            </div>
                        </div>

                        <!-- Card 4: Total Skrining & Akun -->
                        <div class="rounded-2xl p-5 border border-slate-200/90 bg-slate-50/40 flex flex-col justify-between gap-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <span class="text-xs font-semibold text-slate-500">Aktivitas &amp; Pengguna</span>
                                    <div class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">{{ $adminStats['total_screenings'] }}</div>
                                    <p class="text-xs text-slate-500 mt-0.5">Total skrining · {{ $adminStats['total_users'] }} akun</p>
                                </div>
                                <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                            </div>
                            <div class="pt-3 border-t border-slate-200/70 flex items-center justify-between text-xs">
                                <span class="text-slate-500 font-medium">Kontrol RBAC</span>
                                <a href="{{ route('admin.users') }}" class="font-bold text-slate-800 hover:underline whitespace-nowrap">Kelola Akun →</a>
                            </div>
                        </div>
                    </div>

                    <!-- Infrastructure & Governance Quick Strip -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
                        <div class="rounded-xl border border-slate-200/80 p-4 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold text-slate-500">Status Layanan FastAPI ML</p>
                                <p class="text-sm font-bold text-slate-900 mt-0.5">{{ $mlOk ? 'Terhubung (Port 8001)' : 'Fallback Lokal Aktif' }}</p>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $mlOk ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                                <span class="w-2 h-2 rounded-full {{ $mlOk ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                {{ $mlOk ? 'Online' : 'Standby' }}
                            </span>
                        </div>
                        <a href="{{ route('admin.models') }}" class="rounded-xl border border-slate-200/80 hover:border-teal-300 hover:bg-teal-50/30 p-4 flex items-center justify-between gap-3 transition">
                            <div>
                                <p class="text-xs font-semibold text-slate-500">Registri Model Produksi</p>
                                <p class="text-sm font-bold text-slate-900 mt-0.5 font-mono">{{ $activeModel?->version_tag ?? 'xgb-v1.0.0' }}</p>
                            </div>
                            <span class="text-xs font-bold text-teal-700">Kelola →</span>
                        </a>
                        <a href="{{ route('admin.audit') }}" class="rounded-xl border border-slate-200/80 hover:border-teal-300 hover:bg-teal-50/30 p-4 flex items-center justify-between gap-3 transition">
                            <div>
                                <p class="text-xs font-semibold text-slate-500">Audit Keamanan &amp; UU PDP</p>
                                <p class="text-sm font-bold text-slate-900 mt-0.5">Jejak Aktivitas Sistem</p>
                            </div>
                            <span class="text-xs font-bold text-teal-700">Buka Log →</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Panel 2: Aktivitas Skrining Pribadi -->
            <div role="tabpanel" aria-labelledby="tab-admin-personal" data-dx-panel="personal" hidden="until-found">
                <div class="bg-white rounded-b-2xl border-x border-b border-slate-200/90 p-6 shadow-2xs space-y-6">
                    @include('partials.dashboard-personal-section')
                </div>
            </div>

            <!-- Panel 3: Status Evaluasi RM6 -->
            <div role="tabpanel" aria-labelledby="tab-admin-rm6" data-dx-panel="rm6" hidden="until-found">
                <div class="bg-white rounded-b-2xl border-x border-b border-slate-200/90 p-6 shadow-2xs space-y-5">
                    @if($nakesStats)
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="rounded-2xl p-5 border border-indigo-200/80 bg-indigo-50/40">
                                <span class="text-xs font-semibold text-indigo-800">Sudah Anda Nilai</span>
                                <div class="text-2xl font-extrabold text-indigo-950 mt-1 font-mono">{{ $nakesStats['rated_by_me'] }}</div>
                                <p class="text-xs text-indigo-700 mt-0.5">Narasi interpretasi LLM</p>
                            </div>
                            <div class="rounded-2xl p-5 border border-amber-200/80 bg-amber-50/40">
                                <span class="text-xs font-semibold text-amber-800">Menunggu Penilaian Anda</span>
                                <div class="text-2xl font-extrabold text-amber-950 mt-1 font-mono">{{ $nakesStats['pending_rating'] }}</div>
                                <p class="text-xs text-amber-700 mt-0.5">Kasus berstatus selesai</p>
                            </div>
                            <div class="rounded-2xl p-5 border border-slate-200/90 bg-slate-50/60">
                                <span class="text-xs font-semibold text-slate-600">Total Skor Semua Penilai</span>
                                <div class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">{{ $adminStats['total_ratings'] }}</div>
                                <p class="text-xs text-slate-500 mt-0.5">Akurasi · Kejelasan · Kegunaan · Keamanan</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-4 p-5 rounded-2xl bg-slate-50 border border-slate-200/80">
                            <div class="space-y-1">
                                <h2 class="text-sm font-bold text-slate-900">Evaluasi Kualitas Narasi Klinis oleh Tenaga Medis (RM6)</h2>
                                <p class="text-xs text-slate-600">Tinjau hasil narasi LLM terhadap kontribusi SHAP lokal dan ekspor data Likert untuk analisis bab hasil tesis.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2.5">
                                <a href="{{ route('nakes.ratings.index') }}" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold whitespace-nowrap transition">
                                    Buka Antrean Penilaian
                                </a>
                                <a href="{{ route('admin.ratings.export') }}" class="px-4 py-2.5 rounded-xl bg-white hover:bg-slate-100 text-slate-800 border border-slate-300 text-xs font-semibold whitespace-nowrap transition">
                                    Unduh CSV Penilaian
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        <!-- PENGGUNA UMUM & TENAGA MEDIS: Proportional 4-Column KPI + RM6 Banner + Recent Screenings -->
        <div class="space-y-6">
            @if($nakesStats)
                <div class="rounded-2xl bg-indigo-50/80 border border-indigo-200/90 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-2xs">
                    <div class="flex items-start gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
                            <svg aria-hidden="true" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-sm font-extrabold text-indigo-950">Evaluasi Kualitas Narasi LLM oleh Tenaga Medis (RM6)</h2>
                            <p class="text-xs text-indigo-900 leading-relaxed">
                                Anda telah menilai <strong>{{ $nakesStats['rated_by_me'] }} narasi</strong>. Terdapat <strong>{{ $nakesStats['pending_rating'] }} kasus</strong> yang siap dinilai dengan rubrik Likert (1–5).
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('nakes.ratings.index') }}" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs whitespace-nowrap transition shrink-0">
                        Buka Penilaian Narasi →
                    </a>
                </div>
            @endif

            @include('partials.dashboard-personal-section')
        </div>
    @endif
</div>
@endsection
