<!-- Compact Floating Dock: Mode Demo Sidang (Collapsible 1-Click Role Switcher) -->
<div
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    class="fixed bottom-4 right-4 z-40 flex flex-col items-end gap-2 print:hidden"
>
    <!-- Expanded Role Switcher Card -->
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        role="region"
        aria-label="Pengalih Peran Mode Demo Sidang"
        class="w-72 rounded-2xl bg-slate-900 text-slate-100 border border-slate-700/90 shadow-2xl p-4 space-y-3"
    >
        <div class="flex items-center justify-between gap-2 pb-2.5 border-b border-slate-800">
            <div>
                <p class="text-xs font-bold text-white flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-teal-400"></span>
                    <span>Mode Demo Sidang Tesis</span>
                </p>
                <p class="text-[11px] text-slate-400 mt-0.5">Beralih peran 1-klik untuk simulasi multi-user.</p>
            </div>
            <button type="button" @click="open = false" aria-label="Tutup panel mode demo" class="w-7 h-7 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 flex items-center justify-center transition">
                <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="space-y-1.5">
            <form action="{{ route('demo.login', 'pengguna') }}" method="POST">
                @csrf
                <button type="submit" class="w-full text-left px-3 py-2 rounded-xl text-xs font-semibold flex items-center justify-between transition {{ auth()->check() && auth()->user()->isPengguna() ? 'bg-emerald-500/20 text-emerald-200 ring-1 ring-emerald-400/50' : 'bg-slate-800/90 hover:bg-slate-800 text-slate-200' }}">
                    <span>1. Pengguna Umum</span>
                    @if(auth()->check() && auth()->user()->isPengguna())
                        <span class="text-[10px] font-bold uppercase text-emerald-300">Aktif</span>
                    @else
                        <span class="text-slate-400">→</span>
                    @endif
                </button>
            </form>
            <form action="{{ route('demo.login', 'nakes') }}" method="POST">
                @csrf
                <button type="submit" class="w-full text-left px-3 py-2 rounded-xl text-xs font-semibold flex items-center justify-between transition {{ auth()->check() && auth()->user()->isNakes() ? 'bg-sky-500/20 text-sky-200 ring-1 ring-sky-400/50' : 'bg-slate-800/90 hover:bg-slate-800 text-slate-200' }}">
                    <span>2. Tenaga Medis / Klinisi</span>
                    @if(auth()->check() && auth()->user()->isNakes())
                        <span class="text-[10px] font-bold uppercase text-sky-300">Aktif</span>
                    @else
                        <span class="text-slate-400">→</span>
                    @endif
                </button>
            </form>
            <form action="{{ route('demo.login', 'admin') }}" method="POST">
                @csrf
                <button type="submit" class="w-full text-left px-3 py-2 rounded-xl text-xs font-semibold flex items-center justify-between transition {{ auth()->check() && auth()->user()->isAdmin() ? 'bg-indigo-500/20 text-indigo-200 ring-1 ring-indigo-400/50' : 'bg-slate-800/90 hover:bg-slate-800 text-slate-200' }}">
                    <span>3. Admin &amp; Peneliti</span>
                    @if(auth()->check() && auth()->user()->isAdmin())
                        <span class="text-[10px] font-bold uppercase text-indigo-300">Aktif</span>
                    @else
                        <span class="text-slate-400">→</span>
                    @endif
                </button>
            </form>
        </div>
    </div>

    <!-- Compact Pill Toggle Button -->
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open.toString()"
        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-full bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-lg border border-slate-700/80 transition"
    >
        <span class="w-2 h-2 rounded-full bg-teal-400"></span>
        <span>Mode Demo Sidang</span>
        <svg aria-hidden="true" class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
        </svg>
    </button>
</div>
