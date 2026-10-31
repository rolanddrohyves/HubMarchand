<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistant IA Marchand Copilot - HubMarchand</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
@include('partials.module-navbar')
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div><a href="{{ route('accueil') }}" class="text-sm font-medium text-cyan-700">Retour aux modules</a><h1 class="mt-2 text-3xl font-bold">Assistant IA Marchand Copilot</h1><p class="mt-1 text-slate-600">Des recommandations pratiques à partir des données de votre boutique.</p></div>
        <a href="{{ route('logout') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium">Déconnexion</a>
    </div>
    @foreach (['status' => 'green', 'generated_content' => 'cyan', 'social_post' => 'cyan', 'faq_response' => 'cyan', 'abandoned_message' => 'cyan'] as $key => $color)
        @if (session($key))<div class="mb-4 rounded-xl border border-{{ $color }}-200 bg-{{ $color }}-50 px-4 py-3 text-sm text-{{ $color }}-800">
            @if (is_array(session($key)))<pre class="whitespace-pre-wrap font-sans">{{ json_encode(session($key), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>@else{{ session($key) }}@endif
        </div>@endif
    @endforeach
    @if ($errors->any())<div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif

    <section class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('copilot.content') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">@csrf
            <h2 class="text-xl font-bold">Générer une fiche produit</h2><p class="mt-1 text-sm text-slate-500">Description commerciale et texte SEO à partir de votre catalogue.</p>
            <div class="mt-4 grid gap-3"><select name="product_id" required class="rounded-xl border border-slate-300 px-3 py-3 text-sm"><option value="">Choisir un produit</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select><select name="tone" class="rounded-xl border border-slate-300 px-3 py-3 text-sm"><option value="vendeur">Ton vendeur</option><option value="professionnel">Ton professionnel</option><option value="luxueux">Ton luxueux</option><option value="decontracte">Ton décontracté</option></select><button class="rounded-xl bg-cyan-600 px-4 py-3 text-sm font-semibold text-white">Générer le contenu</button></div>
        </form>
        <form method="POST" action="{{ route('copilot.social') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">@csrf
            <h2 class="text-xl font-bold">Créer un post social</h2><p class="mt-1 text-sm text-slate-500">Texte prêt à publier sur Instagram, Facebook ou WhatsApp.</p>
            <div class="mt-4 grid gap-3"><select name="product_id" required class="rounded-xl border border-slate-300 px-3 py-3 text-sm"><option value="">Choisir un produit</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select><select name="channel" class="rounded-xl border border-slate-300 px-3 py-3 text-sm"><option value="instagram">Instagram</option><option value="facebook">Facebook</option><option value="whatsapp">WhatsApp</option></select><button class="rounded-xl bg-cyan-600 px-4 py-3 text-sm font-semibold text-white">Générer le post</button></div>
        </form>
    </section>

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-bold">Analyse prédictive du stock</h2><p class="mt-1 text-sm text-slate-500">Les alertes utilisent les sorties des 30 derniers jours et les produits dormants depuis 45 jours.</p>
        <div class="mt-4 grid gap-3 md:grid-cols-2">@forelse($recommendations as $recommendation)<div class="rounded-xl border {{ $recommendation['dead'] ? 'border-amber-200 bg-amber-50' : 'border-red-200 bg-red-50' }} p-4"><div class="flex items-start justify-between gap-3"><div><h3 class="font-semibold">{{ $recommendation['product']->name }}</h3>@if($recommendation['dead'])<p class="mt-1 text-sm text-amber-800">Produit mort : aucune sortie récente. Suggérer une promotion de 20 % ou un pack.</p>@else<p class="mt-1 text-sm text-red-800">Rupture estimée dans {{ $recommendation['days_left'] }} jour(s). Recommander {{ $recommendation['recommended_quantity'] }} unité(s){{ $recommendation['supplier'] ? ' chez '.$recommendation['supplier'] : '' }}.</p>@endif</div><span class="rounded-full bg-white px-2 py-1 text-xs font-semibold">{{ $recommendation['product']->stock_quantity }} en stock</span></div></div>@empty<p class="text-sm text-slate-500">Aucune alerte intelligente pour le moment.</p>@endforelse</div>
    </section>

    <section class="mt-6 grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('copilot.faq') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">@csrf<h2 class="text-xl font-bold">Réponse FAQ</h2><p class="mt-1 text-sm text-slate-500">Testez une réponse automatique sur disponibilité, horaires ou livraison.</p><div class="mt-4 flex gap-3"><input name="question" required maxlength="500" placeholder="Ex. Quels produits sont disponibles ?" class="min-w-0 flex-1 rounded-xl border border-slate-300 px-3 py-3 text-sm"><button class="rounded-xl bg-cyan-600 px-4 py-3 text-sm font-semibold text-white">Répondre</button></div></form>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h2 class="text-xl font-bold">Paniers à relancer</h2><p class="mt-1 text-sm text-slate-500">Commandes e-commerce en attente depuis au moins 24 heures.</p><div class="mt-4 space-y-3">@forelse($abandonedOrders as $order)<div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-3"><div><p class="font-semibold">{{ $order->customer_name ?: 'Client sans nom' }}</p><p class="text-xs text-slate-500">{{ $order->reference }} · {{ number_format($order->total_amount, 0, ',', ' ') }} F CFA</p></div><form method="POST" action="{{ route('copilot.abandoned-message', $order) }}">@csrf<button class="text-sm font-semibold text-cyan-700">Préparer la relance</button></form></div>@empty<p class="text-sm text-slate-500">Aucun panier abandonné détecté.</p>@endforelse</div></div>
    </section>
</main>
</body>
</html>
