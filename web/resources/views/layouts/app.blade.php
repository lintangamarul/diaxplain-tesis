<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Workspace SPK Diabetes') | DiaXplain</title>
    @include('layouts.partials.head-assets')
</head>
@php
    $authUser = auth()->user();
@endphp
<body
    x-data="{
        sidebarCollapsed: localStorage.getItem('dx_sidebar_collapsed') === 'true',
        toggleSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('dx_sidebar_collapsed', String(this.sidebarCollapsed));
        }
    }"
    class="min-h-full bg-[#F8FAFC] text-slate-800 antialiased font-sans"
>
    <!-- Accessible Skip Link -->
    <a href="#main-content" class="skip-link visually-hidden">Lewati ke konten utama</a>

    <!-- Mobile Slide-Over Navigation Drawer (Native Modal <dialog class="Drawer">) -->
    <dialog id="mobile-nav-drawer" class="Drawer lg:hidden" aria-label="Menu Navigasi DiaXplain">
        <div class="Drawer-scroller">
            <aside class="Drawer-sheet">
                @include('layouts.partials.sidebar-nav', ['isDrawer' => true])
            </aside>
        </div>
    </dialog>

    <div class="min-h-screen flex">
        <!-- Desktop Collapsible Left Sidebar (260px expanded / 76px collapsed) -->
        <aside
            :class="sidebarCollapsed ? 'w-[76px]' : 'w-[260px]'"
            class="hidden lg:flex lg:flex-col shrink-0 bg-white border-r border-slate-200/90 sticky top-0 h-screen transition-[width] duration-200 ease-out z-30"
        >
            @include('layouts.partials.sidebar-nav', ['isDrawer' => false])
        </aside>

        <!-- Main Workspace Column -->
        <div class="flex-1 min-w-0 flex flex-col">
            <!-- Sticky Topbar (64px) -->
            <header class="sticky top-0 z-30 h-16 bg-white/95 backdrop-blur border-b border-slate-200/90 px-4 sm:px-6 flex items-center justify-between gap-4">
                <!-- Left: Sidebar Toggle + Breadcrumb / Active Page Title -->
                <div class="flex items-center gap-3 min-w-0">
                    <!-- Mobile Drawer Button -->
                    <button
                        type="button"
                        id="mobile-drawer-open"
                        aria-label="Buka menu navigasi"
                        aria-expanded="false"
                        aria-controls="mobile-nav-drawer"
                        class="lg:hidden w-9 h-9 rounded-lg border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-100 hover:text-slate-900 shrink-0 transition"
                    >
                        <svg aria-hidden="true" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <!-- Desktop Collapse Toggle Button -->
                    <button
                        type="button"
                        @click="toggleSidebar()"
                        :aria-label="sidebarCollapsed ? 'Perluas sidebar' : 'Ciutkan sidebar'"
                        :aria-expanded="(!sidebarCollapsed).toString()"
                        class="hidden lg:inline-flex w-9 h-9 rounded-lg border border-slate-200 items-center justify-center text-slate-500 hover:bg-slate-100 hover:text-slate-900 shrink-0 transition"
                    >
                        <svg aria-hidden="true" class="w-4 h-4 transition-transform duration-200" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                        </svg>
                    </button>

                    <!-- Breadcrumb / Active Page Title -->
                    <div class="min-w-0 flex items-center gap-2 text-sm">
                        <span class="hidden sm:inline text-slate-400 font-medium">DiaXplain</span>
                        <span class="hidden sm:inline text-slate-300">/</span>
                        <span class="font-bold text-slate-900 truncate">
                            @hasSection('page_title')
                                @yield('page_title')
                            @else
                                @yield('title', 'Dashboard')
                            @endif
                        </span>
                    </div>
                </div>

                <!-- Right: Quick CTA + User Profile Dropdown -->
                <div class="flex items-center gap-3 shrink-0">
                    @if(!request()->routeIs('skrining.create'))
                        <a
                            href="{{ route('skrining.create') }}"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-teal-700 hover:bg-teal-800 text-white text-xs sm:text-sm font-semibold whitespace-nowrap shadow-xs transition"
                        >
                            <svg aria-hidden="true" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span class="hidden sm:inline">Skrining Baru</span>
                            <span class="sm:hidden">Skrining</span>
                        </a>
                    @endif

                    @if($authUser)
                        <!-- User Profile Popover Trigger -->
                        <div class="relative">
                            <button
                                type="button"
                                id="user-profile-trigger"
                                popovertarget="user-profile-menu"
                                aria-haspopup="menu"
                                aria-expanded="false"
                                class="flex items-center gap-2.5 pl-1.5 pr-2.5 py-1.5 rounded-xl border border-slate-200/90 bg-white hover:bg-slate-50 transition"
                            >
                                <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-700 to-slate-800 text-white text-xs font-bold flex items-center justify-center shrink-0">
                                    {{ $authUser->initials() }}
                                </span>
                                <span class="hidden md:flex flex-col items-start text-left leading-tight max-w-[160px]">
                                    <span class="text-xs font-bold text-slate-900 truncate w-full">{{ $authUser->displayName() }}</span>
                                    <span class="text-[11px] font-medium text-slate-500 truncate w-full">{{ $authUser->roleBadgeLabel() }}</span>
                                </span>
                                <svg aria-hidden="true" class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <!-- Popover Placed Immediately After Trigger (menu guide) -->
                            <div id="user-profile-menu" popover="auto" class="dx-menu-popover w-64">
                                <div class="p-3.5 border-b border-slate-100 bg-slate-50/70 rounded-t-[0.875rem]">
                                    <p class="text-xs font-bold text-slate-900 truncate">{{ $authUser->displayName() }}</p>
                                    <p class="text-[11px] font-mono text-slate-500 truncate mt-0.5">{{ $authUser->email }}</p>
                                    <span class="mt-2 inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold border {{ $authUser->roleBadgeClasses() }}">
                                        {{ $authUser->roleBadgeLabel() }}
                                    </span>
                                </div>
                                <div focusgroup="menu nomemory" aria-labelledby="user-profile-trigger" class="p-1.5 space-y-0.5 text-xs font-medium">
                                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-700 hover:bg-slate-100 transition">
                                        <svg aria-hidden="true" class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                        <span>Dashboard Utama</span>
                                    </a>
                                    <a href="{{ route('skrining.index') }}#privasi-uupdp" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-700 hover:bg-slate-100 transition">
                                        <svg aria-hidden="true" class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                        <span>Privasi &amp; Data Saya (UU PDP)</span>
                                    </a>
                                    @if($authUser->isAdmin())
                                        <a href="{{ route('admin.llm.config') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-700 hover:bg-slate-100 transition">
                                            <svg aria-hidden="true" class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                            <span>Konfigurasi API Key AI / LLM</span>
                                        </a>
                                    @endif
                                    <hr class="my-1 border-slate-100">
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-rose-700 hover:bg-rose-50 font-semibold transition">
                                            <svg aria-hidden="true" class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                            <span>Keluar dari Sesi</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </header>

            <!-- Flash Notifications (Polite Live Region) -->
            <div aria-live="polite" class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-5 empty:pt-0">
                @if(session('success'))
                    <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3.5 flex items-start gap-3 text-emerald-900 shadow-2xs">
                        <svg aria-hidden="true" class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-sm font-medium">{{ session('success') }}</div>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="mb-4 rounded-xl bg-amber-50 border border-amber-300 px-4 py-3.5 flex items-start gap-3 text-amber-900 shadow-2xs">
                        <svg aria-hidden="true" class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div class="text-sm font-medium">{{ session('warning') }}</div>
                    </div>
                @endif

                @if($errors->any())
                    <div role="alert" class="mb-4 rounded-xl bg-rose-50 border border-rose-200 px-4 py-3.5 text-rose-900 shadow-2xs">
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

            <!-- Main Workspace Content -->
            <main id="main-content" tabindex="-1" class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
                @yield('content')
            </main>

            <!-- Compact Clinical Workspace Footer -->
            <footer class="border-t border-slate-200/80 bg-white/70 mt-8">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-500">
                    <p><strong>DiaXplain</strong> · SPK Klasifikasi Status Diabetes (XGBoost + TreeSHAP + LLM) — Lintang Amarul Fatah (Unisbank Semarang)</p>
                    <p class="text-slate-400">Bukan Alat Diagnosis Medis · UU PDP</p>
                </div>
            </footer>
        </div>
    </div>

    @include('layouts.partials.demo-switcher')
    @include('layouts.partials.core-scripts')
    @stack('scripts')
</body>
</html>
