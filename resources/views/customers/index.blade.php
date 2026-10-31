<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients - HubMarchand</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
@include('partials.module-navbar')
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div><a href="{{ route('accueil') }}" class="text-sm font-medium text-cyan-700">Retour aux modules</a><h1 class="mt-2 text-3xl font-bold">Clients & B2B</h1><p class="mt-1 text-slate-600">CRM, historique des achats et suivi des crédits.</p><div class="mt-4">@include('partials.export-actions', ['resource' => 'customers'])</div></div>
        <a href="{{ route('logout') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm">Déconnexion</a>
    </div>
    @if (session('status'))<div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between"><h2 class="text-xl font-bold">Fichier clients</h2><span class="text-sm text-slate-500">{{ $customers->total() }} client(s)</span></div>
            <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-b text-xs uppercase text-slate-500"><tr><th class="px-3 py-3">Client</th><th class="px-3 py-3">Segment</th><th class="px-3 py-3">Achats</th><th class="px-3 py-3">Total</th><th class="px-3 py-3">Actions</th></tr></thead><tbody class="divide-y">
                @forelse ($customers as $customer)
                    @php($balance = (int) ($customer->credit_total ?? 0) - (int) ($customer->paid_total ?? 0))
                    <tr><td class="px-3 py-3"><a class="font-semibold text-cyan-700" href="{{ route('customers.show', $customer) }}">{{ $customer->name }}</a><div class="text-xs text-slate-500">{{ $customer->phone ?: $customer->email }}</div></td><td class="px-3 py-3">{{ ucfirst($customer->segment) }}</td><td class="px-3 py-3">{{ $customer->orders_count }}</td><td class="px-3 py-3">{{ number_format($customer->purchases_total ?? 0, 0, ',', ' ') }} F CFA @if($balance > 0)<div class="text-xs font-semibold text-red-600">Dû : {{ number_format($balance, 0, ',', ' ') }}</div>@endif</td><td class="px-3 py-3"><a href="{{ route('customers.show', $customer) }}" class="font-semibold text-cyan-700">Voir</a></td></tr>
                @empty <tr><td colspan="5" class="px-3 py-8 text-center text-slate-500">Aucun client enregistré.</td></tr>@endforelse
            </tbody></table></div>
            <div class="mt-5">{{ $customers->links() }}</div>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-xl font-bold">Nouveau client</h2>
            <form method="POST" action="{{ route('customers.store') }}" class="mt-4 space-y-3">@csrf
                <input name="name" required placeholder="Nom complet ou entreprise" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm">
                <input name="phone" placeholder="Téléphone" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm">
                <input name="email" type="email" placeholder="Email" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm">
                <input name="address" placeholder="Adresse" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm">
                <select name="segment" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm"><option value="particulier">Particulier</option><option value="professionnel">Professionnel</option><option value="vip">VIP</option></select>
                <button class="w-full rounded-xl bg-cyan-600 px-4 py-3 text-sm font-semibold text-white">Ajouter le client</button>
            </form>
        </section>
    </div>
    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="text-xl font-bold">Crédits à recouvrer</h2><div class="mt-4 grid gap-3 md:grid-cols-2">@forelse($creditOrders as $order)<div class="rounded-xl border border-amber-200 bg-amber-50 p-4"><div class="flex justify-between"><strong>{{ $order->customer?->name ?: 'Client inconnu' }}</strong><span class="text-xs">{{ $order->due_at?->format('d/m/Y') ?: 'Sans échéance' }}</span></div><p class="mt-2 text-sm">{{ $order->reference }} · Solde {{ number_format($order->outstandingAmount(), 0, ',', ' ') }} F CFA</p><form method="POST" action="{{ route('customers.reminders.store', $order->customer) }}" class="mt-3">@csrf<input type="hidden" name="sales_order_id" value="{{ $order->id }}"><button class="text-sm font-semibold text-cyan-700">Enregistrer une relance</button></form></div>@empty <p class="text-sm text-slate-500">Aucun crédit en cours.</p>@endforelse</div></section>
</main>
</body>
</html>
