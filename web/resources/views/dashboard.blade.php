@extends('layouts.app')

@section('title', 'Dashboard Utama')

@section('content')
<div class="space-y-8">
    <!-- Top Greeting & Quick Actions -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-2xs flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $user->isAdmin() ? 'bg-purple-100 text-purple-800' : ($user->isNakes() ? 'bg-sky-100 text-sky-800' : 'bg-emerald-100 text-emerald-800') }}">
                    {{ $user->roleBadgeLabel() }}
                </span>
                @if($activeModel)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-mono bg-slate-100 text-slate-700 border border-slate-200">
                        Model Aktif: {{ $activeModel->version_tag }} ({{ $activeModel->technique }})
                    </span>
                @endif
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Selamat datang, {{ $user->name }}</h1>
            <p class="text-xs sm:text-sm text-slate-600">
                Sistem Pendukung Keputusan Klasifikasi Status Diabetes (<em>No Diabetes, Prediabetes, Diabetes</em>) berbasis XGBoost, SHAP, dan Interpretasi LLM.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('skrining.create') }}" class="px-5 py-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-sm shadow-xs transition flex items-center gap-2">
                <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Skrining Baru (21 Fitur)</span>
            </a>
            @if($user->isAdmin())
                <a href="{{ route('admin.llm.config') }}" class="px-4 py-3 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 font-bold text-sm transition">
                    Konfigurasi API Key AI / LLM
                </a>
                <a href="{{ route('admin.experiments') }}" class="px-4 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm transition">
                    Dashboard Eksperimen E0–E6
                </a>
            @elseif($user->isNakes())
                <a href="{{ route('nakes.ratings.index') }}" class="px-4 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm transition">
                    Penilaian Narasi AI (RM6)
                </a>
            @endif
        </div>
    </div>

    <!-- Admin System Overview & Third-Party API Key Banner -->
    @if($adminStats)
        <section aria-labelledby="admin-overview-heading" class="space-y-4">
            <h2 id="admin-overview-heading" class="text-lg font-extrabold text-slate-900">Ringkasan Sistem & Layanan AI (Administrator)</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs">
                    <span class="text-xs font-semibold text-slate-500">Skenario Eksperimen Terimpor</span>
                    <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ $adminStats['total_experiments'] }} Skenario</div>
                    <a href="{{ route('admin.experiments') }}" class="text-xs font-semibold text-teal-700 hover:underline mt-2 inline-block">Lihat E0–E6 & SHAP Global →</a>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs">
                    <span class="text-xs font-semibold text-slate-500">Provider AI / LLM Pihak Ketiga</span>
                    <div class="text-lg font-extrabold text-slate-900 mt-1 font-mono">{{ $adminStats['llm_provider'] }} ({{ $adminStats['llm_model'] }})</div>
                    <div class="mt-2 flex items-center gap-2 text-xs">
                        @if($adminStats['llm_key_configured'])
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-semibold">API Key Aktif</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-semibold">Mode Templat Deterministik (Belum Input Key)</span>
                        @endif
                        <a href="{{ route('admin.llm.config') }}" class="text-teal-700 font-bold hover:underline">Input / Ubah Key →</a>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs">
                    <span class="text-xs font-semibold text-slate-500">Evaluasi Pakar Narasi LLM (RM6)</span>
                    <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ $adminStats['total_ratings'] }} Penilaian</div>
                    <a href="{{ route('admin.ratings.export') }}" class="text-xs font-semibold text-indigo-700 hover:underline mt-2 inline-block">Unduh CSV Penilaian →</a>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs">
                    <span class="text-xs font-semibold text-slate-500">Total Skrining & Pengguna</span>
                    <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ $adminStats['total_screenings'] }} Skrining · {{ $adminStats['total_users'] }} Akun</div>
                    <a href="{{ route('admin.users') }}" class="text-xs font-semibold text-slate-700 hover:underline mt-2 inline-block">Kelola Pengguna & Audit →</a>
                </div>
            </div>
        </section>
    @endif

    <!-- Nakes Evaluation Banner -->
    @if($nakesStats)
        <div class="rounded-2xl bg-indigo-50/90 border border-indigo-200 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <h2 class="text-base font-extrabold text-indigo-950">Modul Evaluasi Kualitas Narasi LLM oleh Tenaga Medis (RM6)</h2>
                <p class="text-xs text-indigo-900">
                    Anda telah menilai <strong>{{ $nakesStats['rated_by_me'] }} narasi</strong>. Saat ini terdapat <strong>{{ $nakesStats['pending_rating'] }} kasus</strong> yang dapat Anda beri skor rubrik (Akurasi, Kejelasan, Kegunaan, Keamanan 1–5).
                </p>
            </div>
            <a href="{{ route('nakes.ratings.index') }}" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs whitespace-nowrap transition">
                Buka Halaman Penilaian Narasi →
            </a>
        </div>
    @endif

    <!-- Personal / Clinical Screening Summary Cards -->
    <section aria-labelledby="my-stats-heading" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <h2 id="my-stats-heading" class="visually-hidden">Ringkasan Riwayat Skrining</h2>
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs">
            <span class="text-xs font-semibold text-slate-500">Total Skrining Anda</span>
            <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ $myTotalScreenings }}</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-emerald-200 bg-emerald-50/30">
            <span class="text-xs font-semibold text-emerald-800">Tidak Diabetes (Kelas 0)</span>
            <div class="text-2xl font-extrabold text-emerald-700 mt-1">{{ $classDistribution['No Diabetes'] ?? 0 }}</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-amber-200 bg-amber-50/30">
            <span class="text-xs font-semibold text-amber-900">Prediabetes (Kelas 1)</span>
            <div class="text-2xl font-extrabold text-amber-700 mt-1">{{ $classDistribution['Prediabetes'] ?? 0 }}</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-rose-200 bg-rose-50/30">
            <span class="text-xs font-semibold text-rose-900">Diabetes (Kelas 2)</span>
            <div class="text-2xl font-extrabold text-rose-700 mt-1">{{ $classDistribution['Diabetes'] ?? 0 }}</div>
        </div>
    </section>

    <!-- Recent Screenings Table -->
    <section aria-labelledby="recent-screenings-caption" class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-extrabold text-slate-900">Riwayat Skrining Terbaru</h2>
                <p class="text-xs text-slate-500">Klik "Lihat Laporan SPK" untuk memeriksa grafik SHAP lokal, narasi LLM, dan mengunduh PDF.</p>
            </div>
            <a href="{{ route('skrining.index') }}" class="text-xs font-bold text-teal-700 hover:underline">Lihat Semua →</a>
        </div>

        @if($myScreenings->isEmpty())
            <div class="p-10 text-center space-y-3">
                <p class="text-sm text-slate-600">Belum ada riwayat skrining pada akun ini.</p>
                <a href="{{ route('skrining.create') }}" class="inline-flex px-4 py-2.5 rounded-xl bg-teal-600 text-white font-bold text-xs">
                    + Buat Skrining Pertama Anda
                </a>
            </div>
        @else
            <div tabindex="0" role="region" aria-labelledby="recent-screenings-caption" class="table-wrapper">
                <table class="text-left text-xs sm:text-sm">
                    <caption id="recent-screenings-caption" class="visually-hidden">Daftar riwayat skrining terbaru beserta hasil klasifikasi dan probabilitasnya</caption>
                    <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                        <tr>
                            <th scope="col" class="py-3 px-4">ID / Pasien</th>
                            <th scope="col" class="py-3 px-4">Waktu Skrining</th>
                            <th scope="col" class="py-3 px-4">BMI & Usia</th>
                            <th scope="col" class="py-3 px-4">Hasil Klasifikasi Model</th>
                            <th scope="col" class="py-3 px-4">Keyakinan</th>
                            <th scope="col" class="py-3 px-4">Status Narasi AI</th>
                            <th scope="col" class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($myScreenings as $scr)
                            @php
                                $pred = $scr->prediction;
                                $interp = $scr->latestInterpretation;
                                $badgeClass = match($pred?->label_code) {
                                    0 => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                    1 => 'bg-amber-100 text-amber-900 border-amber-300',
                                    2 => 'bg-rose-100 text-rose-800 border-rose-300',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <th scope="row" class="py-3 px-4 font-mono text-xs font-semibold text-slate-800">
                                    {{ $scr->patient_label ?: ('SCR-' . strtoupper(substr($scr->id, 0, 8))) }}
                                </th>
                                <td class="py-3 px-4 text-slate-600">{{ $scr->created_at?->format('d M Y, H:i') }}</td>
                                <td class="py-3 px-4 text-slate-700">
                                    BMI <strong>{{ $scr->input['BMI'] ?? '-' }}</strong> · Kat. Usia {{ $scr->input['Age'] ?? '-' }}
                                </td>
                                <td class="py-3 px-4">
                                    @if($pred)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $badgeClass }}">
                                            {{ $pred->label_id ?? $pred->label }} ({{ $pred->label }})
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 capitalize text-slate-600 font-medium">{{ $pred?->confidence_note ?? '-' }}</td>
                                <td class="py-3 px-4">
                                    @if($interp && $interp->status === 'done')
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold {{ $interp->used_fallback ? 'text-slate-600' : 'text-teal-700' }}">
                                            ● {{ $interp->used_fallback ? 'Templat Tervalidasi' : ('LLM (' . $interp->provider . ')') }}
                                        </span>
                                    @else
                                        <span class="text-xs text-amber-700">Menyusun...</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right space-x-2 whitespace-nowrap">
                                    <a href="{{ route('skrining.show', $scr->id) }}" class="px-3 py-1.5 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-800 font-bold text-xs transition">
                                        Lihat Laporan SPK
                                    </a>
                                    <a href="{{ route('skrining.pdf', $scr->id) }}" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                                        PDF
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection

