<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SPK Klasifikasi Status Diabetes') | DiaXplain</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Tailwind CSS, Alpine.js, Chart.js -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', '-apple-system', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            200: '#99f6e4',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                        }
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        /* Canonical visually-hidden utility with focus-within opt-out for skip links */
        .visually-hidden:where(:not(:focus-within, :active)) {
            position: absolute !important;
            clip-path: inset(50%) !important;
            overflow: hidden !important;
            width: 1px !important;
            height: 1px !important;
            margin: -1px !important;
            padding: 0 !important;
            border: 0 !important;
            white-space: nowrap !important;
        }

        .skip-link:focus {
            position: fixed;
            inset-block-start: 0.75rem;
            inset-inline-start: 0.75rem;
            z-index: 9999;
            background: #0f172a;
            color: #ffffff;
            padding: 0.75rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 600;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        /* High-contrast focus ring per accessibility guidelines */
        :where(a:any-link, button, input, select, textarea, [tabindex="0"]):focus-visible {
            outline: 3px solid #0284c7;
            outline-offset: 2px;
        }

        /* Minimum tap target size & iOS zoom prevention */
        input:not([type="checkbox"]):not([type="radio"]),
        select,
        .tap-target {
            font-size: 1rem;
            min-block-size: 48px;
        }

        /* Deferred validation feedback after user interaction (:user-invalid) */
        input:user-invalid,
        select:user-invalid,
        input.user-invalid-fallback,
        select.user-invalid-fallback {
            border-color: #dc2626 !important;
            background-color: #fef2f2 !important;
        }

        input:user-invalid ~ .field-error-msg,
        input.user-invalid-fallback ~ .field-error-msg {
            display: flex !important;
        }

        /* Responsive container-query table wrapper with sticky headers */
        .table-wrapper {
            overflow: auto;
            max-inline-size: 100%;
            container-type: inline-size;
        }
        .table-wrapper table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        .table-wrapper thead {
            position: sticky;
            inset-block-start: 0;
            z-index: 2;
        }
        .table-wrapper tbody th {
            position: sticky;
            inset-inline-start: 0;
            z-index: 1;
            background: #ffffff;
        }

        dialog::backdrop {
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(3px);
        }
    </style>
</head>
<body class="min-h-full bg-slate-50 text-slate-800 antialiased flex flex-col font-sans">
    <!-- Accessible Skip Link -->
    <a href="#main-content" class="skip-link visually-hidden">Lewati ke konten utama</a>

    <!-- Top Navigation Header -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200/80 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-4">
                <!-- Brand Logo -->
                <div class="flex items-center gap-3">
                    <a href="{{ auth()->check() ? route('dashboard') : route('landing') }}" class="flex items-center gap-2.5 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-600 to-sky-700 flex items-center justify-center text-white shadow-sm group-hover:shadow-md transition">
                            <svg aria-hidden="true" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-extrabold text-lg tracking-tight text-slate-900">Dia<span class="text-teal-600">Xplain</span></span>
                                <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-teal-50 text-teal-700 border border-teal-200">
                                    XGBoost + SHAP + LLM
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 hidden md:block">Sistem Pendukung Keputusan Klasifikasi Status Diabetes</p>
                        </div>
                    </a>
                </div>

                <!-- Primary Navigation -->
                <nav aria-label="Utama" class="hidden lg:flex items-center gap-1">
                    @auth
                        <a href="{{ route('dashboard') }}" class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('dashboard') ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('skrining.create') }}" class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('skrining.create') ? 'bg-teal-600 text-white shadow-xs' : 'text-teal-700 bg-teal-50/70 hover:bg-teal-100' }}">
                            + Skrining Baru
                        </a>
                        <a href="{{ route('skrining.index') }}" class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('skrining.index') || request()->routeIs('skrining.show') ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            Riwayat Skrining
                        </a>
                        <a href="{{ route('skrining.compare') }}" class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('skrining.compare') ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            Bandingkan
                        </a>

                        @if(auth()->user()->isNakes() || auth()->user()->isAdmin())
                            <a href="{{ route('nakes.ratings.index') }}" class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('nakes.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                Penilaian Narasi (RM6)
                            </a>
                        @endif

                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.experiments') }}" class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('admin.experiments') ? 'bg-sky-50 text-sky-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                Eksperimen E0–E6 & SHAP
                            </a>
                            <a href="{{ route('admin.llm.config') }}" class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('admin.llm.*') ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                API Key AI / LLM
                            </a>
                            <a href="{{ route('admin.models') }}" class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('admin.models*') || request()->routeIs('admin.users*') || request()->routeIs('admin.audit*') ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                Tata Kelola
                            </a>
                        @endif
                    @else
                        <a href="{{ route('landing') }}" class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('landing') ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-slate-100' }}">
                            Beranda
                        </a>
                        <a href="{{ route('about') }}" class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('about') ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-slate-100' }}">
                            Metodologi & Keterbatasan
                        </a>
                    @endauth
                </nav>

                <!-- User Profile / Auth Actions -->
                <div class="flex items-center gap-3">
                    @auth
                        <div class="hidden sm:flex flex-col items-end">
                            <span class="text-xs font-bold text-slate-800">{{ auth()->user()->name }}</span>
                            <span class="text-[11px] font-medium px-2 py-0.5 rounded-full {{ auth()->user()->isAdmin() ? 'bg-purple-100 text-purple-800' : (auth()->user()->isNakes() ? 'bg-sky-100 text-sky-800' : 'bg-emerald-100 text-emerald-800') }}">
                                {{ auth()->user()->roleBadgeLabel() }}
                            </span>
                        </div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-2 rounded-lg text-xs font-semibold text-slate-600 hover:text-red-700 hover:bg-red-50 border border-slate-200 transition">
                                Keluar
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-100 transition">
                            Masuk
                        </a>
                        <a href="{{ route('register') }}" class="px-4 py-2 rounded-lg text-sm font-semibold bg-teal-600 hover:bg-teal-700 text-white shadow-xs transition">
                            Daftar Akun
                        </a>
                    @endauth
                </div>
            </div>

            <!-- Mobile Navigation Strip -->
            @auth
                <nav aria-label="Menu Mobile" class="flex lg:hidden items-center gap-1.5 overflow-x-auto py-2 border-t border-slate-100 text-xs">
                    <a href="{{ route('dashboard') }}" class="px-2.5 py-1.5 rounded-md font-semibold whitespace-nowrap {{ request()->routeIs('dashboard') ? 'bg-teal-50 text-teal-700' : 'text-slate-600' }}">Dashboard</a>
                    <a href="{{ route('skrining.create') }}" class="px-2.5 py-1.5 rounded-md font-semibold whitespace-nowrap bg-teal-600 text-white">+ Skrining</a>
                    <a href="{{ route('skrining.index') }}" class="px-2.5 py-1.5 rounded-md font-semibold whitespace-nowrap {{ request()->routeIs('skrining.index') ? 'bg-teal-50 text-teal-700' : 'text-slate-600' }}">Riwayat</a>
                    @if(auth()->user()->isNakes() || auth()->user()->isAdmin())
                        <a href="{{ route('nakes.ratings.index') }}" class="px-2.5 py-1.5 rounded-md font-semibold whitespace-nowrap {{ request()->routeIs('nakes.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600' }}">Penilaian AI</a>
                    @endif
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.experiments') }}" class="px-2.5 py-1.5 rounded-md font-semibold whitespace-nowrap {{ request()->routeIs('admin.experiments') ? 'bg-sky-50 text-sky-700' : 'text-slate-600' }}">Eksperimen</a>
                        <a href="{{ route('admin.llm.config') }}" class="px-2.5 py-1.5 rounded-md font-semibold whitespace-nowrap {{ request()->routeIs('admin.llm.*') ? 'bg-amber-50 text-amber-800' : 'text-slate-600' }}">API Key AI</a>
                    @endif
                </nav>
            @endauth
        </div>
    </header>

    <!-- Quick Demo Role Switcher Ribbon (Helpful for Thesis Defense / Demo Evaluation) -->
    <div class="bg-slate-900 text-slate-300 text-xs border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-1.5 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full bg-teal-400"></span>
                <span><strong>Mode Purwarupa Tesis:</strong> Beralih peran instan untuk simulasi sidang:</span>
            </div>
            <div class="flex items-center gap-2">
                <form action="{{ route('demo.login', 'pengguna') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-emerald-700 text-white font-medium transition {{ auth()->check() && auth()->user()->isPengguna() ? 'ring-1 ring-emerald-400 bg-emerald-900/60' : '' }}">
                        1. Pengguna Umum
                    </button>
                </form>
                <form action="{{ route('demo.login', 'nakes') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-sky-700 text-white font-medium transition {{ auth()->check() && auth()->user()->isNakes() ? 'ring-1 ring-sky-400 bg-sky-900/60' : '' }}">
                        2. Tenaga Medis
                    </button>
                </form>
                <form action="{{ route('demo.login', 'admin') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-purple-700 text-white font-medium transition {{ auth()->check() && auth()->user()->isAdmin() ? 'ring-1 ring-purple-400 bg-purple-900/60' : '' }}">
                        3. Admin & Peneliti (Dashboard Eksperimen & API Key)
                    </button>
                </form>
            </div>
        </div>
    </div>

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
            <div role="alert" class="mb-4 rounded-xl bg-red-50 border border-red-200 p-4 text-red-900 shadow-2xs">
                <div class="flex items-center gap-2 font-bold text-sm mb-1">
                    <svg aria-hidden="true" class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Mohon periksa kembali isian berikut:</span>
                </div>
                <ul class="list-disc list-inside text-xs space-y-1 text-red-800">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <!-- Main Content Landmark -->
    <main id="main-content" tabindex="-1" class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @yield('content')
    </main>

    <!-- Footer Landmark -->
    <footer class="bg-white border-t border-slate-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
            <div>
                <p class="font-semibold text-slate-700">DiaXplain — Sistem Pendukung Keputusan Klasifikasi Status Diabetes (XGBoost + SHAP + LLM)</p>
                <p class="mt-0.5">Proyek Tesis Magister: <strong>Lintang Amarul Fatah (NIM 25.01.85.0020)</strong> — Universitas Stikubank (Unisbank) Semarang.</p>
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('about') }}" class="text-teal-700 hover:underline font-medium">Tentang Model & Keterbatasan</a>
                <span class="text-slate-300">|</span>
                <span>Bukan Alat Diagnosis Medis</span>
            </div>
        </div>
    </footer>

    <!-- Deferred Validation ARIA Synchronization & Fallback (validate-input-after-interaction) -->
    <script>
        (() => {
            const dirtyState = new WeakMap();
            const syncAriaAndFallback = (el) => {
                if (!el || typeof el.checkValidity !== 'function') return;
                const isInvalid = el.matches?.(':user-invalid') || !el.checkValidity();
                el.setAttribute('aria-invalid', isInvalid ? 'true' : 'false');
                if (!CSS.supports('selector(:user-invalid)')) {
                    el.classList.toggle('user-invalid-fallback', !el.checkValidity());
                }
            };

            document.addEventListener('blur', (e) => {
                const input = e.target;
                if (!input || !input.checkValidity) return;
                const st = dirtyState.get(input) || { interacted: false };
                if (st.interacted) syncAriaAndFallback(input);
            }, true);

            document.addEventListener('input', (e) => {
                const input = e.target;
                if (!input || !input.checkValidity) return;
                dirtyState.set(input, { interacted: true });
                if (input.getAttribute('aria-invalid') === 'true') {
                    syncAriaAndFallback(input);
                }
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>

