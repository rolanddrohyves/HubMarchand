<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $product->name }} - HubMarchand</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('inventory.index') }}" class="text-sm font-medium text-cyan-700 hover:text-cyan-600">Retour au stock</a>
                <h1 class="mt-3 text-3xl font-bold">{{ $product->name }}</h1>
                <p class="mt-2 text-slate-600">Détail de la fiche produit.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('inventory.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold">Modifier</a>
                <form method="POST" action="{{ route('inventory.products.destroy', $product) }}" onsubmit="return confirm('Voulez-vous vraiment supprimer ce produit ?');">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-xl border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700">Supprimer</button>
                </form>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                @if ($product->image_path)
                    <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}" class="mb-6 h-64 w-full rounded-xl object-cover">
                @endif
                <div class="grid gap-5 sm:grid-cols-2">
                    <div><p class="text-xs uppercase text-slate-500">SKU</p><p class="mt-1 font-semibold">{{ $product->sku }}</p></div>
                    <div><p class="text-xs uppercase text-slate-500">Code-barres</p><p class="mt-1 font-semibold">{{ $product->barcode ?: 'Non renseigné' }}</p></div>
                    <div><p class="text-xs uppercase text-slate-500">Catégorie</p><p class="mt-1 font-semibold">{{ $product->category ?: 'Non renseignée' }}</p></div>
                    <div><p class="text-xs uppercase text-slate-500">Statut</p><p class="mt-1 font-semibold">{{ $product->is_active ? 'Actif' : 'Inactif' }}</p></div>
                    <div><p class="text-xs uppercase text-slate-500">Stock actuel</p><p class="mt-1 text-xl font-bold">{{ $product->stock_quantity }} unités</p></div>
                    <div><p class="text-xs uppercase text-slate-500">Seuil minimum</p><p class="mt-1 font-semibold">{{ $product->minimum_stock }} unités</p></div>
                    <div><p class="text-xs uppercase text-slate-500">Prix d'achat</p><p class="mt-1 font-semibold">{{ number_format($product->purchase_price, 0, ',', ' ') }} F CFA</p></div>
                    <div><p class="text-xs uppercase text-slate-500">Prix de vente</p><p class="mt-1 font-semibold">{{ number_format($product->unit_price, 0, ',', ' ') }} F CFA</p></div>
                </div>
            </section>

            <aside class="space-y-6">
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="font-bold">Fournisseur(s)</h2>
                    <ul class="mt-3 space-y-2 text-sm">@forelse ($product->suppliers as $supplier)<li>{{ $supplier->name }}</li>@empty<li class="text-slate-500">Aucun fournisseur associé.</li>@endforelse</ul>
                </section>
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="font-bold">Variantes</h2>
                    <ul class="mt-3 space-y-2 text-sm">@forelse ($product->variants as $variant)<li>{{ $variant->size ?: '—' }} · {{ $variant->color ?: '—' }} · {{ $variant->stock_quantity }} unités</li>@empty<li class="text-slate-500">Aucune variante.</li>@endforelse</ul>
                </section>
            </aside>
        </div>

        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-bold">Derniers mouvements</h2>
            <div class="mt-3 overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr class="border-b text-xs uppercase text-slate-500"><th class="px-2 py-3">Date</th><th class="px-2 py-3">Type</th><th class="px-2 py-3">Quantité</th><th class="px-2 py-3">Stock après</th><th class="px-2 py-3">Motif</th></tr></thead><tbody>@forelse ($product->movements as $movement)<tr class="border-b border-slate-100"><td class="px-2 py-3">{{ $movement->created_at->format('d/m/Y H:i') }}</td><td class="px-2 py-3">{{ ['entry' => 'Entrée', 'exit' => 'Sortie', 'adjustment' => 'Ajustement'][$movement->type] }}</td><td class="px-2 py-3">{{ $movement->quantity }}</td><td class="px-2 py-3">{{ $movement->stock_after }}</td><td class="px-2 py-3">{{ $movement->reason ?: '—' }}</td></tr>@empty<tr><td colspan="5" class="px-2 py-5 text-center text-slate-500">Aucun mouvement.</td></tr>@endforelse</tbody></table></div>
        </section>
    </main>
</body>
</html>
