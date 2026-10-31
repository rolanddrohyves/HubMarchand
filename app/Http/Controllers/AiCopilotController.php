<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SalesOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AiCopilotController extends Controller
{
    public function index(Request $request): View
    {
        $store = $request->user()->currentStore();
        $products = $store->products()->with('suppliers')->where('is_active', true)->orderBy('name')->get();
        $recommendations = $this->stockRecommendations($store->id, $products);
        $abandonedOrders = $store->salesOrders()
            ->where('source', 'ecommerce')
            ->where('status', 'pending')
            ->where('ordered_at', '<=', now()->subHours(24))
            ->latest('ordered_at')
            ->limit(10)
            ->get();

        return view('copilot.index', compact('store', 'products', 'recommendations', 'abandonedOrders'));
    }

    public function generateContent(Request $request): RedirectResponse
    {
        $store = $request->user()->currentStore();
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'tone' => ['required', 'in:vendeur,professionnel,luxueux,decontracte'],
        ]);
        $product = $store->products()->findOrFail($validated['product_id']);
        $content = $this->productContent($product, $validated['tone']);

        return back()->with('generated_content', $content);
    }

    public function generateSocialPost(Request $request): RedirectResponse
    {
        $store = $request->user()->currentStore();
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'channel' => ['required', 'in:instagram,facebook,whatsapp'],
        ]);
        $product = $store->products()->findOrFail($validated['product_id']);
        $post = "Découvrez {$product->name} : qualité, disponibilité et livraison rapide. "
            .'Commandez maintenant à '.number_format($product->unit_price, 0, ',', ' ').' F CFA.';
        $hashtags = '#HubMarchand #'.str_replace(' ', '', ucfirst((string) ($product->category ?: 'Nouveauté')));

        return back()->with('social_post', [
            'channel' => ucfirst($validated['channel']),
            'text' => $post."\n\n".$hashtags.' #Boutique #AfriqueDeLOuest',
        ]);
    }

    public function generateFaqResponse(Request $request): RedirectResponse
    {
        $store = $request->user()->currentStore();
        $validated = $request->validate(['question' => ['required', 'string', 'max:500']]);
        $question = mb_strtolower($validated['question']);
        $answer = str_contains($question, 'horaire')
            ? 'Notre boutique est ouverte selon les horaires configurés par le marchand. Contactez-nous pour confirmer la disponibilité.'
            : (str_contains($question, 'livraison')
                ? 'Nous livrons dans les zones configurées par la boutique. Le tarif est calculé selon la zone et le poids de la commande.'
                : 'Je peux vous renseigner sur la disponibilité, le prix et la livraison. Précisez le nom du produit recherché.');

        if (str_contains($question, 'stock') || str_contains($question, 'disponib')) {
            $matches = $store->products()->where('is_active', true)->where('stock_quantity', '>', 0)->limit(5)->pluck('name');
            $answer = $matches->isEmpty()
                ? 'Aucun produit n’est actuellement disponible.'
                : 'Produits disponibles : '.$matches->implode(', ').'.';
        }

        return back()->with('faq_response', $answer);
    }

    public function abandonedMessage(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        abort_unless($salesOrder->store_id === $request->user()->currentStore()->id, 404);
        $name = $salesOrder->customer_name ?: 'Bonjour';

        return back()->with('abandoned_message', "Bonjour {$name}, votre commande {$salesOrder->reference} vous attend encore. "
            .'Finalisez-la aujourd’hui pour recevoir vos articles rapidement. Répondez à ce message si vous avez besoin d’aide.');
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return Collection<int, array<string, mixed>>
     */
    private function stockRecommendations(int $storeId, Collection $products): Collection
    {
        return $products->map(function (Product $product): ?array {
            $sold = (int) $product->movements()
                ->where('type', 'exit')
                ->where('created_at', '>=', now()->subDays(30))
                ->sum('quantity');
            $dailyRate = $sold / 30;
            $daysLeft = $dailyRate > 0 ? (int) floor($product->stock_quantity / $dailyRate) : null;
            $lastSale = $product->movements()->where('type', 'exit')->latest()->value('created_at');
            $dead = $product->stock_quantity > 0 && (! $lastSale || now()->diffInDays($lastSale) >= 45);

            if (($daysLeft === null || $daysLeft > 7) && ! $dead) {
                return null;
            }

            return [
                'product' => $product,
                'days_left' => $daysLeft,
                'recommended_quantity' => $dailyRate > 0 ? (int) ceil($dailyRate * 14) : 0,
                'supplier' => $product->suppliers->first()?->name,
                'dead' => $dead,
            ];
        })->filter()->values();
    }

    private function productContent(Product $product, string $tone): array
    {
        $opening = match ($tone) {
            'professionnel' => 'Une solution fiable pour vos besoins quotidiens.',
            'luxueux' => 'Une pièce d’exception pensée pour celles et ceux qui recherchent le meilleur.',
            'decontracte' => 'Le produit qu’il vous faut pour vous faciliter la vie !',
            default => 'Ne passez pas à côté de cette belle opportunité.',
        };

        return [
            'name' => $product->name,
            'category' => $product->category ?: 'À classer',
            'description' => $opening." {$product->name} est disponible dès maintenant dans notre boutique. "
                .'Profitez d’un prix attractif et d’un accompagnement personnalisé.',
            'seo' => "{$product->name} au meilleur prix en Afrique de l’Ouest. Découvrez notre sélection et commandez facilement.",
            'variants' => $product->variants->map(fn ($variant): string => trim(implode(' - ', array_filter([$variant->size, $variant->color]))))->filter()->values()->all(),
        ];
    }
}
