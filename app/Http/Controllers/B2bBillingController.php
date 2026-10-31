<?php

namespace App\Http\Controllers;

use App\Models\B2bDocument;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class B2bBillingController extends Controller
{
    public function index(Request $request): View
    {
        $store = $request->user()->currentStore();
        $documents = $store->b2bDocuments()->with('customer')->latest('issued_at')->paginate(15)->withQueryString();
        $customers = $store->customers()->whereIn('segment', ['professionnel', 'vip'])->orderBy('name')->get();
        $products = $store->products()->where('is_active', true)->orderBy('name')->get();
        $overdue = $store->b2bDocuments()->with('customer')->whereIn('type', ['invoice', 'credit_note'])->whereIn('status', ['sent', 'partially_paid'])->whereNotNull('due_at')->whereDate('due_at', '<', today())->whereColumn('paid_amount', '<', 'total_amount')->get();

        return view('billing.index', compact('store', 'documents', 'customers', 'products', 'overdue'));
    }

    public function store(Request $request): RedirectResponse
    {
        $store = $request->user()->currentStore();
        $validated = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('store_id', $store->id)],
            'type' => ['required', Rule::in(['quote', 'proforma', 'invoice', 'credit_note'])],
            'valid_until' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'discount_percent' => ['required', 'integer', 'between:0,100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('store_id', $store->id)],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.discount_percent' => ['required', 'integer', 'between:0,100'],
        ]);
        $total = 0;
        $lines = [];
        foreach ($validated['items'] as $item) {
            $product = Product::query()->where('store_id', $store->id)->findOrFail($item['product_id']);
            $lineSubtotal = $product->unit_price * $item['quantity'];
            $lineTotal = (int) round($lineSubtotal * (1 - ($item['discount_percent'] / 100)));
            $total += $lineTotal;
            $lines[] = ['product_id' => $product->id, 'description' => $product->name, 'quantity' => $item['quantity'], 'unit_price' => $product->unit_price, 'discount_percent' => $item['discount_percent'], 'line_total' => $lineTotal];
        }
        $discountAmount = (int) round($total * ($validated['discount_percent'] / 100));
        $document = $store->b2bDocuments()->create([
            'customer_id' => $validated['customer_id'],
            'number' => strtoupper($validated['type']).'-'.now()->format('YmdHis').'-'.random_int(100, 999),
            'type' => $validated['type'],
            'status' => 'draft',
            'issued_at' => today(),
            'valid_until' => $validated['valid_until'] ?? null,
            'due_at' => $validated['due_at'] ?? null,
            'subtotal' => $total,
            'discount_amount' => $discountAmount,
            'total_amount' => max(0, $total - $discountAmount),
            'discount_percent' => $validated['discount_percent'],
            'notes' => $validated['notes'] ?? null,
        ]);
        $document->items()->createMany($lines);

        return back()->with('status', 'Document B2B créé.');
    }

    public function convert(Request $request, B2bDocument $document): RedirectResponse
    {
        abort_unless($document->store_id === $request->user()->currentStore()->id, 404);
        abort_unless($document->type === 'quote' && in_array($document->status, ['draft', 'sent'], true), 422);
        $invoice = $document->replicate();
        $invoice->source_document_id = $document->id;
        $invoice->number = 'INV-'.now()->format('YmdHis').'-'.random_int(100, 999);
        $invoice->type = 'invoice';
        $invoice->status = 'sent';
        $invoice->issued_at = today();
        $invoice->save();
        foreach ($document->items as $item) {
            $invoice->items()->create($item->only(['product_id', 'description', 'quantity', 'unit_price', 'discount_percent', 'line_total']));
        }

        return back()->with('status', 'Devis converti en facture.');
    }

    public function show(Request $request, B2bDocument $document): View
    {
        abort_unless($document->store_id === $request->user()->currentStore()->id, 404);
        $document->load(['customer', 'store', 'items.product']);

        return view('billing.show', compact('document'));
    }

    public function updateStatus(Request $request, B2bDocument $document): RedirectResponse
    {
        abort_unless($document->store_id === $request->user()->currentStore()->id, 404);
        $validated = $request->validate(['status' => ['required', Rule::in(['draft', 'sent', 'paid', 'partially_paid', 'cancelled'])], 'paid_amount' => ['nullable', 'integer', 'min:0']]);
        $document->update($validated);
        if (($validated['paid_amount'] ?? 0) >= $document->total_amount && $document->total_amount > 0) {
            $document->update(['status' => 'paid']);
        }

        return back()->with('status', 'Statut de la facture mis à jour.');
    }
}
