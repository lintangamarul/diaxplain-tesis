@extends('layouts.guest')

@section('title', 'Daftar Akun Pengguna Baru')

@section('content')
<div class="max-w-md mx-auto my-4 sm:my-8">
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-6 sm:p-8 space-y-6">
        <div class="space-y-1.5">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-200/80">
                Pendaftaran Pengguna Umum
            </span>
            <h1 class="text-2xl font-extrabold text-slate-900">Daftar Akun DiaXplain</h1>
            <p class="text-xs sm:text-sm text-slate-600">Buat akun untuk menyimpan riwayat skrining kesehatan mandiri dan mengunduh laporan PDF.</p>
        </div>

        <form action="{{ route('register.post') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-sm font-semibold text-slate-800 mb-1">Nama Lengkap</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    maxlength="100"
                    autocomplete="name"
                    class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-slate-900 focus:border-teal-600"
                >
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-slate-800 mb-1">Alamat Email</label>
                <span id="reg-email-hint" class="block text-xs text-slate-500 mb-1.5">Gunakan email aktif untuk masuk ke akun Anda.</span>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                    aria-describedby="reg-email-hint"
                    class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-slate-900 focus:border-teal-600"
                >
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-slate-800 mb-1">Kata Sandi Baru</label>
                <span id="pwd-rules" class="block text-xs text-slate-500 mb-1.5">Minimal 8 karakter.</span>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    aria-describedby="pwd-rules"
                    class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-slate-900 focus:border-teal-600"
                >
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-slate-800 mb-1">Konfirmasi Kata Sandi</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-slate-900 focus:border-teal-600"
                >
            </div>

            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80">
                <label for="consent" class="flex items-start gap-2.5 text-xs text-slate-700 cursor-pointer leading-relaxed">
                    <input type="checkbox" id="consent" name="consent" value="1" required class="mt-0.5 w-4 h-4 rounded accent-teal-700">
                    <span>Saya memahami bahwa <strong>DiaXplain</strong> adalah sistem pendukung keputusan untuk skrining awal risiko diabetes (bukan alat diagnosis medis) dan menyetujui pemrosesan indikator kesehatan secara anonim.</span>
                </label>
            </div>

            <button type="submit" enterkeyhint="done" class="w-full py-3 px-4 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-bold text-sm shadow-xs transition tap-target">
                Buat Akun &amp; Mulai Skrining
            </button>

            <p class="text-center text-xs text-slate-500 pt-1">
                Sudah memiliki akun? <a href="{{ route('login') }}" class="font-semibold text-teal-700 hover:underline">Masuk di sini</a>
            </p>
        </form>
    </div>
</div>
@endsection
