<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function index(Request $request): View
    {
        $store = $request->user()->currentStore();
        $orders = $store->salesOrders()->with(['items.product', 'courier'])->whereIn('fulfillment_status', ['pending', 'preparing', 'ready', 'picked_up', 'in_transit', 'delivered', 'failed', 'returned'])->latest('ordered_at')->get();
        $couriers = $store->couriers()->where('is_active', true)->orderBy('name')->get();
        $zones = $store->deliveryZones()->orderBy('name')->get();
        $collected = $store->salesOrders()->where('payment_method', 'credit')->where('cod_collected', '>', 0)->sum('cod_collected');
        $settled = $store->deliverySettlements()->sum('amount');

        return view('delivery.index', compact('store', 'orders', 'couriers', 'zones', 'collected', 'settled'));
    }

    public function updateOrder(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        abort_unless($salesOrder->store_id === $request->user()->currentStore()->id, 404);
        $validated = $request->validate([
            'fulfillment_status' => ['required', Rule::in(['pending', 'preparing', 'ready', 'picked_up', 'in_transit', 'delivered', 'failed', 'returned'])],
            'courier_id' => ['nullable', Rule::exists('couriers', 'id')->where('store_id', $salesOrder->store_id)],
            'cod_collected' => ['nullable', 'integer', 'min:0'],
        ]);
        $salesOrder->update($validated);

        return back()->with('status', 'Suivi de livraison mis à jour.');
    }

    public function courier(Request $request): RedirectResponse
    {
        $store = $request->user()->currentStore();
        $validated = $request->validate(['name' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:30'], 'company' => ['nullable', 'string', 'max:255'], 'zones' => ['nullable', 'string', 'max:255']]);
        $store->couriers()->create($validated);

        return back()->with('status', 'Livreur ajouté.');
    }

    public function zone(Request $request): RedirectResponse
    {
        $store = $request->user()->currentStore();
        $validated = $request->validate(['name' => ['required', 'string', 'max:255'], 'base_fee' => ['required', 'integer', 'min:0'], 'free_above' => ['nullable', 'integer', 'min:0'], 'price_per_kg' => ['nullable', 'integer', 'min:0']]);
        $store->deliveryZones()->create($validated);

        return back()->with('status', 'Zone tarifaire ajoutée.');
    }

    public function settle(Request $request): RedirectResponse
    {
        $store = $request->user()->currentStore();
        $validated = $request->validate(['courier_id' => ['required', Rule::exists('couriers', 'id')->where('store_id', $store->id)], 'amount' => ['required', 'integer', 'min:1'], 'settled_at' => ['required', 'date'], 'note' => ['nullable', 'string', 'max:255']]);
        $store->deliverySettlements()->create($validated);

        return back()->with('status', 'Rapprochement enregistré.');
    }
}
