@php
    $enabledModules = auth()->user()->currentStore()->enabled_modules;
    $navigationItems = [
        ['label' => 'Accueil', 'route' => 'accueil', 'pattern' => 'accueil', 'module' => null],
        ['label' => 'Stocks', 'route' => 'inventory.index', 'pattern' => 'inventory.*', 'module' => 'stock'],
        ['label' => 'Caisse', 'route' => 'pos.index', 'pattern' => 'pos.*', 'module' => 'pos'],
        ['label' => 'Clients', 'route' => 'customers.index', 'pattern' => 'customers.*', 'module' => 'crm'],
        ['label' => 'Analytique', 'route' => 'analytics.index', 'pattern' => 'analytics.*', 'module' => 'analytics'],
        ['label' => 'Livraisons', 'route' => 'delivery.index', 'pattern' => 'delivery.*', 'module' => 'delivery'],
        ['label' => 'Facturation B2B', 'route' => 'billing.index', 'pattern' => 'billing.*', 'module' => 'invoice'],
        ['label' => 'E-commerce', 'route' => 'ecommerce.index', 'pattern' => 'ecommerce.*', 'module' => 'shop'],
        ['label' => 'Copilot IA', 'route' => 'copilot.index', 'pattern' => 'copilot.*', 'module' => 'copilot', 'ai' => true],
        ['label' => 'Paramètres', 'route' => 'settings', 'pattern' => 'settings*', 'module' => null],
    ];
@endphp

<nav class="sticky top-0 z-40 border-b border-slate-200 bg-white shadow-sm" aria-label="Navigation des modules">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-16 items-center gap-2 py-2 sm:gap-4">
            <a href="{{ route('accueil') }}" class="shrink-0 text-sm font-black uppercase tracking-[0.16em] text-slate-950">Hub<span class="text-cyan-600">Marchand</span></a>
            <div class="hidden min-w-0 flex-1 items-center gap-1 overflow-x-auto md:flex [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($navigationItems as $item)
                    @php($active = request()->routeIs($item['pattern']))
                    @php($moduleEnabled = $item['module'] === null || auth()->user()->currentStore()->hasModuleAccess($item['module']))
                    @if (! $moduleEnabled || (($item['ai'] ?? false) && ! auth()->user()->currentStore()->subscriptionPlan?->ai_included))
                        <span title="{{ $moduleEnabled ? 'Ce module IA n’est pas inclus dans votre forfait' : 'Module désactivé dans les paramètres' }}" class="inline-flex shrink-0 cursor-not-allowed items-center gap-1 rounded-lg px-3 py-2 text-sm font-semibold text-slate-400">
                            {{ $item['label'] }}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                            <span class="sr-only">{{ $moduleEnabled ? 'Non inclus dans votre forfait' : 'Module désactivé dans les paramètres' }}</span>
                        </span>
                    @else
                    <a href="{{ route($item['route']) }}" @class([
                        'shrink-0 rounded-lg px-3 py-2 text-sm font-semibold transition',
                        'bg-cyan-50 text-cyan-700' => $active,
                        'text-slate-600 hover:bg-slate-100 hover:text-slate-950' => ! $active,
                    ]) @if ($active) aria-current="page" @endif>
                        {{ $item['label'] }}
                    </a>
                    @endif
                @endforeach
                @if (auth()->user()->isSuperAdmin())
                    <a href="{{ route('super-admin.index') }}" class="shrink-0 rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">Panel Super Admin</a>
                @endif
            </div>
            <div class="relative z-10 ml-auto flex shrink-0 items-center gap-1 bg-white pl-2">
                <a href="{{ route('notifications.index') }}" class="relative inline-flex shrink-0 items-center gap-2 rounded-xl p-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-950" aria-label="Notifications">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9a6 6 0 0 0-12 0v.75a8.967 8.967 0 0 1-2.31 6.022c1.73.64 3.55 1.09 5.453 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                    <span class="hidden lg:inline">Notifications</span>
                    @if (auth()->user()->unreadNotifications()->exists())
                        <span class="absolute right-1.5 top-1.5 inline-flex h-2 w-2 rounded-full bg-amber-500 ring-2 ring-white" aria-label="Notifications non lues"></span>
                    @endif
                </a>
                <a href="{{ route('logout') }}" class="hidden shrink-0 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 sm:inline-flex">Déconnexion</a>
                <details class="relative md:hidden">
                    <summary class="flex cursor-pointer list-none items-center rounded-xl border border-slate-200 p-2.5 text-slate-700 transition hover:bg-slate-50 [&::-webkit-details-marker]:hidden" aria-label="Ouvrir le menu">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <span class="sr-only">Menu</span>
                    </summary>
                    <div class="absolute right-0 top-14 z-50 w-[min(20rem,calc(100vw-2rem))] rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
                        <p class="px-3 pb-2 pt-1 text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Modules</p>
                        <div class="grid gap-1">
                            @foreach ($navigationItems as $item)
                                @php($active = request()->routeIs($item['pattern']))
                                @php($moduleEnabled = $item['module'] === null || auth()->user()->currentStore()->hasModuleAccess($item['module']))
                                @if (! $moduleEnabled || (($item['ai'] ?? false) && ! auth()->user()->currentStore()->subscriptionPlan?->ai_included))
                                    <span class="flex cursor-not-allowed items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-400">
                                        {{ $item['label'] }}
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                                    </span>
                                @else
                                    <a href="{{ route($item['route']) }}" @class([
                                        'rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                                        'bg-cyan-50 text-cyan-700' => $active,
                                        'text-slate-700 hover:bg-slate-50' => ! $active,
                                    ])>{{ $item['label'] }}</a>
                                @endif
                            @endforeach
                            @if (auth()->user()->isSuperAdmin())
                                <a href="{{ route('super-admin.index') }}" class="rounded-xl bg-slate-900 px-3 py-2.5 text-sm font-semibold text-white">Panel Super Admin</a>
                            @endif
                        </div>
                        <div class="mt-2 border-t border-slate-100 pt-2 sm:hidden">
                            <a href="{{ route('logout') }}" class="block rounded-xl px-3 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50">Déconnexion</a>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </div>
</nav>
