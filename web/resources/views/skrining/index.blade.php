@extends('layouts.app')

@section('title', 'Riwayat Skrining & Kendali Data Pribadi')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900">Riwayat Skrining Kesehatan</h1>
            <p class="text-xs sm:text-sm text-slate-600">Daftar seluruh hasil analisis skrining XGBoost + TreeSHAP + LLM yang pernah Anda buat.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('skrining.create') }}" class="px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs transition">
                + Skrining Baru
            </a>
            <a href="{{ route('skrining.compare') }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs transition">
                Bandingkan 2 Skrining
            </a>
            @if($screenings->total() > 0)
                <button
                    type="button"
                    onclick="document.getElementById('purge-data-dialog').showModal()"
                    class="px-3.5 py-2.5 rounded-xl border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 font-bold text-xs transition"
                >
                    Hapus Semua Data Saya (UU PDP)
                </button>
            @endif
        </div>
    </div>

    <!-- Filter Form -->
    <form action="{{ route('skrining.index') }}" method="GET" class="bg-white rounded-2xl p-4 border border-slate-200 flex flex-wrap items-end gap-3 text-xs">
        <div class="flex-1 min-w-[200px]">
            <label for="search" class="block font-bold text-slate-700 mb-1">Cari Kode Pseudonim Pasien</label>
            <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Misal: PASIEN-KLINIK-014" autocorrect="off" autocapitalize="off" spellcheck="false" class="w-full rounded-xl border border-slate-300 px-3 py-2">
        </div>
        <div class="w-48">
            <label for="label" class="block font-bold text-slate-700 mb-1">Filter Kelas Prediksi</label>
            <select id="label" name="label" class="w-full rounded-xl border border-slate-300 px-3 py-2 bg-white">
                <option value="">Semua Kelas</option>
                <option value="No Diabetes" {{ request('label') === 'No Diabetes' ? 'selected' : '' }}>Tidak Diabetes (0)</option>
                <option value="Prediabetes" {{ request('label') === 'Prediabetes' ? 'selected' : '' }}>Prediabetes (1)</option>
                <option value="Diabetes" {{ request('label') === 'Diabetes' ? 'selected' : '' }}>Diabetes (2)</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2.5 rounded-xl bg-slate-900 text-white font-bold tap-target">Terapkan Filter</button>
    </form>

    <!-- Screenings Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        @if($screenings->isEmpty())
            <div class="p-10 text-center space-y-3">
                <p class="text-sm text-slate-600">Tidak ada riwayat skrining yang ditemukan.</p>
                <a href="{{ route('skrining.create') }}" class="inline-flex px-4 py-2.5 rounded-xl bg-teal-600 text-white font-bold text-xs">
                    + Buat Skrining Baru
                </a>
            </div>
        @else
            <div tabindex="0" role="region" aria-labelledby="history-table-caption" class="table-wrapper">
                <table class="text-left text-xs sm:text-sm">
                    <caption id="history-table-caption" class="visually-hidden">Tabel riwayat skrining pengguna</caption>
                    <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                        <tr>
                            <th scope="col" class="py-3.5 px-4">ID / Pseudonim</th>
                            <th scope="col" class="py-3.5 px-4">Tanggal</th>
                            <th scope="col" class="py-3.5 px-4">Indikator Kunci</th>
                            <th scope="col" class="py-3.5 px-4">Status Prediksi</th>
                            <th scope="col" class="py-3.5 px-4">Probabilitas</th>
                            <th scope="col" class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($screenings as $scr)
                            @php
                                $pred = $scr->prediction;
                                $probs = $pred?->probabilities ?? [];
                                $topProb = $pred ? (($probs[$pred->label] ?? 0) * 100) : 0;
                                $badge = match($pred?->label_code) {
                                    0 => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                    1 => 'bg-amber-100 text-amber-900 border-amber-300',
                                    default => 'bg-rose-100 text-rose-800 border-rose-300',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <th scope="row" class="py-3.5 px-4 font-mono text-xs font-bold text-slate-900">
                                    {{ $scr->patient_label ?: ('SCR-' . strtoupper(substr($scr->id, 0, 8))) }}
                                </th>
                                <td class="py-3.5 px-4 text-slate-600">{{ $scr->created_at?->format('d M Y, H:i') }}</td>
                                <td class="py-3.5 px-4 text-xs text-slate-700">
                                    BMI: <strong>{{ $scr->input['BMI'] ?? '-' }}</strong> ·
                                    Hipertensi: <strong>{{ !empty($scr->input['HighBP']) ? 'Ya' : 'Tidak' }}</strong> ·
                                    GenHlth: <strong>{{ $scr->input['GenHlth'] ?? '-' }}</strong>
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($pred)
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $badge }}">
                                            {{ $pred->label_id ?? $pred->label }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-mono text-xs font-bold text-slate-800">
                                    {{ number_format($topProb, 1, ',', '.') }}% ({{ $pred?->confidence_note }})
                                </td>
                                <td class="py-3.5 px-4 text-right space-x-1.5 whitespace-nowrap">
                                    <a href="{{ route('skrining.show', $scr->id) }}" class="px-3 py-1.5 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-800 font-bold text-xs">
                                        Detail SPK
                                    </a>
                                    <a href="{{ route('skrining.pdf', $scr->id) }}" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs">
                                        PDF
                                    </a>
                                    <form action="{{ route('skrining.destroy', $scr->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" aria-label="Hapus skrining {{ substr($scr->id, 0, 8) }}" class="px-2.5 py-1.5 rounded-lg text-red-600 hover:bg-red-50 font-semibold text-xs">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-200">
                {{ $screenings->links() }}
            </div>
        @endif
    </div>

    <!-- Native Accessible Modal Dialog (<dialog>) for Purging Data -->
    <dialog id="purge-data-dialog" aria-labelledby="purge-dialog-title" class="rounded-2xl p-6 max-w-md w-full border border-slate-200 shadow-2xl">
        <h2 id="purge-dialog-title" class="text-lg font-extrabold text-slate-900">Konfirmasi Penghapusan Seluruh Riwayat Skrining</h2>
        <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
            Sesuai hak subjek data pada <strong>UU No. 27 Tahun 2022 (UU PDP)</strong>, tindakan ini akan menghapus seluruh data skrining, hasil prediksi, dan narasi pada akun Anda secara permanen.
        </p>
        <div class="mt-6 flex items-center justify-end gap-2.5">
            <button type="button" onclick="this.closest('dialog').close()" class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-700">
                Batal
            </button>
            <form action="{{ route('skrining.purge') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold">
                    Ya, Hapus Permanen Semua Data Saya
                </button>
            </form>
        </div>
    </dialog>
</div>
@endsection

