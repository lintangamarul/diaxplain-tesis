<nav aria-label="Submenu Tata Kelola & Audit" class="flex items-center gap-1.5 overflow-x-auto pb-1">
    <a
        href="{{ route('admin.models') }}"
        @if(request()->routeIs('admin.models*')) aria-current="page" @endif
        class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap border transition {{ request()->routeIs('admin.models*') ? 'bg-teal-700 text-white border-teal-700 shadow-2xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900' }}"
    >
        Registri Model XGBoost
    </a>
    <a
        href="{{ route('admin.users') }}"
        @if(request()->routeIs('admin.users*')) aria-current="page" @endif
        class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap border transition {{ request()->routeIs('admin.users*') ? 'bg-teal-700 text-white border-teal-700 shadow-2xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900' }}"
    >
        Manajemen Pengguna &amp; RBAC
    </a>
    <a
        href="{{ route('admin.audit') }}"
        @if(request()->routeIs('admin.audit*')) aria-current="page" @endif
        class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap border transition {{ request()->routeIs('admin.audit*') ? 'bg-teal-700 text-white border-teal-700 shadow-2xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900' }}"
    >
        Log Audit Keamanan
    </a>
</nav>
