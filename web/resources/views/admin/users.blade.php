@extends('layouts.app')

@section('title', 'Manajemen Pengguna & Peran (RBAC)')
@section('page_title', 'Tata Kelola · Pengguna & RBAC')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        @include('admin.partials.governance-tabs')
        <button type="button" onclick="document.getElementById('addUserDialog').showModal()" class="px-4 py-2.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white text-xs font-semibold whitespace-nowrap shadow-xs transition">
            + Tambah Pengguna Baru
        </button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200/80">
            <h1 class="text-xl font-extrabold text-slate-900">Manajemen Pengguna &amp; Hak Akses (RBAC)</h1>
            <p class="text-xs text-slate-500 mt-0.5">Kelola peran pengguna (Pengguna Umum, Tenaga Medis, Admin &amp; Peneliti) serta status aktivasi akun.</p>
        </div>

        <div class="table-wrapper">
            <table class="text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                    <tr>
                        <th scope="col" class="py-3 px-4">Nama</th>
                        <th scope="col" class="py-3 px-4">Email</th>
                        <th scope="col" class="py-3 px-4">Peran</th>
                        <th scope="col" class="py-3 px-4 text-right">Skrining</th>
                        <th scope="col" class="py-3 px-4 text-right">Penilaian LLM</th>
                        <th scope="col" class="py-3 px-4">Ubah Peran / Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/70">
                    @foreach($users as $u)
                        <tr class="hover:bg-slate-50/80">
                            <th scope="row" class="py-3.5 px-4 font-bold text-slate-900 whitespace-nowrap">{{ $u->displayName() }}</th>
                            <td class="py-3.5 px-4 font-mono text-slate-600 whitespace-nowrap">{{ $u->email }}</td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-md font-semibold border text-[11px] {{ $u->roleBadgeClasses() }}">
                                    {{ $u->roleBadgeLabel() }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono">{{ $u->screenings_count }}</td>
                            <td class="py-3.5 px-4 text-right font-mono">{{ $u->narrative_ratings_count }}</td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <form method="POST" action="{{ route('admin.users.role', $u) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role" aria-label="Peran untuk {{ $u->displayName() }}" class="compact-input rounded-lg border border-slate-300 bg-white text-xs py-1.5 pl-2 pr-6">
                                        <option value="pengguna" {{ $u->role === 'pengguna' ? 'selected' : '' }}>Pengguna</option>
                                        <option value="nakes" {{ $u->role === 'nakes' ? 'selected' : '' }}>Tenaga Medis</option>
                                        <option value="admin" {{ $u->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                    </select>
                                    <select name="is_active" aria-label="Status akun {{ $u->displayName() }}" class="compact-input rounded-lg border border-slate-300 bg-white text-xs py-1.5 pl-2 pr-6">
                                        <option value="1" {{ $u->is_active ? 'selected' : '' }}>Aktif</option>
                                        <option value="0" {{ !$u->is_active ? 'selected' : '' }}>Nonaktif</option>
                                    </select>
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold transition">Simpan</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Native Accessible HTML Dialog for Adding User (per dialog guide) -->
    <dialog id="addUserDialog" closedby="any" aria-labelledby="add-user-dialog-title" class="rounded-2xl border border-slate-200 p-0 shadow-2xl w-full max-w-md">
        <div class="p-6">
            <div class="flex items-center justify-between pb-4 border-b border-slate-200/80">
                <h1 id="add-user-dialog-title" class="text-base font-extrabold text-slate-900">Tambah Pengguna Baru</h1>
                <form method="dialog">
                    <button type="submit" value="cancel" aria-label="Tutup dialog" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition">✕</button>
                </form>
            </div>
            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4 mt-4">
                @csrf
                <div>
                    <label for="new_name" class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap</label>
                    <input type="text" id="new_name" name="name" required autofocus class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label for="new_email" class="block text-xs font-bold text-slate-700 mb-1">Alamat Email</label>
                    <input type="email" id="new_email" name="email" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label for="new_role" class="block text-xs font-bold text-slate-700 mb-1">Peran</label>
                    <select id="new_role" name="role" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="pengguna">Pengguna Umum</option>
                        <option value="nakes">Tenaga Medis / Klinisi</option>
                        <option value="admin">Admin &amp; Peneliti</option>
                    </select>
                </div>
                <div>
                    <label for="new_password" class="block text-xs font-bold text-slate-700 mb-1">Kata Sandi Awal</label>
                    <input type="password" id="new_password" name="password" minlength="8" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-200/80">
                    <button type="button" onclick="this.closest('dialog').close('cancel')" class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-700">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-teal-700 hover:bg-teal-800 text-white text-xs font-bold">Simpan Pengguna</button>
                </div>
            </form>
        </div>
    </dialog>
</div>
@endsection
