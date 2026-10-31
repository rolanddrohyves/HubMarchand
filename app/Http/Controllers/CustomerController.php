<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $store = $this->boutique($request);
        $customers = $store->customers()
            ->withSum(['orders as purchases_total' => fn ($query) => $query->where('status', '!=', 'cancelled')], 'total_amount')
            ->withSum(['orders as credit_total' => fn ($query) => $query->where('payment_method', 'credit')->where('status', '!=', 'cancelled')], 'total_amount')
            ->withSum(['orders as paid_total' => fn ($query) => $query->where('payment_method', 'credit')->where('status', '!=', 'cancelled')], 'paid_amount')
            ->withCount('orders')
            ->latest()
            ->paginate(15)
            ->withQueryString();
        $creditOrders = $store->salesOrders()
            ->with('customer')
            ->where('payment_method', 'credit')
            ->where('status', '!=', 'cancelled')
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->latest('due_at')
            ->get();

        return view('customers.index', compact('store', 'customers', 'creditOrders'));
    }

    public function store(Request $request): RedirectResponse
    {
        $store = $this->boutique($request);
        $request->merge([
            'payment_terms' => $request->input('payment_terms', 'immediate'),
            'deposit_percent' => $request->input('deposit_percent', 0),
        ]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'segment' => ['required', Rule::in(['particulier', 'professionnel', 'vip'])],
            'payment_terms' => ['required', Rule::in(['immediate', '30_days', '60_days_eom'])],
            'deposit_percent' => ['required', 'integer', 'between:0,100'],
        ]);
        $store->customers()->create($validated);

        return back()->with('status', 'Client ajouté.');
    }

    public function show(Request $request, Customer $customer): View
    {
        abort_unless($customer->store_id === $this->boutique($request)->id, 404);
        $customer->load([
            'orders' => fn ($query) => $query->with('items.product')->latest('ordered_at'),
            'creditPayments' => fn ($query) => $query->latest('paid_at'),
            'reminders' => fn ($query) => $query->latest('reminded_at'),
        ]);

        return view('customers.show', compact('customer'));
    }

    public function payment(Request $request, Customer $customer): RedirectResponse
    {
        abort_unless($customer->store_id === $this->boutique($request)->id, 404);
        $validated = $request->validate([
            'sales_order_id' => ['required', Rule::exists('sales_orders', 'id')->where('customer_id', $customer->id)->where('store_id', $customer->store_id)],
            'amount' => ['required', 'integer', 'min:1'],
            'paid_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $order = $customer->orders()->findOrFail($validated['sales_order_id']);
        if ($validated['amount'] > $order->outstandingAmount()) {
            return back()->withErrors(['amount' => 'Le remboursement dépasse le solde de cette dette.']);
        }
        $customer->creditPayments()->create($validated + ['sales_order_id' => $order->id]);
        $order->increment('paid_amount', $validated['amount']);
        if ($order->fresh()->paid_amount >= $order->total_amount) {
            $order->update(['status' => 'paid']);
        }

        return back()->with('status', 'Remboursement enregistré.');
    }

    public function remind(Request $request, Customer $customer): RedirectResponse
    {
        abort_unless($customer->store_id === $this->boutique($request)->id, 404);
        $validated = $request->validate(['sales_order_id' => ['nullable', Rule::exists('sales_orders', 'id')->where('customer_id', $customer->id)]]);
        $customer->reminders()->create([
            'sales_order_id' => $validated['sales_order_id'] ?? null,
            'message' => 'Relance envoyée au client concernant son solde impayé.',
            'reminded_at' => today(),
        ]);

        return back()->with('status', 'Relance enregistrée.');
    }

    private function boutique(Request $request)
    {
        return $request->user()->currentStore();
    }
}
