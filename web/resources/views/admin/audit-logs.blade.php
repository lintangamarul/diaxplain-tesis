@extends('layouts.app')

@section('title', 'Log Audit Keamanan & Privasi')

@section('content')
<div class="space-y-6">
    <!-- Admin Sub-navigation -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white rounded-2xl border border-slate-200/80 px-5 py-3 shadow-sm">
        <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
            <a href="{{ route('admin.experiments') }}" class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100">Dasbor Eksperimen (RM1–RM6)</a>
            <a href="{{ route('admin.llm.config') }}" class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100">API Key Layanan AI (LLM)</a>
            <a href="{{ route('admin.models') }}" class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100">Registri Model</a>
            <a href="{{ route('admin.users') }}" class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100">Manajemen Pengguna</a>
            <a href="{{ route('admin.audit') }}" class="px-3 py-1.5 rounded-xl bg-teal-50 text-teal-800 border border-teal-200">Log Audit</a>
        </div>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="mb-6">
            <h1 class="text-2xl font-extrabold text-slate-900">Jejak Audit Aktivitas & Keamanan Sistem</h1>
            <p class="text-xs text-slate-500 mt-1">Pencatatan otomatis aktivitas sensitif (login, pembuatan skrining, perubahan API key, penilaian pakar, dan ekspor data).</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 text-slate-500 uppercase bg-slate-50/80">
                        <th class="py-3 px-3">Waktu</th>
                        <th class="py-3 px-3">Pengguna</th>
                        <th class="py-3 px-3">Aksi</th>
                        <th class="py-3 px-3">Target</th>
                        <th class="py-3 px-3">Alamat IP</th>
                        <th class="py-3 px-3">Metadata</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60">
                    @forelse($logs as $log)
                        <tr>
                            <td class="py-3 px-3 text-slate-500 font-mono">{{ $log->created_at?->format('d M Y H:i:s') }}</td>
                            <td class="py-3 px-3 font-semibold text-slate-800">{{ $log->user?->name ?? 'Sistem / Tamu' }}</td>
                            <td class="py-3 px-3 font-mono font-bold text-teal-800">{{ $log->action }}</td>
                            <td class="py-3 px-3 font-mono text-slate-700">{{ $log->target ?? '-' }}</td>
                            <td class="py-3 px-3 font-mono text-slate-500">{{ $log->ip_address ?? '-' }}</td>
                            <td class="py-3 px-3 font-mono text-[11px] text-slate-500">{{ $log->metadata_json ? json_encode($log->metadata_json) : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">Belum ada catatan audit.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
