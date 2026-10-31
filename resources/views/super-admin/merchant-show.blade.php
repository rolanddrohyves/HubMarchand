<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>{{ $store->name }} · Super Admin</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">@include('super-admin.partials.nav')
<main class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    @if(session('status'))<div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>@endif
    <header class="flex flex-wrap items-start justify-between gap-4"><div><p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-700">Détail du marchand</p><h1 class="mt-2 text-3xl font-bold">{{ $store->name }}</h1><p class="mt-2 text-slate-600">{{ $store->user?->name }} · {{ $store->user?->email }}</p></div><div class="flex gap-2"><a href="{{ route('super-admin.merchants.edit', $store) }}" class="rounded-xl bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white">Modifier</a><a href="{{ route('super-admin.merchants') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Retour</a></div></header>
    <section class="grid gap-4 sm:grid-cols-2"><div class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Forfait</p><p class="mt-2 text-lg font-bold">{{ $store->subscriptionPlan?->name ?? 'Aucun forfait configuré' }}</p><p class="mt-1 text-sm text-slate-600">{{ $store->subscription_ends_at?->format('d/m/Y') ?? 'Aucune échéance' }}</p></div><div class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">État du compte</p><p class="mt-2 text-lg font-bold {{ $store->hasActiveSubscription() ? 'text-green-700' : 'text-red-700' }}">{{ $store->hasActiveSubscription() ? 'Autorisé' : 'Bloqué' }}</p><p class="mt-1 text-sm text-slate-600">Inscrit le {{ $store->created_at->format('d/m/Y') }}</p></div></section>
</main>
</body>
</html>
