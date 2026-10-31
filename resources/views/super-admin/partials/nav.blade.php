<nav class="border-b border-slate-800 bg-slate-950 text-white">
    <div class="mx-auto flex max-w-7xl items-center gap-6 overflow-x-auto px-4 py-4 sm:px-6 lg:px-8">
        <a href="{{ route('super-admin.index') }}" class="flex shrink-0 items-center gap-2 text-sm font-black uppercase tracking-[0.16em]">
            @if($systemSettings->logo_path)<img src="{{ asset('storage/'.$systemSettings->logo_path) }}" class="h-8 w-8 rounded object-contain" alt="{{ $systemSettings->name }}">@endif
            <span>{{ $systemSettings->name }} <span class="text-slate-500">Admin</span></span>
        </a>
        <div class="flex gap-1 text-sm">
            @foreach ([['super-admin.index', 'Vue d’ensemble'], ['super-admin.merchants', 'Marchands'], ['super-admin.plans', 'Forfaits'], ['super-admin.logs', 'Journal de sécurité']] as [$route, $label])
                <a href="{{ route($route) }}" @class(['shrink-0 rounded-lg px-3 py-2 font-semibold', 'bg-white/10 text-cyan-300' => request()->routeIs($route), 'text-slate-300 hover:bg-white/10' => ! request()->routeIs($route)])>{{ $label }}</a>
            @endforeach
            @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('super-admin.settings') }}" @class(['shrink-0 rounded-lg px-3 py-2 font-semibold', 'bg-white/10 text-cyan-300' => request()->routeIs('super-admin.settings'), 'text-slate-300 hover:bg-white/10' => ! request()->routeIs('super-admin.settings')])>Paramètres</a>
                <a href="{{ route('super-admin.admins') }}" @class(['shrink-0 rounded-lg px-3 py-2 font-semibold', 'bg-white/10 text-cyan-300' => request()->routeIs('super-admin.admins'), 'text-slate-300 hover:bg-white/10' => ! request()->routeIs('super-admin.admins')])>Administrateurs</a>
            @endif
        </div>
        <a href="{{ route('logout') }}" class="ml-auto shrink-0 rounded-lg border border-slate-700 px-3 py-2 text-sm text-slate-300 hover:bg-white/10">Déconnexion</a>
    </div>
</nav>
