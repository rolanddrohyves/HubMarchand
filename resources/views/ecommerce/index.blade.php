<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration e-commerce - HubMarchand</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
@include('partials.module-navbar')
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div><a href="{{ route('accueil') }}" class="text-sm font-medium text-cyan-700">Retour aux modules</a><h1 class="mt-2 text-3xl font-bold">E-commerce & boutique en ligne</h1><p class="mt-1 text-slate-600">Configurez votre vitrine et choisissez les produits visibles par vos clients.</p></div>
        <a href="{{ route('logout') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium">Déconnexion</a>
    </div>
    @if(session('status'))<div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('ecommerce.update') }}" enctype="multipart/form-data" class="space-y-6">@csrf @method('PUT')
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-bold">Identité et thème</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-3">
                <label class="text-sm font-medium">Thème<select name="shop_theme" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-3"><option value="minimal" @selected($store->shop_theme === 'minimal')>Minimal</option><option value="mode" @selected($store->shop_theme === 'mode')>Mode</option><option value="epicerie" @selected($store->shop_theme === 'epicerie')>Épicerie</option><option value="restaurant" @selected($store->shop_theme === 'restaurant')>Restauration</option><option value="electronique" @selected($store->shop_theme === 'electronique')>Électronique</option></select></label>
                <label class="text-sm font-medium">Couleur principale<input name="primary_color" type="text" pattern="#[0-9a-fA-F]{6}" value="{{ old('primary_color', $store->primary_color ?: '#0891b2') }}" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-3"></label>
                <label class="text-sm font-medium">Sous-domaine<input name="subdomain" value="{{ old('subdomain', $store->subdomain) }}" placeholder="ma-boutique" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-3"><span class="mt-1 block text-xs text-slate-500">Exemple : ma-boutique.hubmarchand.com</span></label>
                <label class="text-sm font-medium md:col-span-2">Domaine personnalisé<input name="custom_domain" value="{{ old('custom_domain', $store->custom_domain) }}" placeholder="www.maboutique.com" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-3"></label>
                <label class="text-sm font-medium">Phrase d'accroche<input name="shop_tagline" value="{{ old('shop_tagline', $store->shop_tagline) }}" placeholder="La qualité au meilleur prix" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-3"></label>
                <label class="text-sm font-medium md:col-span-2">Annonce de la boutique<textarea name="shop_announcement" rows="2" placeholder="Livraison gratuite dès 30 000 F CFA..." class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-3">{{ old('shop_announcement', $store->shop_announcement) }}</textarea></label>
                <label class="text-sm font-medium">Favicon<input name="favicon" type="file" accept=".png,.ico,.jpg,.jpeg" class="mt-2 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></label>
                <label class="text-sm font-medium md:col-span-2">Image de bannière<input name="homepage_banner" type="file" accept=".jpg,.jpeg,.png,.webp" class="mt-2 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></label>
            </div>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-bold">Paiements web</h2><p class="mt-1 text-sm text-slate-500">Ces méthodes sont proposées uniquement sur la boutique en ligne.</p>
            @php($webPayments = $store->web_payment_methods ?: ['mobile_money'])<div class="mt-4 grid gap-3 sm:grid-cols-3">@foreach(['mobile_money'=>'Mobile Money','card'=>'Carte bancaire'] as $value=>$label)<label class="flex items-center gap-3 rounded-xl border border-slate-200 p-4"><input type="checkbox" name="web_payment_methods[]" value="{{ $value }}" @checked(in_array($value, $webPayments, true)) class="h-4 w-4 text-cyan-600"><span class="text-sm font-medium">{{ $label }}</span></label>@endforeach<label class="flex items-center gap-3 rounded-xl border border-slate-200 p-4"><input type="checkbox" name="cash_on_delivery" value="1" @checked($store->cash_on_delivery ?? true) class="h-4 w-4 text-cyan-600"><span class="text-sm font-medium">Paiement à la livraison</span></label></div>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-bold">Blocs de la page d'accueil</h2><div class="mt-4 grid gap-3 sm:grid-cols-3">@foreach(['banner'=>'Bannière publicitaire','featured'=>'Produits vedettes','testimonials'=>'Témoignages clients'] as $value=>$label)<label class="flex items-center gap-3 rounded-xl border border-slate-200 p-4"><input type="checkbox" name="homepage_blocks[]" value="{{ $value }}" @checked(in_array($value, $store->homepage_blocks ?: ['banner','featured'], true)) class="h-4 w-4 text-cyan-600"><span class="text-sm font-medium">{{ $label }}</span></label>@endforeach</div>
        </section>
        <div class="flex justify-end"><button class="rounded-xl bg-cyan-600 px-5 py-3 text-sm font-semibold text-white">Enregistrer la configuration</button></div>
    </form>
    <section class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-xl font-bold">Catalogue publié</h2><p class="mt-1 text-sm text-slate-500">Publiez ou retirez un produit sans modifier le stock central.</p></div><a href="{{ route('ecommerce.shop', $store) }}" target="_blank" class="rounded-xl border border-cyan-200 px-4 py-2 text-sm font-semibold text-cyan-700">Voir la boutique</a></div>
        <div class="mt-5 overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-b text-xs uppercase text-slate-500"><tr><th class="px-2 py-3">Produit</th><th class="px-2 py-3">Collection</th><th class="px-2 py-3">Stock</th><th class="px-2 py-3">Prix</th><th class="px-2 py-3 text-right">Publication</th></tr></thead><tbody class="divide-y">@foreach($products as $product)<tr><td class="px-2 py-3 font-medium">{{ $product->name }}</td><td class="px-2 py-3"><form method="POST" action="{{ route('ecommerce.products.publish', $product) }}" class="flex gap-2">@csrf @method('PUT')<input name="web_collection" value="{{ $product->web_collection }}" placeholder="Nouvelle collection" class="w-36 rounded-lg border border-slate-300 px-2 py-1 text-xs"><input type="hidden" name="is_published" value="{{ $product->is_published ? '1' : '0' }}"></td><td class="px-2 py-3">{{ $product->stock_quantity }}</td><td class="px-2 py-3">{{ number_format($product->unit_price, 0, ',', ' ') }} F CFA</td><td class="px-2 py-3 text-right"><button name="is_published" value="{{ $product->is_published ? '0' : '1' }}" class="rounded-lg px-3 py-2 text-xs font-semibold {{ $product->is_published ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">{{ $product->is_published ? 'Publié' : 'Publier' }}</button></form></td></tr>@endforeach</tbody></table></div>
    </section>
</main>
</body>
</html>
