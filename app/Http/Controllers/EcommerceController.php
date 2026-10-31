<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EcommerceController extends Controller
{
    public function index(Request $request): View
    {
        $store = $request->user()->currentStore();
        $products = $store->products()->latest()->get();

        return view('ecommerce.index', compact('store', 'products'));
    }

    public function update(Request $request): RedirectResponse
    {
        $store = $request->user()->currentStore();
        $validated = $request->validate([
            'shop_theme' => ['required', Rule::in(['minimal', 'mode', 'epicerie', 'restaurant', 'electronique'])],
            'shop_tagline' => ['nullable', 'string', 'max:255'],
            'shop_announcement' => ['nullable', 'string', 'max:1000'],
            'primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'favicon' => ['nullable', 'image', 'mimes:png,ico,jpg,jpeg', 'max:1024'],
            'homepage_banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'subdomain' => ['nullable', 'alpha_dash', 'max:63', Rule::unique('stores', 'subdomain')->ignore($store->id)],
            'custom_domain' => ['nullable', 'string', 'max:255', Rule::unique('stores', 'custom_domain')->ignore($store->id)],
            'web_payment_methods' => ['nullable', 'array'],
            'web_payment_methods.*' => ['in:mobile_money,card'],
            'cash_on_delivery' => ['sometimes', 'boolean'],
            'homepage_blocks' => ['nullable', 'array'],
            'homepage_blocks.*' => ['in:banner,featured,testimonials'],
            'collections' => ['nullable', 'array'],
            'collections.*' => ['nullable', 'string', 'max:100'],
        ]);
        if ($request->hasFile('favicon')) {
            if ($store->favicon_path) {
                Storage::disk('public')->delete($store->favicon_path);
            }
            $validated['favicon_path'] = $request->file('favicon')->store('stores/favicons', 'public');
        }
        if ($request->hasFile('homepage_banner')) {
            if ($store->homepage_banner_path) {
                Storage::disk('public')->delete($store->homepage_banner_path);
            }
            $validated['homepage_banner_path'] = $request->file('homepage_banner')->store('stores/banners', 'public');
        }
        unset($validated['favicon'], $validated['homepage_banner'], $validated['collections']);
        $validated['web_payment_methods'] = array_values($validated['web_payment_methods'] ?? []);
        $validated['cash_on_delivery'] = $request->boolean('cash_on_delivery');
        $validated['homepage_blocks'] = array_values($validated['homepage_blocks'] ?? []);
        $store->update($validated);

        return back()->with('status', 'Configuration e-commerce enregistrée.');
    }

    public function publish(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->store_id === $request->user()->currentStore()->id, 404);
        $validated = $request->validate([
            'is_published' => ['required', 'boolean'],
            'web_collection' => ['nullable', 'string', 'max:100'],
        ]);
        $product->update($validated);

        return back()->with('status', $product->is_published ? 'Produit publié sur la boutique en ligne.' : 'Produit retiré de la boutique en ligne.');
    }

    public function shop(Request $request, Store $store): View
    {
        abort_unless($store->hasActiveSubscription(), 404);
        abort_unless($store->enabled_modules === null || in_array('shop', $store->enabled_modules, true), 404);
        $visitorKey = hash('sha256', $request->ip().'|'.$request->userAgent());
        $store->shopVisits()->create([
            'visitor_key' => $visitorKey,
            'visited_at' => now(),
        ]);
        $products = $store->products()->where('is_active', true)->where('is_published', true)->orderBy('name')->get();
        $collections = $products->pluck('web_collection')->filter()->unique()->values();

        return view('ecommerce.shop', compact('store', 'products', 'collections'));
    }

    public function checkout(Request $request, Store $store): RedirectResponse
    {
        abort_unless($store->hasActiveSubscription(), 404);
        abort_unless($store->enabled_modules === null || in_array('shop', $store->enabled_modules, true), 404);
        if ($request->filled('items') && is_string($request->input('items'))) {
            $items = json_decode($request->string('items')->toString(), true);
            if (is_array($items)) {
                $request->merge(['items' => $items]);
            }
        }
        $paymentMethods = array_values($store->web_payment_methods ?? []);
        if ($store->cash_on_delivery) {
            $paymentMethods[] = 'cash_on_delivery';
        }
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'delivery_address' => ['required', 'string', 'max:500'],
            'payment_method' => ['required', Rule::in($paymentMethods)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $order = DB::transaction(function () use ($store, $validated): SalesOrder {
            $total = 0;
            $cost = 0;
            $lines = [];
            foreach ($validated['items'] as $line) {
                $product = $store->products()->where('is_active', true)->where('is_published', true)->lockForUpdate()->findOrFail($line['product_id']);
                abort_if($product->stock_quantity < $line['quantity'], 422, "Stock insuffisant pour {$product->name}.");
                $total += $product->unit_price * $line['quantity'];
                $cost += $product->purchase_price * $line['quantity'];
                $newStock = $product->stock_quantity - $line['quantity'];
                $product->update(['stock_quantity' => $newStock]);
                $product->movements()->create([
                    'user_id' => $store->user_id,
                    'type' => 'exit',
                    'quantity' => $line['quantity'],
                    'stock_after' => $newStock,
                    'reason' => 'Commande boutique en ligne',
                ]);
                $lines[] = ['product_id' => $product->id, 'quantity' => $line['quantity'], 'unit_price' => $product->unit_price, 'unit_cost' => $product->purchase_price];
            }
            $order = $store->salesOrders()->create([
                'reference' => 'WEB-'.now()->format('YmdHis').'-'.random_int(100, 999),
                'source' => 'ecommerce',
                'customer_name' => $validated['customer_name'].' - '.$validated['customer_phone'],
                'status' => 'pending',
                'fulfillment_status' => 'preparing',
                'delivery_address' => $validated['delivery_address'],
                'payment_method' => $validated['payment_method'],
                'total_amount' => $total,
                'cost_amount' => $cost,
                'ordered_at' => now(),
            ]);
            $order->items()->createMany($lines);

            return $order;
        });

        return redirect()->route('ecommerce.shop', $store)->with('status', "Commande {$order->reference} enregistrée.");
    }
}
