@extends('layouts.app')

@section('title', 'Registri Versi Model XGBoost')
@section('page_title', 'Tata Kelola · Registri Model')

@section('content')
<div class="space-y-6">
    @include('admin.partials.governance-tabs')

    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200/80">
            <h1 class="text-xl font-extrabold text-slate-900">Registri Versi Model Klasifikasi Diabetes</h1>
            <p class="text-xs text-slate-500 mt-0.5">Kelola versi model XGBoost yang aktif melayani prediksi skrining pengguna.</p>
        </div>

        <div class="table-wrapper">
            <table class="text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                    <tr>
                        <th scope="col" class="py-3 px-4">Tag Versi</th>
                        <th scope="col" class="py-3 px-4">Nama Model</th>
                        <th scope="col" class="py-3 px-4">Teknik</th>
                        <th scope="col" class="py-3 px-4 text-right">Macro-F1</th>
                        <th scope="col" class="py-3 px-4 text-right">Balanced Acc</th>
                        <th scope="col" class="py-3 px-4 text-right">Prediksi</th>
                        <th scope="col" class="py-3 px-4">Status</th>
                        <th scope="col" class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/70">
                    @forelse($models as $m)
                        @php $tm = $m->metrics['test_metrics'] ?? ($m->metrics ?? []); @endphp
                        <tr class="hover:bg-slate-50/80">
                            <th scope="row" class="py-3.5 px-4 font-mono font-bold text-slate-900 whitespace-nowrap">{{ $m->version_tag }}</th>
                            <td class="py-3.5 px-4 font-semibold text-slate-800 whitespace-nowrap">{{ $m->name }}</td>
                            <td class="py-3.5 px-4 font-mono text-slate-600 whitespace-nowrap">{{ $m->technique }}</td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-teal-700">{{ number_format((float) ($tm['f1_macro'] ?? 0) * 100, 2, ',', '.') }}%</td>
                            <td class="py-3.5 px-4 text-right font-mono">{{ number_format((float) ($tm['balanced_accuracy'] ?? 0) * 100, 2, ',', '.') }}%</td>
                            <td class="py-3.5 px-4 text-right font-mono">{{ $m->predictions_count }}</td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($m->is_active)
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold">Aktif</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200 font-semibold">Arsip</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                @if(!$m->is_active)
                                    <form method="POST" action="{{ route('admin.models.activate', $m) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-teal-700 hover:bg-teal-800 text-white font-semibold transition">Aktifkan</button>
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
