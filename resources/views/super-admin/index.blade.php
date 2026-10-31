<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Panel Super Admin · HubMarchand</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
@include('super-admin.partials.nav')
<main class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
    <header><p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-700">Administration plateforme</p><h1 class="mt-2 text-3xl font-bold tracking-tight">Panel Super Admin</h1><p class="mt-2 text-slate-600">Surveillez les boutiques, sécurisez les accès et pilotez vos abonnements.</p></header>
    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([['Marchands inscrits', $merchantCount, 'super-admin.merchants'], ['Marchands actifs', $activeMerchantCount, 'super-admin.merchants'], ['Boutiques suspendues', $suspendedMerchantCount, 'super-admin.merchants'], ['Forfaits actifs', $planCount, 'super-admin.plans']] as [$label, $value, $route])
            <a href="{{ route($route) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-cyan-300"><p class="text-sm text-slate-500">{{ $label }}</p><p class="mt-2 text-3xl font-bold">{{ $value }}</p><span class="mt-3 inline-block text-xs font-semibold text-cyan-700">Ouvrir →</span></a>
        @endforeach
    </section>
    <section class="grid gap-3 sm:grid-cols-3">
        <a href="{{ route('super-admin.merchants') }}#creation-marchand" class="rounded-xl bg-cyan-600 px-4 py-3 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-700">+ Créer un marchand</a>
        <a href="{{ route('super-admin.merchants') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-center text-sm font-semibold text-slate-700 shadow-sm transition hover:border-cyan-300">Voir la liste des marchands</a>
        <a href="{{ route('super-admin.plans') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-center text-sm font-semibold text-slate-700 shadow-sm transition hover:border-cyan-300">Gérer les abonnements</a>
    </section>
    <section class="grid gap-6 lg:grid-cols-[1.2fr_.8fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div class="flex items-center justify-between"><div><h2 class="text-xl font-bold">Marchands récents</h2><p class="mt-1 text-sm text-slate-500">Dernières boutiques enregistrées sur la plateforme.</p></div><a href="{{ route('super-admin.merchants') }}" class="text-sm font-semibold text-cyan-700">Voir tout</a></div><div class="mt-5 divide-y divide-slate-100">@forelse($recentMerchants as $store)<div class="flex items-center justify-between gap-3 py-3"><div><p class="font-semibold">{{ $store->name }}</p><p class="text-xs text-slate-500">{{ $store->user?->email }} · {{ $store->created_at->format('d/m/Y') }}</p></div><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $store->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $store->status === 'active' ? 'Actif' : 'Suspendu' }}</span></div>@empty<p class="py-5 text-sm text-slate-500">Aucun marchand.</p>@endforelse</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div class="flex items-center justify-between"><div><h2 class="text-xl font-bold">Journal de sécurité</h2><p class="mt-1 text-sm text-slate-500">Actions sensibles récentes.</p></div><a href="{{ route('super-admin.logs') }}" class="text-sm font-semibold text-cyan-700">Tout voir</a></div><div class="mt-5 space-y-4">@forelse($recentLogs as $log)<div><p class="text-sm font-semibold">{{ $log->description }}</p><p class="mt-1 text-xs text-slate-500">{{ $log->actor?->name ?? 'Système' }} · {{ $log->created_at->format('d/m/Y H:i') }}</p></div>@empty<p class="py-5 text-sm text-slate-500">Aucune action enregistrée.</p>@endforelse</div></div>
    </section>
</main>
</body>
</html>
