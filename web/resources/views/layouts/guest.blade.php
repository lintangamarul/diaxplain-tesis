<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SPK Klasifikasi Status Diabetes') | DiaXplain</title>
    @include('layouts.partials.head-assets')
</head>
<body class="min-h-full bg-[#F8FAFC] text-slate-800 antialiased flex flex-col font-sans">
    <!-- Accessible Skip Link -->
    <a href="#main-content" class="skip-link visually-hidden">Lewati ke konten utama</a>

    <!-- Clean Public Top Navigation (68px) -->
    <header class="sticky top-0 z-40 h-[68px] bg-white/95 backdrop-blur border-b border-slate-200/80 flex items-center">
        <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
            <!-- Brand -->
            <a href="{{ route('landing') }}" class="flex items-center gap-2.5 shrink-0 group">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-teal-700 to-teal-500 flex items-center justify-center text-white shadow-xs group-hover:shadow-sm transition">
                    <svg aria-hidden="true" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div class="flex items-center gap-2">
                    <span class="font-extrabold text-lg tracking-tight text-slate-900">Dia<span class="text-teal-700">Xplain</span></span>
                    <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-teal-50 text-teal-800 border border-teal-200/80">
                        XGBoost · SHAP · LLM
                    </span>
                </div>
            </a>

            <!-- Primary Public Links (Desktop) -->
            <nav aria-label="Navigasi Utama" class="hidden md:flex items-center gap-1">
                <a
                    href="{{ route('landing') }}"
                    @if(request()->routeIs('landing')) aria-current="page" @endif
                    class="dx-public-link px-3.5 py-2 rounded-lg text-sm font-semibold whitespace-nowrap transition {{ request()->routeIs('landing') ? 'text-teal-800' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70' }}"
                >
                    Beranda
                </a>
                <a
                    href="{{ route('about') }}"
                    @if(request()->routeIs('about')) aria-current="page" @endif
                    class="dx-public-link px-3.5 py-2 rounded-lg text-sm font-semibold whitespace-nowrap transition {{ request()->routeIs('about') ? 'text-teal-800' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70' }}"
                >
                    Metodologi &amp; Keterbatasan
                </a>
                @auth
                    <a href="{{ route('dashboard') }}" class="dx-public-link px-3.5 py-2 rounded-lg text-sm font-semibold whitespace-nowrap text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 transition">
                        Workspace Aplikasi
                    </a>
                @endauth
            </nav>

            <!-- Right Auth Actions + Mobile Popover Menu -->
            <div class="flex items-center gap-2.5 shrink-0">
                @auth
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-xl bg-teal-700 hover:bg-teal-800 text-white text-sm font-semibold whitespace-nowrap shadow-xs transition">
                        Buka Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-3.5 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100 whitespace-nowrap transition">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}" class="px-4 py-2 rounded-xl bg-teal-700 hover:bg-teal-800 text-white text-sm font-semibold whitespace-nowrap shadow-xs transition">
                        Daftar Akun
                    </a>
                @endauth

                <!-- Mobile Menu Trigger (Native Popover) -->
                <button
                    type="button"
                    popovertarget="public-mobile-menu"
                    popovertargetaction="toggle"
                    aria-controls="public-mobile-menu"
                    aria-expanded="false"
                    aria-label="Buka menu navigasi"
                    class="md:hidden w-10 h-10 rounded-xl border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-100 transition"
                >
                    <svg aria-hidden="true" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div id="public-mobile-menu" popover="auto" class="dx-menu-popover w-60 p-2 md:hidden">
                    <div class="flex flex-col gap-1 text-sm font-semibold">
                        <a href="{{ route('landing') }}" @if(request()->routeIs('landing')) aria-current="page" @endif class="px-3 py-2.5 rounded-lg {{ request()->routeIs('landing') ? 'bg-teal-50 text-teal-800' : 'text-slate-700 hover:bg-slate-50' }}">
                            Beranda
                        </a>
                        <a href="{{ route('about') }}" @if(request()->routeIs('about')) aria-current="page" @endif class="px-3 py-2.5 rounded-lg {{ request()->routeIs('about') ? 'bg-teal-50 text-teal-800' : 'text-slate-700 hover:bg-slate-50' }}">
                            Metodologi &amp; Keterbatasan
                        </a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="px-3 py-2.5 rounded-lg text-teal-700 hover:bg-teal-50">
                                Workspace Aplikasi
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Notifications (Polite Live Region) -->
    <div aria-live="polite" class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 mt-4 empty:mt-0">
        @if(session('success'))
            <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 p-4 flex items-start gap-3 text-emerald-900 shadow-2xs">
                <svg aria-hidden="true" class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm font-medium">{{ session('success') }}</div>
            </div>
        @endif

        @if(session('warning'))
            <div class="mb-4 rounded-xl bg-amber-50 border border-amber-300 p-4 flex items-start gap-3 text-amber-900 shadow-2xs">
                <svg aria-hidden="true" class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="text-sm font-medium">{{ session('warning') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div role="alert" class="mb-4 rounded-xl bg-rose-50 border border-rose-200 p-4 text-rose-900 shadow-2xs">
                <div class="flex items-center gap-2 font-bold text-sm mb-1">
                    <svg aria-hidden="true" class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Mohon periksa kembali isian berikut:</span>
                </div>
                <ul class="list-disc list-inside text-xs space-y-1 text-rose-800">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <!-- Main Content -->
    <main id="main-content" tabindex="-1" class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200/80 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
            <div>
                <p class="font-semibold text-slate-700">DiaXplain — Sistem Pendukung Keputusan Klasifikasi Status Diabetes</p>
                <p class="mt-0.5">Proyek Tesis Magister: <strong class="text-slate-700">Lintang Amarul Fatah (NIM 25.01.85.0020)</strong> — Universitas Stikubank (Unisbank) Semarang.</p>
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('about') }}" class="text-teal-700 hover:underline font-semibold">Metodologi &amp; Keterbatasan</a>
                <span class="text-slate-300">|</span>
                <span>Bukan Alat Diagnosis Medis</span>
            </div>
        </div>
    </footer>

    @include('layouts.partials.demo-switcher')
    @include('layouts.partials.core-scripts')
    @stack('scripts')
</body>
</html>
