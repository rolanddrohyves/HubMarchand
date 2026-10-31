<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - HubMarchand</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    @include('partials.module-navbar')
    @php
        $modules = [
            ['name' => 'Gestion des Stocks', 'description' => 'Gestion des stocks courant et inventaire des ventes', 'status' => 'Activer', 'icon' => 'stock'],
            ['name' => 'Interface de Caisse POS', 'description' => 'Interface de caisse POS pour les commerçants', 'status' => 'Activer', 'icon' => 'pos'],
            ['name' => 'Analytique & Rapports Financiers', 'description' => 'Analyse et rapports financiers', 'status' => 'Activer', 'icon' => 'analytics'],
            ['name' => 'E-commerce & Boutique en Ligne', 'description' => 'E-commerce et boutique en ligne', 'status' => 'Activer', 'icon' => 'shop'],
            ['name' => 'Fidélisation Client CRM', 'description' => 'Fidélisation client et vue commerce client', 'status' => 'Activer', 'icon' => 'crm'],
            ['name' => 'Gestion des Livraisons', 'description' => 'Livraisons et gestion des transports', 'status' => 'Gérer', 'icon' => 'delivery'],
            ['name' => 'Facturation B2B', 'description' => 'Remise et facturation B2B', 'status' => 'Activer', 'icon' => 'invoice'],
            ['name' => 'Assistant IA Marchand Copilot', 'description' => 'Assistant IA pour le marchand', 'status' => 'Gérer', 'icon' => 'copilot', 'ai' => true],
            ['name' => 'Paramètres', 'description' => 'Configuration générale de votre boutique', 'status' => 'Gérer', 'icon' => 'settings'],
        ];
    @endphp
    @php($hasAiAccess = $store->subscriptionPlan?->ai_included === true)
    @php($isModuleLocked = fn (array $module): bool => ($module['icon'] ?? null) !== 'settings' && ! $store->hasModuleAccess($module['icon']) || (($module['icon'] ?? null) === 'copilot' && ! $hasAiAccess))

    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-700">HubMarchand · {{ $store->name }}</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Bonjour, {{ auth()->user()->name }}</h1>
                <p class="mt-2 text-sm text-slate-500">Voici ce qui mérite votre attention aujourd’hui.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('pos.index') }}" class="rounded-xl bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-700">Nouvelle vente</a>
                <a href="{{ route('inventory.index') }}" class="hidden rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-cyan-300 sm:inline-flex">Ajouter un produit</a>
                <a href="{{ route('logout') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50">Déconnexion</a>
            </div>
        </header>
        @if (session('error'))
            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ session('error') }}</div>
        @endif

        <section class="mb-8">
            <div class="mb-4 flex items-end justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.15em] text-slate-500">Aujourd’hui · {{ now()->format('d/m/Y') }}</p>
                    <h2 class="mt-1 text-2xl font-bold text-slate-900">Vue d’ensemble</h2>
                </div>
                <span class="hidden text-sm text-slate-500 sm:inline">{{ now()->format('H:i') }}</span>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-cyan-200 bg-cyan-50 p-5 shadow-sm"><p class="text-sm text-cyan-700">CA encaissé aujourd'hui</p><p class="mt-2 text-2xl font-bold text-cyan-950">{{ number_format($todayRevenue, 0, ',', ' ') }} F CFA</p><p class="mt-2 text-xs {{ ($revenueChange ?? 0) >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ $revenueChange === null ? 'Pas de comparaison disponible' : (($revenueChange >= 0 ? '+' : '').$revenueChange.' % vs hier à la même heure') }}</p></div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm"><p class="text-sm text-amber-700">Commandes à traiter</p><p class="mt-2 text-2xl font-bold text-amber-950">{{ $pendingOrders }}</p><p class="mt-2 text-xs text-amber-800">Préparation, validation ou expédition</p></div>
                <div class="rounded-2xl border border-violet-200 bg-violet-50 p-5 shadow-sm"><p class="text-sm text-violet-700">Panier moyen du jour</p><p class="mt-2 text-2xl font-bold text-violet-950">{{ number_format($averageBasket, 0, ',', ' ') }} F CFA</p><p class="mt-2 text-xs text-violet-800">{{ $todayOrders }} vente(s) encaissée(s)</p></div>
                <div class="rounded-2xl border border-green-200 bg-green-50 p-5 shadow-sm"><p class="text-sm text-green-700">Visiteurs boutique en ligne</p><p class="mt-2 text-2xl font-bold text-green-950">{{ $todayVisitors }}</p><p class="mt-2 text-xs text-green-800">Visiteurs uniques aujourd'hui</p></div>
            </div>
        </section>

        <section class="mb-8 grid gap-6 lg:grid-cols-3">
            <div class="rounded-2xl border border-red-200 bg-red-50 p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3"><h2 class="text-xl font-bold text-red-950">Urgences stock</h2><span class="rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">{{ $lowStock }}</span></div>
                <div class="mt-4 space-y-3">@forelse($lowStockProducts as $product)<div class="flex items-center justify-between gap-3 rounded-xl bg-white/70 p-3"><div><p class="text-sm font-semibold">{{ $product->name }}</p><p class="text-xs text-red-700">{{ $product->stock_quantity }} restant(s), seuil {{ $product->minimum_stock }}</p></div><a href="{{ route('inventory.products.show', $product) }}" class="text-xs font-semibold text-red-700">Voir</a></div>@empty<p class="text-sm text-red-800">Aucune rupture critique.</p>@endforelse</div>
            </div>
            <div class="rounded-2xl border border-orange-200 bg-orange-50 p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3"><h2 class="text-xl font-bold text-orange-950">Livraisons à surveiller</h2><span class="rounded-full bg-orange-100 px-2 py-1 text-xs font-semibold text-orange-700">{{ $deliveryAlerts->count() }}</span></div>
                <div class="mt-4 space-y-3">@forelse($deliveryAlerts as $order)<div class="rounded-xl bg-white/70 p-3"><p class="text-sm font-semibold">{{ $order->reference }}</p><p class="text-xs text-orange-800">{{ $order->customer_name ?: 'Client non renseigné' }} · {{ $order->fulfillment_status === 'returned' ? 'Retournée' : 'Échec de livraison' }}</p></div>@empty<p class="text-sm text-orange-800">Aucun retour ou échec récent.</p>@endforelse</div>
            </div>
            <div class="rounded-2xl border border-purple-200 bg-purple-50 p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3"><h2 class="text-xl font-bold text-purple-950">Factures B2B en retard</h2><span class="rounded-full bg-purple-100 px-2 py-1 text-xs font-semibold text-purple-700">{{ $overdueInvoices->count() }}</span></div>
                <div class="mt-4 space-y-3">@forelse($overdueInvoices as $invoice)<div class="rounded-xl bg-white/70 p-3"><p class="text-sm font-semibold">{{ $invoice->number }}</p><p class="text-xs text-purple-800">{{ $invoice->customer->name }} · échéance {{ $invoice->due_at->format('d/m/Y') }}</p></div>@empty<p class="text-sm text-purple-800">Aucune facture en retard.</p>@endforelse</div>
            </div>
        </section>

        <section class="mb-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-3"><div><h2 class="text-xl font-bold">Flux des commandes</h2><p class="mt-1 text-sm text-slate-500">Les cinq dernières ventes, boutique et canaux digitaux confondus.</p></div><a href="{{ route('pos.index') }}" class="text-sm font-semibold text-cyan-700">Voir tout</a></div>
                <div class="mt-4 overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-b text-xs uppercase text-slate-500"><tr><th class="px-2 py-2">Client</th><th class="px-2 py-2">Canal</th><th class="px-2 py-2">Montant</th><th class="px-2 py-2">Paiement</th></tr></thead><tbody class="divide-y">@forelse($recentOrders as $order)<tr><td class="px-2 py-3 font-medium">{{ $order->customer_name ?: 'Client non renseigné' }}<span class="block text-xs text-slate-500">{{ $order->reference }}</span></td><td class="px-2 py-3">{{ ['pos'=>'Boutique','ecommerce'=>'Site web','social'=>'Réseaux sociaux'][$order->source] ?? $order->source }}</td><td class="px-2 py-3">{{ number_format($order->total_amount, 0, ',', ' ') }} F CFA</td><td class="px-2 py-3"><span class="{{ $order->status === 'paid' ? 'text-green-700' : 'text-amber-700' }}">{{ $order->status === 'paid' ? 'Payé' : 'En attente' }}</span></td></tr>@empty<tr><td colspan="4" class="px-2 py-5 text-center text-slate-500">Aucune commande enregistrée.</td></tr>@endforelse</tbody></table></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold">Graphique flash</h2><p class="mt-1 text-sm text-slate-500">Ventes encaissées heure par heure.</p>
                @php($maxFlash = max(1, $flashChart->max('amount')))
                <div class="mt-6 flex h-40 items-end gap-1">@foreach($flashChart as $hour)<div class="group flex h-full flex-1 flex-col justify-end"><div class="w-full rounded-t bg-cyan-500 transition group-hover:bg-cyan-700" style="height: {{ max(3, ($hour['amount'] / $maxFlash) * 100) }}%" title="{{ $hour['label'] }} : {{ number_format($hour['amount'], 0, ',', ' ') }} F CFA"></div></div>@endforeach</div>
                <div class="mt-2 flex justify-between text-[10px] text-slate-400"><span>00h</span><span>{{ now()->format('H') }}h</span></div>
            </div>
        </section>

        <section class="mb-8 grid gap-6 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                <div class="flex items-start justify-between gap-3">
                    <div><h2 class="text-xl font-bold">Ventes et marges</h2><p class="mt-1 text-sm text-slate-500">Évolution des 7 derniers jours.</p></div>
                    <div class="flex gap-3 text-xs text-slate-500"><span><i class="mr-1 inline-block h-2 w-2 rounded-full bg-cyan-500"></i>Ventes</span><span><i class="mr-1 inline-block h-2 w-2 rounded-full bg-green-500"></i>Marge</span></div>
                </div>
                @php($maxChartValue = max(1, $chart->max(fn ($day) => max($day['revenue'], $day['margin']))))
                <div class="mt-8 grid h-56 grid-cols-7 items-end gap-3">
                    @foreach ($chart as $day)
                        <div class="flex h-full flex-col items-center justify-end gap-2">
                            <div class="flex h-full w-full items-end justify-center gap-1">
                                <span class="w-3 rounded-t bg-cyan-500" style="height: {{ max(2, ($day['revenue'] / $maxChartValue) * 100) }}%" title="Ventes : {{ number_format($day['revenue'], 0, ',', ' ') }} F CFA"></span>
                                <span class="w-3 rounded-t bg-green-500" style="height: {{ max(2, ($day['margin'] / $maxChartValue) * 100) }}%" title="Marge : {{ number_format($day['margin'], 0, ',', ' ') }} F CFA"></span>
                            </div>
                            <span class="text-xs text-slate-500">{{ $day['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold">Finances du jour</h2>
                <div class="mt-5 space-y-4">
                    <div class="flex justify-between gap-3 text-sm"><span class="text-slate-500">Chiffre d'affaires</span><strong>{{ number_format($todayRevenue, 0, ',', ' ') }} F CFA</strong></div>
                    <div class="flex justify-between gap-3 text-sm"><span class="text-slate-500">Dépenses</span><strong class="text-red-700">- {{ number_format($todayExpenses, 0, ',', ' ') }} F CFA</strong></div>
                    <div class="border-t border-slate-100 pt-4"><div class="flex justify-between gap-3"><span class="font-semibold">Bénéfice net</span><strong class="{{ $todayProfit >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ number_format($todayProfit, 0, ',', ' ') }} F CFA</strong></div></div>
                </div>
                <div class="mt-6 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">La marge est calculée sur les ventes payées, après déduction du coût d'achat et des dépenses enregistrées.</div>
            </div>
        </section>

        <section class="mb-5 flex items-end justify-between gap-3">
            <div><p class="text-sm font-semibold uppercase tracking-[0.15em] text-slate-500">Accès rapide</p><h2 class="mt-1 text-2xl font-bold text-slate-900">Vos modules</h2></div>
            <a href="{{ route('settings') }}" class="text-sm font-semibold text-cyan-700">Gérer les modules</a>
        </section>
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($modules as $module)
                <article @class([
                    'flex min-h-[300px] flex-col justify-between rounded-2xl border bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md',
                    'border-amber-200' => $isModuleLocked($module),
                    'border-slate-200' => ! $isModuleLocked($module),
                ])>
                    <div @class([
                        'relative mb-4 flex h-36 items-center justify-center rounded-2xl',
                        'bg-slate-100' => $isModuleLocked($module),
                        'bg-slate-50' => ! $isModuleLocked($module),
                    ])>
                        @if ($isModuleLocked($module))
                            <span class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700" title="Ce module n'est pas inclus dans votre forfait">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                                Verrouillé
                            </span>
                        @endif
                        @switch($module['icon'])
                            @case('stock')
                                <div class="flex items-end gap-3">
                                    <div class="h-16 w-12 rounded-lg border-2 border-[#d8a86f] bg-[#d39c5c]"></div>
                                    <div class="h-20 w-14 rounded-lg border-2 border-[#d8a86f] bg-[#c98946]"></div>
                                    <div class="h-16 w-12 rounded-lg border-2 border-[#d8a86f] bg-[#b97738]"></div>
                                </div>
                                @break
                            @case('pos')
                                <div class="rounded-xl border border-slate-300 bg-white p-3 shadow-sm">
                                    <div class="grid grid-cols-4 gap-1">
                                        @for ($i = 0; $i < 12; $i++)
                                            <span class="h-2.5 w-2.5 rounded-sm bg-slate-200"></span>
                                        @endfor
                                    </div>
                                </div>
                                @break
                            @case('analytics')
                                <div class="flex h-20 w-24 items-end gap-1 rounded-xl border border-slate-200 bg-white p-2">
                                    <span class="w-full rounded-t bg-cyan-500" style="height: 35%"></span>
                                    <span class="w-full rounded-t bg-cyan-500" style="height: 55%"></span>
                                    <span class="w-full rounded-t bg-cyan-500" style="height: 45%"></span>
                                    <span class="w-full rounded-t bg-cyan-500" style="height: 75%"></span>
                                </div>
                                @break
                            @case('shop')
                                <div class="relative h-20 w-20">
                                    <div class="absolute bottom-0 left-0 h-10 w-12 rounded-xl border-4 border-slate-800"></div>
                                    <div class="absolute bottom-0 right-0 h-10 w-12 rounded-xl border-4 border-slate-800"></div>
                                    <div class="absolute bottom-3 left-5 h-7 w-7 rounded-full border-4 border-slate-800"></div>
                                </div>
                                @break
                            @case('crm')
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-pink-100 text-xl font-bold text-pink-700">C</div>
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-800 text-xl font-bold text-white">U</div>
                                </div>
                                @break
                            @case('delivery')
                                <div class="relative h-20 w-20">
                                    <div class="absolute left-0 top-6 h-10 w-10 rounded-xl border-4 border-slate-800"></div>
                                    <div class="absolute right-0 top-6 h-10 w-10 rounded-xl border-4 border-slate-800"></div>
                                    <div class="absolute right-3 top-1 h-3 w-3 rounded-full border-4 border-slate-800"></div>
                                </div>
                                @break
                            @case('invoice')
                                <div class="h-20 w-20 rounded-xl border border-slate-200 bg-white p-3">
                                    <div class="flex h-full items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-[10px] font-bold uppercase text-slate-500">
                                        PDF
                                    </div>
                                </div>
                                @break
                            @case('copilot')
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-100 text-xl text-cyan-700">C</div>
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-200 text-xl text-slate-700">S</div>
                                </div>
                                @break
                            @case('settings')
                                <div class="flex h-20 w-20 items-center justify-center rounded-xl border border-slate-200 bg-white shadow-sm">
                                    <div class="grid grid-cols-3 gap-1">
                                        @for ($i = 0; $i < 9; $i++)
                                            <span class="h-2.5 w-2.5 rounded-full bg-slate-700"></span>
                                        @endfor
                                    </div>
                                </div>
                                @break
                            @default
                                <div class="h-16 w-16 rounded-2xl bg-slate-200"></div>
                        @endswitch
                    </div>

                    <div>
                        <h3 class="text-xl font-bold leading-snug text-slate-900">{{ $module['name'] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $module['description'] }}</p>
                    </div>

                    @if ($isModuleLocked($module))
                        <div class="mt-5 flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-xl bg-slate-200 px-4 py-3 text-center text-sm font-semibold text-slate-500">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                            Non inclus
                        </div>
                    @elseif ($module['icon'] === 'settings')
                        <a href="{{ route('settings') }}" class="mt-5 block w-full rounded-xl bg-cyan-600 px-4 py-3 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-500">
                            {{ $module['status'] }}
                        </a>
                    @elseif ($module['icon'] === 'stock')
                        <a href="{{ route('inventory.index') }}" class="mt-5 block w-full rounded-xl bg-cyan-600 px-4 py-3 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-500">
                            {{ $module['status'] }}
                        </a>
                    @elseif ($module['icon'] === 'pos')
                        <a href="{{ route('pos.index') }}" class="mt-5 block w-full rounded-xl bg-cyan-600 px-4 py-3 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-500">
                            {{ $module['status'] }}
                        </a>
                    @elseif ($module['icon'] === 'crm')
                        <a href="{{ route('customers.index') }}" class="mt-5 block w-full rounded-xl bg-cyan-600 px-4 py-3 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-500">
                            {{ $module['status'] }}
                        </a>
                    @elseif ($module['icon'] === 'analytics')
                        <a href="{{ route('analytics.index') }}" class="mt-5 block w-full rounded-xl bg-cyan-600 px-4 py-3 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-500">
                            {{ $module['status'] }}
                        </a>
                    @elseif ($module['icon'] === 'delivery')
                        <a href="{{ route('delivery.index') }}" class="mt-5 block w-full rounded-xl bg-cyan-600 px-4 py-3 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-500">
                            {{ $module['status'] }}
                        </a>
                    @elseif ($module['icon'] === 'invoice')
                        <a href="{{ route('billing.index') }}" class="mt-5 block w-full rounded-xl bg-cyan-600 px-4 py-3 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-500">
                            {{ $module['status'] }}
                        </a>
                    @elseif ($module['icon'] === 'shop')
                        <a href="{{ route('ecommerce.index') }}" class="mt-5 block w-full rounded-xl bg-cyan-600 px-4 py-3 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-500">
                            {{ $module['status'] }}
                        </a>
                    @elseif ($module['icon'] === 'copilot')
                        @if ($hasAiAccess)
                            <a href="{{ route('copilot.index') }}" class="mt-5 block w-full rounded-xl bg-cyan-600 px-4 py-3 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-500">
                                {{ $module['status'] }}
                            </a>
                        @else
                            <div class="mt-5 flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-xl bg-slate-200 px-4 py-3 text-center text-sm font-semibold text-slate-500" title="Copilot IA non inclus dans votre forfait">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                                Non inclus
                            </div>
                        @endif
                    @else
                        <button class="mt-5 w-full rounded-xl bg-cyan-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-500">
                            {{ $module['status'] }}
                        </button>
                    @endif
                </article>
            @endforeach
        </div>
    </main>
</body>
</html>
