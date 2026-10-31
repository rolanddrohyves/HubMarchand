<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caisse & commandes - HubMarchand</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
@include('partials.module-navbar')
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div><a href="{{ route('accueil') }}" class="text-sm font-medium text-cyan-700">Retour aux modules</a><h1 class="mt-2 text-3xl font-bold">Caisse & commandes</h1><p class="mt-1 text-slate-600">{{ $store->name }} · vente, préparation et livraison.</p><div class="mt-4">@include('partials.export-actions', ['resource' => 'orders'])</div></div>
        <a href="{{ route('logout') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium">Déconnexion</a>
    </div>
    @if (session('status'))<div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(380px,.9fr)]">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between"><div><h2 class="text-xl font-bold">Écran de caisse</h2><p class="text-sm text-slate-500">Sélectionnez les articles et encaissez.</p></div><span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-700">Mode tactile</span></div>
            <form method="POST" action="{{ route('pos.orders.store') }}" id="pos-form">
                @csrf
                <div class="mb-4 grid gap-3 sm:grid-cols-2">
                    <select name="source" id="order-source" class="rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm"><option value="pos">Vente en magasin</option><option value="ecommerce">Commande en ligne</option><option value="social">Commande via réseaux sociaux</option></select>
                    <select name="payment_status" class="rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm"><option value="paid">Paiement encaissé</option><option value="pending">Paiement en attente</option></select>
                    <select name="payment_method" id="payment-method" class="rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm">@foreach (['cash' => 'Espèces', 'mobile_money' => 'Mobile Money', 'card' => 'Carte bancaire'] as $method => $label) @if (in_array($method, $store->payment_methods ?: ['cash', 'mobile_money', 'card'], true))<option value="{{ $method }}">{{ $label }}</option>@endif @endforeach<option value="credit">Achat à crédit</option></select>
                    <select name="customer_id" class="rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm"><option value="">Client non enregistré</option>@foreach ($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->phone ? ' · '.$customer->phone : '' }}</option>@endforeach</select>
                    <input name="customer_name" placeholder="Nom du client (facultatif)" class="rounded-xl border border-slate-300 px-3 py-3 text-sm sm:col-span-2">
                    <input name="due_at" id="due-at" type="date" class="hidden rounded-xl border border-slate-300 px-3 py-3 text-sm" placeholder="Échéance">
                </div>
                <p id="catalogue-help" class="mb-3 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600">Produits disponibles immédiatement en magasin.</p>
                <div id="cart" class="space-y-3"></div>
                <button type="button" id="add-line" class="mt-4 w-full rounded-xl border border-dashed border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Ajouter un article</button>
                <div class="mt-5 grid gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-3">
                    <input name="delivery_zone" placeholder="Zone de livraison" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <input name="delivery_person" placeholder="Livreur" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <input name="delivery_address" placeholder="Adresse de livraison" class="rounded-lg border border-slate-300 px-3 py-2 text-sm sm:col-span-3">
                </div>
                <button class="mt-4 w-full rounded-xl bg-cyan-600 px-4 py-4 text-sm font-bold text-white hover:bg-cyan-500">Valider la commande</button>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between"><div><h2 class="text-xl font-bold">Commandes</h2><p class="text-sm text-slate-500">Préparer, livrer et terminer.</p></div><span class="text-sm text-slate-500">{{ $orders->total() }} commande(s)</span></div>
            <div class="space-y-3">
                @forelse ($orders as $order)
                    <a href="{{ route('pos.orders.show', $order) }}" class="block rounded-xl border border-slate-200 p-4 transition hover:border-cyan-300 hover:bg-cyan-50/30">
                        <div class="flex items-start justify-between gap-3"><div><p class="font-bold">{{ $order->reference }}</p><p class="text-xs text-slate-500">{{ $order->customer_name ?: 'Client comptoir' }} · {{ $order->ordered_at->format('d/m/Y H:i') }}</p></div><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $order->fulfillment_status === 'delivered' ? 'bg-green-100 text-green-700' : ($order->fulfillment_status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700') }}">{{ ['pending' => 'À préparer', 'preparing' => 'En préparation', 'ready' => 'Prête', 'delivered' => 'Livrée'][$order->fulfillment_status] }}</span></div>
                        <div class="mt-3 flex justify-between text-sm"><span>{{ $order->items->sum('quantity') }} article(s) · {{ ['pos' => 'Caisse', 'ecommerce' => 'E-commerce', 'social' => 'Réseaux sociaux'][$order->source] }}</span><strong>{{ number_format($order->total_amount, 0, ',', ' ') }} F CFA</strong></div>
                    </a>
                @empty
                    <p class="py-10 text-center text-sm text-slate-500">Aucune commande enregistrée.</p>
                @endforelse
            </div>
            <div class="mt-5">{{ $orders->links() }}</div>
        </section>
    </div>
</main>
<template id="line-template"><div class="cart-line grid gap-2 rounded-xl border border-slate-200 p-3 sm:grid-cols-[minmax(0,1fr)_100px_36px]"><select name="items[__INDEX__][product_id]" required class="product-select rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm">@foreach ($products as $product)<option value="{{ $product->id }}" data-stock="{{ $product->stock_quantity }}" data-price="{{ $product->unit_price }}">{{ $product->name }} · {{ $product->stock_quantity }} en stock · {{ number_format($product->unit_price, 0, ',', ' ') }} F</option>@endforeach</select><input name="items[__INDEX__][quantity]" type="number" min="1" value="1" required class="rounded-lg border border-slate-300 px-3 py-3 text-sm"><button type="button" class="remove-line rounded-lg border border-red-200 text-red-700">×</button></div></template>
<script>
    const cart = document.getElementById('cart');
    const template = document.getElementById('line-template');
    const source = document.getElementById('order-source');
    const paymentMethod = document.getElementById('payment-method');
    const dueAt = document.getElementById('due-at');
    const catalogueHelp = document.getElementById('catalogue-help');
    let index = 0;
    const addLine = () => { cart.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', index++)); };
    const updateCatalogue = () => {
        const online = source.value !== 'pos';
        catalogueHelp.textContent = online
            ? 'Produits commandables en ligne. Les produits en rupture sont affichés mais ne peuvent pas être ajoutés.'
            : 'Produits disponibles immédiatement en magasin.';
        cart.querySelectorAll('.product-select').forEach((select) => {
            const selectedValue = select.value;
            select.querySelectorAll('option').forEach((option) => {
                const unavailable = Number(option.dataset.stock) < 1;
                option.disabled = unavailable;
                option.textContent = option.textContent.replace(/ · (Disponible|Rupture)$/, '') + (unavailable ? ' · Rupture' : ' · Disponible');
            });
            const selected = select.querySelector(`option[value="${selectedValue}"]`);
            if (!selected || selected.disabled) {
                const firstAvailable = [...select.options].find((option) => !option.disabled);
                select.value = firstAvailable ? firstAvailable.value : '';
            }
        });
    };
    document.getElementById('add-line').addEventListener('click', addLine);
    source.addEventListener('change', updateCatalogue);
    paymentMethod.addEventListener('change', () => {
        dueAt.classList.toggle('hidden', paymentMethod.value !== 'credit');
        if (paymentMethod.value === 'credit') {
            document.querySelector('[name="payment_status"]').value = 'pending';
        }
    });
    cart.addEventListener('click', (event) => { if (event.target.classList.contains('remove-line')) { event.target.closest('.cart-line').remove(); } });
    addLine();
    updateCatalogue();
</script>
</body>
</html>
