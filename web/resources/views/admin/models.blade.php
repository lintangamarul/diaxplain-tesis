@extends('layouts.app')

@section('title', 'Registri Versi Model XGBoost')

@section('content')
<div class="space-y-6">
    <!-- Admin Sub-navigation -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white rounded-2xl border border-slate-200/80 px-5 py-3 shadow-sm">
        <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
            <a href="{{ route('admin.experiments') }}" class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100">Dasbor Eksperimen (RM1–RM6)</a>
            <a href="{{ route('admin.llm.config') }}" class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100">API Key Layanan AI (LLM)</a>
            <a href="{{ route('admin.models') }}" class="px-3 py-1.5 rounded-xl bg-teal-50 text-teal-800 border border-teal-200">Registri Model</a>
            <a href="{{ route('admin.users') }}" class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100">Manajemen Pengguna</a>
            <a href="{{ route('admin.audit') }}" class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100">Log Audit</a>
        </div>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="mb-6">
            <h1 class="text-2xl font-extrabold text-slate-900">Registri Versi Model Klasifikasi Diabetes</h1>
            <p class="text-xs text-slate-500 mt-1">Kelola versi model XGBoost yang aktif digunakan untuk melayani skrining pengguna.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 text-slate-500 uppercase bg-slate-50/80">
                        <th class="py-3 px-3">Tag Versi</th>
                        <th class="py-3 px-3">Nama Model & Skenario</th>
                        <th class="py-3 px-3">Skenario Terpilih</th>
                        <th class="py-3 px-3 text-right">Macro-F1</th>
                        <th class="py-3 px-3 text-right">Balanced Acc</th>
                        <th class="py-3 px-3 text-right">Total Prediksi</th>
                        <th class="py-3 px-3">Status</th>
                        <th class="py-3 px-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60">
                    @forelse($models as $m)
                        <tr>
                            <td class="py-3 px-3 font-mono font-bold text-slate-900">{{ $m->version_tag }}</td>
                            <td class="py-3 px-3 font-semibold text-slate-800">{{ $m->name }}</td>
                            <td class="py-3 px-3 font-mono">{{ $m->code }}</td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-teal-700">{{ number_format((float)($m->metrics_json['f1_macro'] ?? 0) * 100, 2, ',', '.') }}%</td>
                            <td class="py-3 px-3 text-right font-mono">{{ number_format((float)($m->metrics_json['balanced_accuracy'] ?? 0) * 100, 2, ',', '.') }}%</td>
                            <td class="py-3 px-3 text-right font-mono">{{ $m->predictions_count }}</td>
                            <td class="py-3 px-3">
                                @if($m->is_active)
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold">Aktif</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 font-semibold">Arsip</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right">
                                @if(!$m->is_active)
                                    <form method="POST" action="{{ route('admin.models.activate', $m) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3 py-1 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold">Aktifkan</button>
                                    </form>
                                @else
                                    <span class="text-slate-400">Sedang Digunakan</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-500">Belum ada versi model terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
