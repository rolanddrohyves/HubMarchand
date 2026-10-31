<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $store->name }} - Boutique en ligne</title>
    @if ($store->favicon_path)<link rel="icon" href="{{ asset('storage/'.$store->favicon_path) }}">@endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
<main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <header class="overflow-hidden rounded-3xl bg-white shadow-sm" style="border-top: 5px solid {{ $store->primary_color ?: '#0891b2' }}">
        <div class="flex flex-wrap items-center justify-between gap-5 p-6">
            <div class="flex items-center gap-4">
                @if ($store->logo_path)
                    <img src="{{ asset('storage/'.$store->logo_path) }}" class="h-16 w-16 rounded-2xl object-cover" alt="Logo de {{ $store->name }}">
                @else
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-cyan-50 text-2xl font-bold text-cyan-700">{{ mb_substr($store->name, 0, 1) }}</div>
                @endif
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-700">Boutique en ligne</p>
                    <h1 class="mt-1 text-3xl font-bold">{{ $store->name }}</h1>
                    <p class="mt-1 max-w-2xl text-slate-600">{{ $store->shop_tagline ?: ($store->description ?: 'Découvrez notre sélection de produits.') }}</p>
                </div>
            </div>
            <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                <span class="font-semibold text-slate-900">{{ $products->count() }}</span> produits disponibles
            </div>
        </div>
        @if (in_array('banner', $store->homepage_blocks ?: [], true))
            <div class="bg-slate-900 px-6 py-8 text-white" @if ($store->homepage_banner_path) style="background-image: linear-gradient(90deg, rgba(15,23,42,.9), rgba(15,23,42,.35)), url('{{ asset('storage/'.$store->homepage_banner_path) }}'); background-size: cover; background-position: center;" @endif>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-300">Nouvelle sélection</p>
                <h2 class="mt-2 text-2xl font-bold">Des produits choisis pour vous</h2>
                <p class="mt-2 max-w-xl text-sm text-slate-300">{{ $store->shop_announcement ?: 'Commandez en ligne et choisissez le mode de paiement qui vous convient.' }}</p>
            </div>
        @endif
    </header>

    @if (session('status'))
        <div class="mt-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_370px]">
        <section>
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold">Le catalogue</h2>
                    <p class="mt-1 text-sm text-slate-500">Les produits sont synchronisés avec le stock de la boutique.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <input id="product-search" type="search" placeholder="Rechercher..." class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm sm:w-44">
                    <select id="category-filter" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="">Toutes les catégories</option>
                        @foreach ($products->pluck('category')->filter()->unique()->sort() as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @if ($collections->isNotEmpty())
                <div class="mb-5 flex flex-wrap gap-2">
                    <button type="button" data-collection="" class="collection-filter rounded-full bg-slate-900 px-3 py-2 text-xs font-semibold text-white">Toutes les collections</button>
                    @foreach ($collections as $collection)
                        <button type="button" data-collection="{{ $collection }}" class="collection-filter rounded-full border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-600">{{ $collection }}</button>
                    @endforeach
                </div>
            @endif
            <div id="product-grid" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($products as $product)
                    <article class="product-card overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md" data-name="{{ mb_strtolower($product->name) }}" data-category="{{ $product->category }}" data-collection="{{ $product->web_collection }}">
                        @if ($product->image_path)
                            <img src="{{ asset('storage/'.$product->image_path) }}" class="h-48 w-full object-cover" alt="{{ $product->name }}">
                        @else
                            <div class="flex h-48 items-center justify-center bg-slate-100 text-sm text-slate-400">Aucune image</div>
                        @endif
                        <div class="p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $product->category ?: 'Produit' }}</p>
                            <h3 class="mt-1 text-lg font-bold">{{ $product->name }}</h3>
                            <p class="mt-2 text-lg font-bold text-cyan-700">{{ number_format($product->unit_price, 0, ',', ' ') }} F CFA</p>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <span class="text-xs {{ $product->stock_quantity > 0 ? 'text-green-700' : 'text-red-700' }}">{{ $product->stock_quantity > 0 ? $product->stock_quantity.' disponible(s)' : 'Rupture de stock' }}</span>
                                <button type="button" class="add-to-cart rounded-xl bg-cyan-600 px-3 py-2 text-xs font-semibold text-white disabled:cursor-not-allowed disabled:bg-slate-300" data-id="{{ $product->id }}" data-name="{{ $product->name }}" data-price="{{ $product->unit_price }}" data-stock="{{ $product->stock_quantity }}" @disabled($product->stock_quantity < 1)>Ajouter au panier</button>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="rounded-2xl bg-white p-6 text-sm text-slate-500">La boutique ne propose pas encore de produits publiés.</p>
                @endforelse
            </div>
            <p id="empty-filter" class="hidden rounded-2xl bg-white p-6 text-sm text-slate-500">Aucun produit ne correspond à votre recherche.</p>
        </section>

        <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:sticky lg:top-6">
            <div class="flex items-center justify-between gap-3">
                <div><h2 class="text-xl font-bold">Votre panier</h2><p id="cart-count" class="mt-1 text-sm text-slate-500">0 article</p></div>
                <span class="rounded-full bg-cyan-50 px-3 py-1 text-sm font-bold text-cyan-700">{{ $store->currency ?: 'XOF' }}</span>
            </div>
            <div id="cart-items" class="mt-5 space-y-3"><p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Votre panier est vide.</p></div>
            <div class="mt-5 flex items-center justify-between border-t border-slate-200 pt-4"><span class="font-semibold">Total</span><strong id="cart-total" class="text-xl text-cyan-700">0 F CFA</strong></div>
            <form id="checkout-form" method="POST" action="{{ route('ecommerce.checkout', $store) }}" class="mt-5 hidden space-y-3">
                @csrf
                <input id="items-input" type="hidden" name="items">
                <input name="customer_name" required placeholder="Nom complet" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm">
                <input name="customer_phone" required placeholder="Téléphone" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm">
                <textarea name="delivery_address" required rows="2" placeholder="Adresse de livraison" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm"></textarea>
                <select name="payment_method" required class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm">
                    @foreach ($store->web_payment_methods ?: [] as $method)
                        <option value="{{ $method }}">{{ $method === 'mobile_money' ? 'Mobile Money' : 'Carte bancaire' }}</option>
                    @endforeach
                    @if ($store->cash_on_delivery)<option value="cash_on_delivery">Paiement à la livraison</option>@endif
                </select>
                <button class="w-full rounded-xl bg-cyan-600 px-4 py-3 text-sm font-semibold text-white">Valider ma commande</button>
            </form>
            <p id="checkout-hint" class="mt-4 text-xs text-slate-500">Ajoutez un produit pour afficher le formulaire de commande.</p>
        </aside>
    </div>
</main>
<script>
    const cart = new Map();
    const formatPrice = (price) => new Intl.NumberFormat('fr-FR').format(price) + ' F CFA';
    const renderCart = () => {
        const items = [...cart.values()];
        const count = items.reduce((total, item) => total + item.quantity, 0);
        const total = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        document.querySelector('#cart-count').textContent = `${count} article${count > 1 ? 's' : ''}`;
        document.querySelector('#cart-total').textContent = formatPrice(total);
        document.querySelector('#items-input').value = JSON.stringify(items.map((item) => ({ product_id: item.id, quantity: item.quantity })));
        document.querySelector('#checkout-form').classList.toggle('hidden', items.length === 0);
        document.querySelector('#checkout-hint').classList.toggle('hidden', items.length > 0);
        document.querySelector('#cart-items').innerHTML = items.length ? items.map((item) => `<div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-3"><div class="min-w-0"><p class="truncate text-sm font-semibold">${item.name}</p><p class="text-xs text-slate-500">${formatPrice(item.price)} · ${item.quantity}</p></div><div class="flex items-center gap-1"><button type="button" data-action="decrease" data-id="${item.id}" class="h-7 w-7 rounded-lg bg-white text-slate-700">−</button><button type="button" data-action="increase" data-id="${item.id}" class="h-7 w-7 rounded-lg bg-cyan-600 text-white">+</button></div></div>`).join('') : '<p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Votre panier est vide.</p>';
    };
    document.querySelectorAll('.add-to-cart').forEach((button) => button.addEventListener('click', () => {
        const item = { id: Number(button.dataset.id), name: button.dataset.name, price: Number(button.dataset.price), stock: Number(button.dataset.stock), quantity: 1 };
        const existing = cart.get(item.id);
        if (existing) existing.quantity = Math.min(existing.quantity + 1, existing.stock);
        else cart.set(item.id, item);
        renderCart();
    }));
    document.querySelector('#cart-items').addEventListener('click', (event) => {
        const button = event.target.closest('button[data-action]');
        if (!button) return;
        const item = cart.get(Number(button.dataset.id));
        if (button.dataset.action === 'increase') item.quantity = Math.min(item.quantity + 1, item.stock);
        if (button.dataset.action === 'decrease') item.quantity -= 1;
        if (item.quantity < 1) cart.delete(item.id);
        renderCart();
    });
    const filterProducts = () => {
        const search = document.querySelector('#product-search').value.toLowerCase();
        const category = document.querySelector('#category-filter').value;
        let visible = 0;
        document.querySelectorAll('.product-card').forEach((card) => {
            const matches = card.dataset.name.includes(search) && (!category || card.dataset.category === category);
            card.classList.toggle('hidden', !matches);
            if (matches) visible++;
        });
        document.querySelector('#empty-filter').classList.toggle('hidden', visible > 0);
    };
    document.querySelector('#product-search').addEventListener('input', filterProducts);
    document.querySelector('#category-filter').addEventListener('change', filterProducts);
    document.querySelectorAll('.collection-filter').forEach((button) => button.addEventListener('click', () => {
        document.querySelectorAll('.collection-filter').forEach((item) => item.classList.remove('bg-slate-900', 'text-white'));
        button.classList.add('bg-slate-900', 'text-white');
        const collection = button.dataset.collection;
        document.querySelectorAll('.product-card').forEach((card) => card.classList.toggle('hidden', Boolean(collection) && card.dataset.collection !== collection));
    }));
</script>
</body>
</html>
