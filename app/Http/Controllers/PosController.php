<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SalesOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(Request $request): View
    {
        $store = $this->boutique($request);
        abort_if($store->status === 'suspended', 403, 'Cette boutique est suspendue.');
        $products = $store->products()->where('is_active', true)->orderBy('name')->get();
        $customers = $store->customers()->orderBy('name')->get();
        $orders = $store->salesOrders()->with('items.product')->latest('ordered_at')->paginate(15)->withQueryString();

        return view('pos.index', compact('store', 'products', 'customers', 'orders'));
    }

    public function store(Request $request): RedirectResponse
    {
        $store = $this->boutique($request);
        $request->merge(['payment_method' => $request->input('payment_method', 'cash')]);
        $validated = $request->validate([
            'source' => ['required', Rule::in(['pos', 'ecommerce', 'social'])],
            'customer_id' => ['nullable', 'integer', 'required_if:payment_method,credit', Rule::exists('customers', 'id')->where('store_id', $store->id)],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'payment_status' => ['required', Rule::in(['pending', 'paid'])],
            'payment_method' => ['required', Rule::in(array_merge($store->payment_methods ?: ['cash', 'mobile_money', 'card'], ['credit']))],
            'due_at' => ['nullable', 'date', 'required_if:payment_method,credit'],
            'delivery_person' => ['nullable', 'string', 'max:255'],
            'delivery_zone' => ['nullable', 'string', 'max:255'],
            'delivery_address' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('store_id', $store->id)],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($request, $store, $validated): void {
            $total = 0;
            $cost = 0;
            $lines = [];

            foreach ($validated['items'] as $line) {
                $product = Product::query()->where('store_id', $store->id)->lockForUpdate()->findOrFail($line['product_id']);
                if ($product->stock_quantity < $line['quantity']) {
                    abort(422, "Stock insuffisant pour {$product->name}.");
                }
                $total += $product->unit_price * $line['quantity'];
                $cost += $product->purchase_price * $line['quantity'];
                $newStock = $product->stock_quantity - $line['quantity'];
                $product->update(['stock_quantity' => $newStock]);
                $product->movements()->create([
                    'user_id' => $request->user()->id,
                    'type' => 'exit',
                    'quantity' => $line['quantity'],
                    'stock_after' => $newStock,
                    'reason' => 'Vente '.($validated['source'] === 'pos' ? 'en caisse' : 'en ligne'),
                ]);
                $lines[] = ['product_id' => $product->id, 'quantity' => $line['quantity'], 'unit_price' => $product->unit_price, 'unit_cost' => $product->purchase_price];
            }

            $order = $store->salesOrders()->create([
                'reference' => 'CMD-'.now()->format('YmdHis').'-'.random_int(100, 999),
                'source' => $validated['source'],
                'customer_name' => $validated['customer_name'] ?? null,
                'status' => $validated['payment_method'] === 'credit' ? 'pending' : $validated['payment_status'],
                'customer_id' => $validated['customer_id'] ?? null,
                'payment_method' => $validated['payment_method'],
                'fulfillment_status' => $validated['payment_method'] !== 'credit' && $validated['payment_status'] === 'paid' ? 'preparing' : 'pending',
                'delivery_person' => $validated['delivery_person'] ?? null,
                'delivery_zone' => $validated['delivery_zone'] ?? null,
                'delivery_address' => $validated['delivery_address'] ?? null,
                'total_amount' => $total,
                'cost_amount' => $cost,
                'paid_amount' => $validated['payment_method'] === 'credit' || $validated['payment_status'] !== 'paid' ? 0 : $total,
                'due_at' => $validated['due_at'] ?? null,
                'ordered_at' => now(),
            ]);
            $order->items()->createMany($lines);
        });

        return redirect()->route('pos.index')->with('status', 'Commande enregistrée et stock mis à jour.');
    }

    public function show(Request $request, SalesOrder $salesOrder): View
    {
        abort_unless($salesOrder->store_id === $this->boutique($request)->id, 404);
        $salesOrder->load('items.product');

        return view('pos.show', ['order' => $salesOrder]);
    }

    public function updateStatus(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        abort_unless($salesOrder->store_id === $this->boutique($request)->id, 404);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'paid', 'cancelled'])],
            'fulfillment_status' => ['required', Rule::in(['pending', 'preparing', 'ready', 'picked_up', 'in_transit', 'delivered', 'failed', 'returned'])],
            'delivery_person' => ['nullable', 'string', 'max:255'],
            'delivery_zone' => ['nullable', 'string', 'max:255'],
            'delivery_address' => ['nullable', 'string', 'max:255'],
        ]);
        $salesOrder->update($validated);

        return back()->with('status', 'Commande et livraison mises à jour.');
    }

    private function boutique(Request $request)
    {
        return $request->user()->currentStore();
    }
}
