@extends('layouts.app')

@section('title', 'Manajemen Pengguna & Peran (RBAC)')

@section('content')
<div class="space-y-6">
    <!-- Admin Sub-navigation -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white rounded-2xl border border-slate-200/80 px-5 py-3 shadow-sm">
        <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
            <a href="{{ route('admin.experiments') }}" class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100">Dasbor Eksperimen (RM1–RM6)</a>
            <a href="{{ route('admin.llm.config') }}" class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100">API Key Layanan AI (LLM)</a>
            <a href="{{ route('admin.models') }}" class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100">Registri Model</a>
            <a href="{{ route('admin.users') }}" class="px-3 py-1.5 rounded-xl bg-teal-50 text-teal-800 border border-teal-200">Manajemen Pengguna</a>
            <a href="{{ route('admin.audit') }}" class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100">Log Audit</a>
        </div>
        <button type="button" onclick="document.getElementById('addUserDialog').showModal()" class="px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition shadow-sm">
            + Tambah Pengguna Baru
        </button>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="mb-6">
            <h1 class="text-2xl font-extrabold text-slate-900">Manajemen Pengguna & Hak Akses (RBAC)</h1>
            <p class="text-xs text-slate-500 mt-1">Atur peran pengguna (`pengguna`, `nakes`, `admin`) dan status aktivasi akun.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 text-slate-500 uppercase bg-slate-50/80">
                        <th class="py-3 px-3">Nama</th>
                        <th class="py-3 px-3">Email</th>
                        <th class="py-3 px-3">Peran (Role)</th>
                        <th class="py-3 px-3 text-right">Skrining</th>
                        <th class="py-3 px-3 text-right">Penilaian LLM</th>
                        <th class="py-3 px-3">Ubah Peran / Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60">
                    @foreach($users as $u)
                        <tr>
                            <td class="py-3 px-3 font-bold text-slate-900">{{ $u->name }}</td>
                            <td class="py-3 px-3 font-mono text-slate-600">{{ $u->email }}</td>
                            <td class="py-3 px-3">
                                <span class="px-2.5 py-0.5 rounded-full font-bold uppercase text-[10px] {{ $u->role === 'admin' ? 'bg-indigo-100 text-indigo-800' : ($u->role === 'nakes' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700') }}">
                                    {{ $u->role }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-right font-mono">{{ $u->screenings_count }}</td>
                            <td class="py-3 px-3 text-right font-mono">{{ $u->narrative_ratings_count }}</td>
                            <td class="py-3 px-3">
                                <form method="POST" action="{{ route('admin.users.role', $u) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role" aria-label="Peran untuk {{ $u->name }}" class="rounded-lg border-slate-300 text-xs py-1 pl-2 pr-6">
                                        <option value="pengguna" {{ $u->role === 'pengguna' ? 'selected' : '' }}>Pengguna</option>
                                        <option value="nakes" {{ $u->role === 'nakes' ? 'selected' : '' }}>Tenaga Medis</option>
                                        <option value="admin" {{ $u->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                    </select>
                                    <select name="is_active" aria-label="Status akun {{ $u->name }}" class="rounded-lg border-slate-300 text-xs py-1 pl-2 pr-6">
                                        <option value="1" {{ $u->is_active ? 'selected' : '' }}>Aktif</option>
                                        <option value="0" {{ !$u->is_active ? 'selected' : '' }}>Nonaktif</option>
                                    </select>
                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold">Simpan</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Native HTML Dialog for Adding User -->
    <dialog id="addUserDialog" class="rounded-3xl border border-slate-200 p-0 shadow-2xl backdrop:bg-slate-900/50 w-full max-w-md">
        <div class="p-6">
            <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                <h2 class="text-base font-extrabold text-slate-900">Tambah Pengguna Baru</h2>
                <button type="button" onclick="document.getElementById('addUserDialog').close()" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
            </div>
            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4 mt-4">
                @csrf
                <div>
                    <label for="new_name" class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap</label>
                    <input type="text" id="new_name" name="name" required class="w-full rounded-xl border-slate-300 text-sm">
                </div>
                <div>
                    <label for="new_email" class="block text-xs font-bold text-slate-700 mb-1">Alamat Email</label>
                    <input type="email" id="new_email" name="email" required class="w-full rounded-xl border-slate-300 text-sm">
                </div>
                <div>
                    <label for="new_role" class="block text-xs font-bold text-slate-700 mb-1">Peran</label>
                    <select id="new_role" name="role" class="w-full rounded-xl border-slate-300 text-sm">
                        <option value="pengguna">Pengguna (Masyarakat)</option>
                        <option value="nakes">Tenaga Medis (Penilai LLM)</option>
                        <option value="admin">Admin / Peneliti</option>
                    </select>
                </div>
                <div>
                    <label for="new_password" class="block text-xs font-bold text-slate-700 mb-1">Kata Sandi Awal</label>
                    <input type="password" id="new_password" name="password" minlength="8" required class="w-full rounded-xl border-slate-300 text-sm">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                    <button type="button" onclick="document.getElementById('addUserDialog').close()" class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-700">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold">Simpan Pengguna</button>
                </div>
            </form>
        </div>
    </dialog>
</div>
@endsection
