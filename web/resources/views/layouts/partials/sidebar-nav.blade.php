@php
    $navUser = auth()->user();
    $isDrawer = $isDrawer ?? false;
    $modelTag = $sharedActiveModel?->version_tag ?? 'xgb-v1.0.0';

    $navLinkBase = 'dx-nav-link group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold whitespace-nowrap transition-colors';
    $navLinkActive = 'bg-teal-50/90 text-teal-800';
    $navLinkIdle = 'text-slate-600 hover:bg-slate-100/90 hover:text-slate-900';
@endphp

<!-- Sidebar Header -->
<div class="h-16 px-4 flex items-center justify-between border-b border-slate-200/80 shrink-0">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 min-w-0">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-teal-700 to-teal-500 flex items-center justify-center text-white shadow-xs shrink-0">
            <svg aria-hidden="true" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <div class="min-w-0" @if(!$isDrawer) x-show="!sidebarCollapsed" x-cloak @endif>
            <div class="flex items-center gap-1.5">
                <span class="font-extrabold text-base tracking-tight text-slate-900">Dia<span class="text-teal-700">Xplain</span></span>
            </div>
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-semibold bg-slate-100 text-slate-600 border border-slate-200/80 leading-none">
                {{ $modelTag }}
            </span>
        </div>
    </a>

    @if($isDrawer)
        <button
            type="button"
            id="mobile-drawer-close"
            autofocus
            aria-label="Tutup menu navigasi"
            class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition"
        >
            <svg aria-hidden="true" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    @endif
</div>

