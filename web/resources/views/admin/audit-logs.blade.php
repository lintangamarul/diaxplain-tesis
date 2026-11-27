@extends('layouts.app')

@section('title', 'Log Audit Keamanan & Privasi')
@section('page_title', 'Tata Kelola · Log Audit')

@section('content')
<div class="space-y-6">
    @include('admin.partials.governance-tabs')

    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200/80">
            <h1 class="text-xl font-extrabold text-slate-900">Jejak Audit Aktivitas &amp; Keamanan Sistem</h1>
            <p class="text-xs text-slate-500 mt-0.5">Pencatatan otomatis aktivitas sensitif (login, pembuatan skrining, perubahan API key, penilaian pakar, dan ekspor data).</p>
        </div>

        <div class="table-wrapper">
            <table class="text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                    <tr>
                        <th scope="col" class="py-3 px-4">Waktu</th>
                        <th scope="col" class="py-3 px-4">Pengguna</th>
                        <th scope="col" class="py-3 px-4">Aksi</th>
                        <th scope="col" class="py-3 px-4">Target</th>
                        <th scope="col" class="py-3 px-4">Metadata</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/70">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-3 px-4 text-slate-500 font-mono whitespace-nowrap">{{ $log->created_at?->format('d M Y H:i:s') }}</td>
                            <th scope="row" class="py-3 px-4 font-semibold text-slate-800 whitespace-nowrap">{{ $log->user?->displayName() ?? 'Sistem / Tamu' }}</th>
                            <td class="py-3 px-4 font-mono font-bold text-teal-800 whitespace-nowrap">{{ $log->aksi }}</td>
                            <td class="py-3 px-4 font-mono text-slate-700 whitespace-nowrap">{{ $log->target ?? '-' }}</td>
                            <td class="py-3 px-4 font-mono text-[11px] text-slate-500 max-w-md truncate">{{ !empty($log->meta) ? json_encode($log->meta) : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500">Belum ada catatan audit.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-3.5 border-t border-slate-200/80 bg-slate-50/40">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
