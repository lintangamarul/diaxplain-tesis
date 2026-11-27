@extends('layouts.app')

@section('title', 'Masuk ke Akun DiaXplain')

@section('content')
<div class="max-w-md mx-auto my-6">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900">Masuk ke DiaXplain</h1>
            <p class="text-xs sm:text-sm text-slate-600 mt-1">Gunakan akun terdaftar atau klik tombol akun demo di bawah untuk pengujian instan.</p>
        </div>

        <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-bold text-slate-800 mb-1">Alamat Email</label>
                <span id="email-hint" class="block text-xs text-slate-500 mb-1.5">Contoh: admin@diaxplain.id atau pengguna@diaxplain.id</span>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="username"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                    aria-describedby="email-hint"
                    class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-slate-900 focus:border-teal-600"
                >
                <p class="field-error-msg hidden text-xs text-red-600 mt-1 items-center gap-1">
                    <span aria-hidden="true">⚠</span> Masukkan format alamat email yang valid.
                </p>
            </div>

            <div>
                <label for="password" class="block text-sm font-bold text-slate-800 mb-1">Kata Sandi</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-slate-900 focus:border-teal-600"
                >
            </div>

            <div class="flex items-center justify-between text-xs">
                <label for="remember" class="inline-flex items-center gap-2 text-slate-700 cursor-pointer py-1">
                    <input type="checkbox" id="remember" name="remember" class="w-4 h-4 rounded accent-teal-600">
                    <span>Ingat sesi saya</span>
                </label>
                <a href="{{ route('register') }}" class="font-semibold text-teal-700 hover:underline">Belum punya akun? Daftar</a>
            </div>

            <button type="submit" enterkeyhint="done" class="w-full py-3 px-4 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-sm shadow-xs transition tap-target">
                Masuk Sekarang
            </button>
        </form>

        <!-- Quick Demo Accounts Card -->
        <div class="pt-4 border-t border-slate-200 space-y-2.5">
            <p class="text-xs font-bold text-slate-600 uppercase tracking-wider">Akses Cepat Akun Demo (Password: <code class="font-mono">password</code>)</p>
            <div class="grid grid-cols-1 gap-2">
                <form action="{{ route('demo.login', 'admin') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full text-left px-3.5 py-2.5 rounded-xl border border-purple-200 bg-purple-50/60 hover:bg-purple-100/80 text-xs flex items-center justify-between transition">
                        <span><strong class="text-purple-900">Admin & Peneliti</strong> (Eksperimen E0–E6 & Input API Key)</span>
                        <span class="font-mono text-purple-700">admin@diaxplain.id →</span>
                    </button>
                </form>
                <form action="{{ route('demo.login', 'nakes') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full text-left px-3.5 py-2.5 rounded-xl border border-sky-200 bg-sky-50/60 hover:bg-sky-100/80 text-xs flex items-center justify-between transition">
                        <span><strong class="text-sky-900">Tenaga Medis</strong> (Skrining Pasien & Penilaian Narasi)</span>
                        <span class="font-mono text-sky-700">nakes@diaxplain.id →</span>
                    </button>
                </form>
                <form action="{{ route('demo.login', 'pengguna') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full text-left px-3.5 py-2.5 rounded-xl border border-emerald-200 bg-emerald-50/60 hover:bg-emerald-100/80 text-xs flex items-center justify-between transition">
                        <span><strong class="text-emerald-900">Pengguna Umum</strong> (Skrining Mandiri & Laporan PDF)</span>
                        <span class="font-mono text-emerald-700">pengguna@diaxplain.id →</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