<!-- Scrollable Nav Categories -->
<nav aria-label="{{ $isDrawer ? 'Navigasi Mobile' : 'Navigasi Aplikasi' }}" class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
    <!-- Category: UTAMA -->
    <div>
        <p class="px-3 mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400" @if(!$isDrawer) x-show="!sidebarCollapsed" x-cloak @endif>
            Utama
        </p>
        <ul class="space-y-1">
            <li>
                <a
                    href="{{ route('dashboard') }}"
                    @if(request()->routeIs('dashboard')) aria-current="page" @endif
                    title="Dashboard"
                    class="{{ $navLinkBase }} {{ request()->routeIs('dashboard') ? $navLinkActive : $navLinkIdle }}"
                >
                    <svg aria-hidden="true" class="w-5 h-5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-teal-700' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span @if(!$isDrawer) x-show="!sidebarCollapsed" x-cloak @endif>Dashboard</span>
                </a>
            </li>
            <li>
                <a
                    href="{{ route('skrining.create') }}"
                    @if(request()->routeIs('skrining.create')) aria-current="page" @endif
                    title="Skrining Baru"
                    class="{{ $navLinkBase }} {{ request()->routeIs('skrining.create') ? $navLinkActive : $navLinkIdle }}"
                >
                    <svg aria-hidden="true" class="w-5 h-5 shrink-0 {{ request()->routeIs('skrining.create') ? 'text-teal-700' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span @if(!$isDrawer) x-show="!sidebarCollapsed" x-cloak @endif>Skrining Baru</span>
                </a>
            </li>
            <li>
                <a
                    href="{{ route('skrining.index') }}"
                    @if(request()->routeIs('skrining.index') || request()->routeIs('skrining.show')) aria-current="page" @endif
                    title="Riwayat Skrining"
                    class="{{ $navLinkBase }} {{ (request()->routeIs('skrining.index') || request()->routeIs('skrining.show')) ? $navLinkActive : $navLinkIdle }}"
                >
                    <svg aria-hidden="true" class="w-5 h-5 shrink-0 {{ (request()->routeIs('skrining.index') || request()->routeIs('skrining.show')) ? 'text-teal-700' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span @if(!$isDrawer) x-show="!sidebarCollapsed" x-cloak @endif>Riwayat Skrining</span>
                </a>
            </li>
            <li>
                <a
                    href="{{ route('skrining.compare') }}"
                    @if(request()->routeIs('skrining.compare')) aria-current="page" @endif
                    title="Bandingkan Skrining"
                    class="{{ $navLinkBase }} {{ request()->routeIs('skrining.compare') ? $navLinkActive : $navLinkIdle }}"
                >
                    <svg aria-hidden="true" class="w-5 h-5 shrink-0 {{ request()->routeIs('skrining.compare') ? 'text-teal-700' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    <span @if(!$isDrawer) x-show="!sidebarCollapsed" x-cloak @endif>Bandingkan Skrining</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Category: KLINIS & EVALUASI (Nakes & Admin) -->
    @if($navUser && ($navUser->isNakes() || $navUser->isAdmin()))
        <div>
            <p class="px-3 mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400" @if(!$isDrawer) x-show="!sidebarCollapsed" x-cloak @endif>
                Klinis &amp; Evaluasi
            </p>
            <ul class="space-y-1">
                <li>
                    <a
                        href="{{ route('nakes.ratings.index') }}"
                        @if(request()->routeIs('nakes.*')) aria-current="page" @endif
                        title="Penilaian Narasi (RM6)"
                        class="{{ $navLinkBase }} {{ request()->routeIs('nakes.*') ? $navLinkActive : $navLinkIdle }}"
                    >
                        <svg aria-hidden="true" class="w-5 h-5 shrink-0 {{ request()->routeIs('nakes.*') ? 'text-teal-700' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span @if(!$isDrawer) x-show="!sidebarCollapsed" x-cloak @endif>Penilaian Narasi (RM6)</span>
                    </a>
                </li>
            </ul>
        </div>
    @endif

    <!-- Category: RISET & SISTEM (Admin Only) -->
    @if($navUser && $navUser->isAdmin())
        <div>
            <p class="px-3 mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400" @if(!$isDrawer) x-show="!sidebarCollapsed" x-cloak @endif>
                Riset &amp; Sistem
            </p>
            <ul class="space-y-1">
                <li>
                    <a
                        href="{{ route('admin.experiments') }}"
                        @if(request()->routeIs('admin.experiments*')) aria-current="page" @endif
                        title="Eksperimen E0–E6 & SHAP"
                        class="{{ $navLinkBase }} {{ request()->routeIs('admin.experiments*') ? $navLinkActive : $navLinkIdle }}"
                    >
                        <svg aria-hidden="true" class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.experiments*') ? 'text-teal-700' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span @if(!$isDrawer) x-show="!sidebarCollapsed" x-cloak @endif>Eksperimen E0–E6 &amp; SHAP</span>
                    </a>
                </li>
                <li>
                    <a
                        href="{{ route('admin.llm.config') }}"
                        @if(request()->routeIs('admin.llm.*')) aria-current="page" @endif
                        title="Konfigurasi AI / LLM"
                        class="{{ $navLinkBase }} {{ request()->routeIs('admin.llm.*') ? $navLinkActive : $navLinkIdle }}"
                    >
                        <svg aria-hidden="true" class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.llm.*') ? 'text-teal-700' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        <span @if(!$isDrawer) x-show="!sidebarCollapsed" x-cloak @endif>Konfigurasi AI / LLM</span>
                    </a>
                </li>
                <li>
                    <a
                        href="{{ route('admin.models') }}"
                        @if(request()->routeIs('admin.models*') || request()->routeIs('admin.users*') || request()->routeIs('admin.audit*')) aria-current="page" @endif
                        title="Tata Kelola & Audit"
                        class="{{ $navLinkBase }} {{ (request()->routeIs('admin.models*') || request()->routeIs('admin.users*') || request()->routeIs('admin.audit*')) ? $navLinkActive : $navLinkIdle }}"
                    >
                        <svg aria-hidden="true" class="w-5 h-5 shrink-0 {{ (request()->routeIs('admin.models*') || request()->routeIs('admin.users*') || request()->routeIs('admin.audit*')) ? 'text-teal-700' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span @if(!$isDrawer) x-show="!sidebarCollapsed" x-cloak @endif>Tata Kelola &amp; Audit</span>
                    </a>
                </li>
            </ul>
        </div>
    @endif
</nav>

<!-- Sidebar Footer -->
<div class="p-3 border-t border-slate-200/80 space-y-1 shrink-0">
    <a
        href="{{ route('about') }}"
        @if(request()->routeIs('about')) aria-current="page" @endif
        title="Metodologi & Keterbatasan"
        class="{{ $navLinkBase }} {{ request()->routeIs('about') ? $navLinkActive : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}"
    >
        <svg aria-hidden="true" class="w-5 h-5 shrink-0 text-slate-400 group-hover:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span @if(!$isDrawer) x-show="!sidebarCollapsed" x-cloak @endif class="text-xs">Metodologi &amp; Batasan</span>
    </a>
</div>
