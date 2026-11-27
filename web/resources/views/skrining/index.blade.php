@extends('layouts.app')

@section('title', 'Riwayat Skrining Kesehatan')
@section('page_title', 'Riwayat Skrining')

@section('content')
@php
    $activeLabel = (string) request('label', '');
    $searchQuery = (string) request('search', '');
    $filterTabs = [
        '' => 'Semua',
        'No Diabetes' => 'Tidak Diabetes',
        'Prediabetes' => 'Prediabetes',
        'Diabetes' => 'Diabetes',
    ];
@endphp

<div class="space-y-6">
    <!-- Compact Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900">Riwayat Skrining Kesehatan</h1>
            <p class="text-xs sm:text-sm text-slate-600 mt-0.5">Kelola, cari, unduh laporan PDF, atau bandingkan seluruh hasil analisis XGBoost + TreeSHAP + LLM Anda.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <a href="{{ route('skrining.compare') }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 font-semibold text-xs sm:text-sm whitespace-nowrap transition">
                Bandingkan Skrining
            </a>
            <a href="{{ route('skrining.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-semibold text-xs sm:text-sm whitespace-nowrap shadow-xs transition">
                + Skrining Baru
            </a>
        </div>
    </div>

    <!-- SaaS Data Table Card with Unified Filter Toolbar -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <!-- Unified Toolbar: Status Filter Tabs (Left) + Pseudonym Search (Right) -->
        <div class="px-5 py-3.5 border-b border-slate-200/80 flex flex-col lg:flex-row lg:items-center justify-between gap-3 bg-slate-50/50">
            <!-- Status Filter Links (Semantic <nav>, not WAI-ARIA tabs since these navigate) -->
            <nav aria-label="Filter status klasifikasi" class="flex items-center gap-1 overflow-x-auto pb-1 lg:pb-0">
                @foreach($filterTabs as $val => $text)
                    @php
                        $isCurrent = $activeLabel === $val;
                        $params = array_filter(['label' => $val, 'search' => $searchQuery], fn ($v) => $v !== '');
                    @endphp
                    <a
                        href="{{ route('skrining.index', $params) }}"
                        @if($isCurrent) aria-current="page" @endif
                        class="px-3.5 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition border {{ $isCurrent ? 'bg-teal-700 text-white border-teal-700 shadow-2xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100 hover:text-slate-900' }}"
                    >
                        {{ $text }}
                    </a>
                @endforeach
            </nav>

            <!-- Search Input Form -->
            <form action="{{ route('skrining.index') }}" method="GET" class="flex items-center gap-2 w-full lg:w-auto">
                @if($activeLabel !== '')
                    <input type="hidden" name="label" value="{{ $activeLabel }}">
                @endif
                <div class="relative flex-1 lg:w-72">
                    <label for="search" class="visually-hidden">Cari kode pseudonim pasien</label>
                    <svg aria-hidden="true" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        type="search"
                        id="search"
                        name="search"
                        value="{{ $searchQuery }}"
                        placeholder="Cari kode pseudonim…"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                        class="compact-input w-full rounded-xl border border-slate-300 bg-white pl-9 pr-3 py-2 text-xs text-slate-800 focus:border-teal-600"
                    >
                </div>
                <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold whitespace-nowrap transition">
                    Cari
                </button>
                @if($searchQuery !== '' || $activeLabel !== '')
                    <a href="{{ route('skrining.index') }}" class="px-2.5 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-200/70 whitespace-nowrap transition">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <!-- Table Content -->
        @if($screenings->isEmpty())
            <div class="p-12 text-center space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                    <svg aria-hidden="true" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <p class="text-sm font-semibold text-slate-700">Tidak ada riwayat skrining yang sesuai filter.</p>
                <p class="text-xs text-slate-500">Mulai skrining baru atau ubah filter pencarian di atas.</p>
                <div class="pt-1">
                    <a href="{{ route('skrining.create') }}" class="inline-flex px-4 py-2.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-semibold text-xs transition">
                        + Buat Skrining Baru
                    </a>
                </div>
            </div>
        @else
            <div tabindex="0" role="region" aria-labelledby="history-table-caption" class="table-wrapper">
                <table class="text-left text-xs sm:text-sm">
                    <caption id="history-table-caption" class="visually-hidden">Tabel riwayat skrining pengguna</caption>
                    <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                        <tr>
                            <th scope="col" class="py-3.5 px-4">ID / Pseudonim</th>
                            <th scope="col" class="py-3.5 px-4">Tanggal</th>
                            <th scope="col" class="py-3.5 px-4">Indikator Kunci</th>
                            <th scope="col" class="py-3.5 px-4">Status Prediksi</th>
                            <th scope="col" class="py-3.5 px-4">Probabilitas</th>
                            <th scope="col" class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/70">
                        @foreach($screenings as $scr)
                            @php
                                $pred = $scr->prediction;
                                $probs = $pred?->probabilities ?? [];
                                $topProb = $pred ? (($probs[$pred->label] ?? 0) * 100) : 0;
                                $badge = match($pred?->label_code) {
                                    0 => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                    1 => 'bg-amber-50 text-amber-900 border-amber-200',
                                    default => 'bg-rose-50 text-rose-800 border-rose-200',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/90 transition-colors">
                                <th scope="row" class="py-3.5 px-4 font-mono text-xs font-bold text-slate-900 whitespace-nowrap">
                                    {{ $scr->patient_label ?: ('SCR-' . strtoupper(substr($scr->id, 0, 8))) }}
                                </th>
                                <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap">{{ $scr->created_at?->format('d M Y, H:i') }}</td>
                                <td class="py-3.5 px-4 text-xs text-slate-700 whitespace-nowrap">
                                    BMI <strong class="font-mono">{{ $scr->input['BMI'] ?? '-' }}</strong> ·
                                    Hipertensi <strong>{{ !empty($scr->input['HighBP']) ? 'Ya' : 'Tidak' }}</strong> ·
                                    GenHlth <strong class="font-mono">{{ $scr->input['GenHlth'] ?? '-' }}</strong>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($pred)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $badge }}">
                                            {{ $pred->label_id ?? $pred->label }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-mono text-xs font-bold text-slate-800 whitespace-nowrap">
                                    {{ number_format($topProb, 1, ',', '.') }}%
                                    <span class="font-sans font-normal text-slate-500 capitalize">({{ $pred?->confidence_note }})</span>
                                </td>
                                <td class="py-3.5 px-4 text-right space-x-1.5 whitespace-nowrap">
                                    <a href="{{ route('skrining.show', $scr->id) }}" class="inline-flex px-3 py-1.5 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-800 font-semibold text-xs transition">
                                        Detail
                                    </a>
                                    <a href="{{ route('skrining.pdf', $scr->id) }}" class="inline-flex px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                                        PDF
                                    </a>
                                    <form action="{{ route('skrining.destroy', $scr->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" aria-label="Hapus skrining {{ substr($scr->id, 0, 8) }}" class="inline-flex px-2.5 py-1.5 rounded-lg text-rose-600 hover:bg-rose-50 font-semibold text-xs transition">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3.5 border-t border-slate-200/80 bg-slate-50/40">
                {{ $screenings->links() }}
            </div>
        @endif
    </div>

    <!-- Dedicated Bottom Panel: Privasi & Hak Data UU PDP (Isolated Destructive Zone) -->
    <section id="privasi-uupdp" aria-labelledby="uupdp-heading" class="bg-white rounded-2xl border border-slate-200/90 p-5 sm:p-6 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-start gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 border border-rose-200/80 flex items-center justify-center shrink-0">
                <svg aria-hidden="true" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <div class="space-y-1">
                <h2 id="uupdp-heading" class="text-sm font-bold text-slate-900">Privasi &amp; Hak Penghapusan Data Pribadi (UU No. 27 Tahun 2022 / UU PDP)</h2>
                <p class="text-xs text-slate-600 leading-relaxed max-w-2xl">
                    Anda memiliki kendali penuh atas data riwayat skrining pada akun ini. Penghapusan permanen tidak dapat dibatalkan dan akan membersihkan seluruh riwayat prediksi serta narasi AI Anda.
                </p>
            </div>
        </div>
        @if($screenings->total() > 0)
            <button
                type="button"
                onclick="document.getElementById('purge-data-dialog').showModal()"
                class="px-4 py-2.5 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold text-xs whitespace-nowrap transition shrink-0"
            >
                Hapus Semua Data Saya (UU PDP)
            </button>
        @else
            <span class="text-xs font-medium text-slate-400 whitespace-nowrap">Tidak ada data untuk dihapus</span>
        @endif
    </section>

    <!-- Native Accessible Modal Dialog (<dialog>) for Purging Data (per dialog guide) -->
    <dialog id="purge-data-dialog" closedby="any" aria-labelledby="purge-dialog-title" class="rounded-2xl p-6 max-w-md w-full border border-slate-200 shadow-2xl">
        <h1 id="purge-dialog-title" class="text-lg font-extrabold text-slate-900">Konfirmasi Penghapusan Seluruh Riwayat</h1>
        <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
            Sesuai hak subjek data pada <strong>UU No. 27 Tahun 2022 (UU PDP)</strong>, tindakan ini akan menghapus seluruh data skrining, hasil prediksi XGBoost, kontribusi SHAP, dan narasi LLM pada akun Anda secara permanen.
        </p>
        <div class="mt-6 flex items-center justify-end gap-2.5">
            <form method="dialog">
                <button type="submit" value="cancel" autofocus class="px-4 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-xs font-bold text-slate-700 transition">
                    Batal
                </button>
            </form>
            <form action="{{ route('skrining.purge') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition">
                    Ya, Hapus Permanen
                </button>
            </form>
        </div>
    </dialog>
</div>
@endsection
