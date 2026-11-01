<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $store = $request->user()->currentStore();

        $products = $store->products()
            ->with(['variants', 'suppliers'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->when($request->input('stock') === 'low', fn ($query) => $query->whereColumn('stock_quantity', '<=', 'minimum_stock'))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'products' => $store->products()->where('is_active', true)->count(),
            'units' => $store->products()->sum('stock_quantity'),
            'low_stock' => $store->products()->whereColumn('stock_quantity', '<=', 'minimum_stock')->count(),
            'value' => $store->products()->where('is_active', true)->sum(DB::raw('stock_quantity * unit_price')),
        ];
        $movements = StockMovement::query()
            ->with('product')
            ->whereHas('product', fn ($query) => $query->where('store_id', $store->id))
            ->latest()
            ->limit(10)
            ->get();
        $suppliers = $store->suppliers()->latest()->get();
        $purchaseOrders = $store->purchaseOrders()->with('supplier')->latest()->limit(10)->get();

        return view('inventory.index', compact('store', 'products', 'stats', 'movements', 'suppliers', 'purchaseOrders'));
    }

    public function store(Request $request): RedirectResponse
    {
        $store = $request->user()->currentStore();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products')->where('store_id', $store->id)],
            'barcode' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'purchase_price' => ['required', 'integer', 'min:0'],
            'unit_price' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'variants' => ['nullable', 'array'],
            'variants.*.size' => ['nullable', 'string', 'max:50'],
            'variants.*.color' => ['nullable', 'string', 'max:50'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.stock_quantity' => ['nullable', 'integer', 'min:0'],
            'variants.*.price' => ['nullable', 'integer', 'min:0'],
        ], [
            'image.max' => 'La taille de l’image ne doit pas dépasser 2 Mo.',
        ]);

        $productLimit = $store->subscriptionPlan?->product_limit;
        if ($productLimit !== null && $store->products()->count() >= $productLimit) {
            return back()
                ->withErrors(['product' => 'La limite de produits de votre forfait est atteinte.'])
                ->withInput();
        }

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }
        unset($validated['image'], $validated['variants']);
        $product = $store->products()->create($validated);
        $product->variants()->createMany(array_filter($request->input('variants', []), fn (array $variant): bool => filled($variant['size'] ?? null) || filled($variant['color'] ?? null)));

        if ($product->stock_quantity > 0) {
            $product->movements()->create([
                'user_id' => $request->user()->id,
                'type' => 'entry',
                'quantity' => $product->stock_quantity,
                'stock_after' => $product->stock_quantity,
                'reason' => 'Stock initial',
            ]);
        }

        return redirect()->route('inventory.index')->with('status', 'Produit ajouté au stock.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->ensureProductBelongsToUser($request, $product);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products')->where('store_id', $product->store_id)->ignore($product->id)],
            'barcode' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'purchase_price' => ['required', 'integer', 'min:0'],
            'unit_price' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }
        $product->update($validated);

        return redirect()->route('inventory.index')->with('status', 'Produit mis à jour.');
    }

    public function show(Request $request, Product $product): View
    {
        $this->ensureProductBelongsToUser($request, $product);
        $product->load(['variants', 'suppliers', 'movements' => fn ($query) => $query->latest()->limit(20)]);

        return view('inventory.show', compact('product'));
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->ensureProductBelongsToUser($request, $product);

        if ($product->purchaseOrderItems()->exists()) {
            return back()->withErrors(['product' => 'Ce produit ne peut pas être supprimé car il est utilisé dans un bon de commande.']);
        }

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        $product->delete();

        return redirect()->route('inventory.index')->with('status', 'Produit supprimé.');
    }

    public function movement(Request $request, Product $product): RedirectResponse
    {
        $this->ensureProductBelongsToUser($request, $product);

        $validated = $request->validate([
            'type' => ['required', 'in:entry,exit,adjustment'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $newStock = match ($validated['type']) {
            'entry' => $product->stock_quantity + $validated['quantity'],
            'exit' => $product->stock_quantity - $validated['quantity'],
            'adjustment' => $validated['quantity'],
        };

        if ($newStock < 0) {
            return back()->withErrors(['quantity' => 'La sortie dépasse le stock disponible.'])->withInput();
        }

        DB::transaction(function () use ($product, $request, $validated, $newStock): void {
            $product->update(['stock_quantity' => $newStock]);
            $product->movements()->create([
                'user_id' => $request->user()->id,
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'stock_after' => $newStock,
                'reason' => $validated['reason'] ?? null,
                'note' => $validated['note'] ?? null,
            ]);
        });

        return redirect()->route('inventory.index')->with('status', 'Mouvement de stock enregistré.');
    }

    public function supplier(Request $request): RedirectResponse
    {
        $store = $request->user()->currentStore();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $store->suppliers()->create($validated);

        return redirect()->route('inventory.index')->with('status', 'Fournisseur ajouté.');
    }

    public function purchaseOrder(Request $request): RedirectResponse
    {
        $store = $request->user()->currentStore();
        $validated = $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('store_id', $store->id)],
            'product_id' => ['required', Rule::exists('products', 'id')->where('store_id', $store->id)],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'integer', 'min:0'],
            'expected_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $store, $validated): void {
            $order = $store->purchaseOrders()->create([
                'supplier_id' => $validated['supplier_id'],
                'user_id' => $request->user()->id,
                'reference' => 'BC-'.now()->format('YmdHis').'-'.random_int(100, 999),
                'status' => 'ordered',
                'expected_at' => $validated['expected_at'] ?? null,
                'total_amount' => $validated['quantity'] * $validated['unit_price'],
                'notes' => $validated['notes'] ?? null,
            ]);
            $order->items()->create([
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'unit_price' => $validated['unit_price'],
            ]);
            $order->supplier->products()->syncWithoutDetaching([$validated['product_id']]);
        });

        return redirect()->route('inventory.index')->with('status', 'Bon de commande créé.');
    }

    public function receivePurchaseOrder(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($purchaseOrder->store_id === $request->user()->store?->id, 404);

        if ($purchaseOrder->status !== 'ordered') {
            return back()->withErrors(['purchase_order' => 'Seul un bon de commande en cours peut être réceptionné.']);
        }

        DB::transaction(function () use ($purchaseOrder, $request): void {
            $order = PurchaseOrder::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->id);

            foreach ($order->items as $item) {
                $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);
                $newStock = $product->stock_quantity + $item->quantity;
                $product->update([
                    'stock_quantity' => $newStock,
                    'purchase_price' => $item->unit_price,
                ]);
                $product->movements()->create([
                    'user_id' => $request->user()->id,
                    'type' => 'entry',
                    'quantity' => $item->quantity,
                    'stock_after' => $newStock,
                    'reason' => 'Réception '.$order->reference,
                ]);
            }

            $order->update([
                'status' => 'received',
                'received_at' => now()->toDateString(),
            ]);
        });

        return redirect()->route('inventory.index')->with('status', 'Bon de commande réceptionné et stock mis à jour.');
    }

    private function ensureProductBelongsToUser(Request $request, Product $product): void
    {
        abort_unless($product->store_id === $request->user()->store?->id, 404);
    }
}
