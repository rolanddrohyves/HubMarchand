<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des stocks - HubMarchand</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    @include('partials.module-navbar')
    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <a href="{{ route('accueil') }}" class="text-sm font-medium text-cyan-700 hover:text-cyan-600">Retour aux modules</a>
                <h1 class="mt-3 text-3xl font-bold tracking-tight">Gestion des stocks</h1>
                <p class="mt-2 text-slate-600">{{ $store->name }} · suivi des produits et des mouvements.</p>
                <div class="mt-4">@include('partials.export-actions', ['resource' => 'products'])</div>
            </div>
            <a href="{{ route('logout') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">Déconnexion</a>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Produits actifs</p><p class="mt-2 text-3xl font-bold">{{ $stats['products'] }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Unités en stock</p><p class="mt-2 text-3xl font-bold">{{ $stats['units'] }}</p></div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm"><p class="text-sm text-amber-700">Alertes de stock</p><p class="mt-2 text-3xl font-bold text-amber-900">{{ $stats['low_stock'] }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Valeur du stock</p><p class="mt-2 text-3xl font-bold">{{ number_format($stats['value'], 0, ',', ' ') }} F CFA</p></div>
        </div>

        <section class="mb-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex flex-col justify-between gap-3 md:flex-row md:items-center">
                <div><h2 class="text-xl font-bold">Ajouter un produit</h2><p class="text-sm text-slate-500">Créez une fiche produit et son stock initial.</p></div>
            </div>
            <form method="POST" action="{{ route('inventory.products.store') }}" enctype="multipart/form-data" class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                @csrf
                <input name="name" required placeholder="Nom du produit" class="rounded-xl border border-slate-300 px-4 py-3 text-sm xl:col-span-2">
                <input name="sku" required placeholder="SKU / Référence" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <input name="barcode" placeholder="Code-barres" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <input name="category" placeholder="Catégorie" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <input name="stock_quantity" type="number" min="0" required placeholder="Stock initial" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <input name="minimum_stock" type="number" min="0" required placeholder="Seuil d'alerte" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <input name="purchase_price" type="number" min="0" required placeholder="Prix d'achat (F CFA)" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <input name="unit_price" type="number" min="0" required placeholder="Prix de vente (F CFA)" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <input name="image" type="file" accept=".jpg,.jpeg,.png,.webp" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm xl:col-span-2">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 xl:col-span-6">
                    <div class="mb-3 flex items-center justify-between">
                        <div><p class="text-sm font-semibold">Variantes</p><p class="text-xs text-slate-500">Ajoutez les tailles, couleurs et stocks associés.</p></div>
                        <button type="button" id="add-variant" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold">Ajouter une variante</button>
                    </div>
                    <div id="variants" class="grid gap-3"></div>
                </div>
                <button class="rounded-xl bg-cyan-600 px-4 py-3 text-sm font-semibold text-white hover:bg-cyan-500 xl:col-span-2">Ajouter le produit</button>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex flex-col justify-between gap-4 md:flex-row md:items-center">
                <h2 class="text-xl font-bold">Catalogue et niveaux de stock</h2>
                <form method="GET" action="{{ route('inventory.index') }}" class="flex gap-2">
                    <input name="search" value="{{ request('search') }}" placeholder="Rechercher un produit..." class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                    <select name="stock" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="">Tous les stocks</option>
                        <option value="low" @selected(request('stock') === 'low')>Stocks faibles</option>
                    </select>
                    <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filtrer</button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead><tr class="text-xs uppercase tracking-wide text-slate-500"><th class="px-3 py-3">Produit</th><th class="px-3 py-3">SKU / Code-barres</th><th class="px-3 py-3">Catégorie</th><th class="px-3 py-3">Fournisseur(s)</th><th class="px-3 py-3">Stock</th><th class="px-3 py-3">Prix</th><th class="px-3 py-3">Action</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($products as $product)
                            <tr>
                                <td class="px-3 py-4 font-semibold"><div class="flex items-center gap-3">@if ($product->image_path)<img src="{{ asset('storage/'.$product->image_path) }}" alt="" class="h-10 w-10 rounded-lg object-cover">@endif<span>{{ $product->name }}</span></div></td>
                                <td class="px-3 py-4 text-slate-500">{{ $product->sku }}<br><span class="text-xs">{{ $product->barcode ?: 'Sans code-barres' }}</span></td>
                                <td class="px-3 py-4 text-slate-500">{{ $product->category ?: '—' }}</td>
                                <td class="px-3 py-4 text-slate-500">{{ $product->suppliers->pluck('name')->join(', ') ?: 'Aucun fournisseur' }}</td>
                                <td class="px-3 py-4"><span class="{{ $product->isLowStock() ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800' }} rounded-full px-3 py-1 text-xs font-semibold">{{ $product->stock_quantity }} unités</span><span class="ml-2 text-xs text-slate-400">seuil {{ $product->minimum_stock }}</span></td>
                                <td class="px-3 py-4"><span class="text-xs text-slate-500">Achat</span> {{ number_format($product->purchase_price, 0, ',', ' ') }}<br><span class="text-xs text-slate-500">Vente</span> {{ number_format($product->unit_price, 0, ',', ' ') }} F CFA</td>
                                <td class="px-3 py-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('inventory.products.show', $product) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Voir</a>
                                        <form method="POST" action="{{ route('inventory.movements.store', $product) }}" class="flex items-center gap-2">
                                            @csrf
                                            <select name="type" class="rounded-lg border border-slate-300 px-2 py-2 text-xs"><option value="entry">Entrée</option><option value="exit">Sortie</option><option value="adjustment">Ajuster</option></select>
                                            <input name="quantity" type="number" min="1" required placeholder="Qté" class="w-20 rounded-lg border border-slate-300 px-2 py-2 text-xs">
                                            <button class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white">Stock</button>
                                        </form>
                                        <details class="relative">
                                            <summary class="cursor-pointer list-none rounded-lg border border-cyan-200 bg-cyan-50 px-3 py-2 text-xs font-semibold text-cyan-700">Modifier</summary>
                                            <form method="POST" action="{{ route('inventory.products.update', $product) }}" class="absolute right-0 z-10 mt-2 grid w-72 gap-2 rounded-xl border border-slate-200 bg-white p-4 text-left shadow-xl">
                                                @csrf
                                                @method('PUT')
                                                <input name="name" required value="{{ $product->name }}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                                <input name="sku" required value="{{ $product->sku }}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                                <input name="category" value="{{ $product->category }}" placeholder="Catégorie" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                                <input name="barcode" value="{{ $product->barcode }}" placeholder="Code-barres" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                                <div class="grid grid-cols-2 gap-2">
                                                    <input name="purchase_price" type="number" min="0" required value="{{ $product->purchase_price }}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                                    <input name="minimum_stock" type="number" min="0" required value="{{ $product->minimum_stock }}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                                    <input name="unit_price" type="number" min="0" required value="{{ $product->unit_price }}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                                </div>
                                                <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="is_active" value="1" @checked($product->is_active)> Produit actif</label>
                                                <button class="rounded-lg bg-cyan-600 px-3 py-2 text-xs font-semibold text-white">Enregistrer</button>
                                            </form>
                                        </details>
                                        <form method="POST" action="{{ route('inventory.products.destroy', $product) }}" onsubmit="return confirm('Voulez-vous vraiment supprimer ce produit ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Supprimer</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-3 py-10 text-center text-slate-500">Aucun produit ne correspond à votre recherche.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $products->links() }}</div>
        </section>

        <section class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-bold">Derniers mouvements</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead><tr class="text-xs uppercase tracking-wide text-slate-500"><th class="px-3 py-3">Date</th><th class="px-3 py-3">Produit</th><th class="px-3 py-3">Type</th><th class="px-3 py-3">Quantité</th><th class="px-3 py-3">Stock après</th><th class="px-3 py-3">Motif</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($movements as $movement)
                            <tr>
                                <td class="px-3 py-3 text-slate-500">{{ $movement->created_at->format($store->date_format ?: 'd/m/Y') }}</td>
                                <td class="px-3 py-3 font-medium">{{ $movement->product->name }}</td>
                                <td class="px-3 py-3">{{ ['entry' => 'Entrée', 'exit' => 'Sortie', 'adjustment' => 'Ajustement'][$movement->type] }}</td>
                                <td class="px-3 py-3">{{ $movement->quantity }}</td>
                                <td class="px-3 py-3">{{ $movement->stock_after }}</td>
                                <td class="px-3 py-3 text-slate-500">{{ $movement->reason ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">Aucun mouvement enregistré.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="mt-8 grid gap-8 lg:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold">Fournisseurs</h2>
                <form method="POST" action="{{ route('inventory.suppliers.store') }}" class="mt-4 grid gap-3">
                    @csrf
                    <input name="name" required placeholder="Nom du grossiste" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <div class="grid gap-3 sm:grid-cols-2"><input name="contact_name" placeholder="Contact" class="rounded-xl border border-slate-300 px-4 py-3 text-sm"><input name="phone" placeholder="Téléphone" class="rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
                    <input name="email" type="email" placeholder="E-mail" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <input name="address" placeholder="Adresse" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <button class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">Ajouter le fournisseur</button>
                </form>
                <ul class="mt-5 divide-y divide-slate-100">@forelse ($suppliers as $supplier)<li class="py-3"><p class="font-semibold">{{ $supplier->name }}</p><p class="text-sm text-slate-500">{{ $supplier->phone ?: 'Téléphone non renseigné' }}</p></li>@empty<li class="py-3 text-sm text-slate-500">Aucun fournisseur enregistré.</li>@endforelse</ul>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold">Réapprovisionnement</h2>
                <form method="POST" action="{{ route('inventory.purchase-orders.store') }}" class="mt-4 grid gap-3">
                    @csrf
                    <select name="supplier_id" required class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"><option value="">Choisir un fournisseur</option>@foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select>
                    <select name="product_id" required class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"><option value="">Choisir un produit</option>@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->name }} ({{ $product->stock_quantity }})</option>@endforeach</select>
                    <div class="grid gap-3 sm:grid-cols-2"><input name="quantity" type="number" min="1" required placeholder="Quantité" class="rounded-xl border border-slate-300 px-4 py-3 text-sm"><input name="unit_price" type="number" min="0" required placeholder="Prix d'achat" class="rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
                    <input name="expected_at" type="date" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <button class="rounded-xl bg-cyan-600 px-4 py-3 text-sm font-semibold text-white">Créer le bon de commande</button>
                </form>
                <ul class="mt-5 divide-y divide-slate-100">@forelse ($purchaseOrders as $order)<li class="flex items-center justify-between gap-3 py-3"><div><p class="font-semibold">{{ $order->reference }}</p><p class="text-sm text-slate-500">{{ $order->supplier->name }} · {{ $order->status }}</p></div><div class="flex items-center gap-3"><span class="text-sm font-semibold">{{ number_format($order->total_amount, 0, ',', ' ') }} F CFA</span>@if ($order->status === 'ordered')<form method="POST" action="{{ route('inventory.purchase-orders.receive', $order) }}">@csrf<button class="rounded-lg bg-green-600 px-3 py-2 text-xs font-semibold text-white">Réceptionner</button></form>@endif</div></li>@empty<li class="py-3 text-sm text-slate-500">Aucun bon de commande.</li>@endforelse</ul>
            </section>
        </div>
    </main>
    <template id="variant-template">
        <div class="variant-row grid gap-2 rounded-lg border border-slate-200 bg-white p-3 sm:grid-cols-5">
            <input name="variants[__INDEX__][size]" placeholder="Taille" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
            <input name="variants[__INDEX__][color]" placeholder="Couleur" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
            <input name="variants[__INDEX__][sku]" placeholder="SKU variante" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
            <input name="variants[__INDEX__][stock_quantity]" type="number" min="0" placeholder="Stock" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
            <div class="flex gap-2"><input name="variants[__INDEX__][price]" type="number" min="0" placeholder="Prix" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-xs"><button type="button" class="remove-variant rounded-lg border border-red-200 px-3 text-xs text-red-700">Retirer</button></div>
        </div>
    </template>
    <script>
        const variants = document.getElementById('variants');
        const template = document.getElementById('variant-template');
        let variantIndex = 0;

        document.getElementById('add-variant').addEventListener('click', () => {
            variants.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', variantIndex++));
        });

        variants.addEventListener('click', (event) => {
            if (event.target.classList.contains('remove-variant')) {
                event.target.closest('.variant-row').remove();
            }
        });
    </script>
</body>
</html>
