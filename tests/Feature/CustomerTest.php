<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Store;
use App\Models\User;

test('customer file shows purchase history and outstanding credit', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $customer = Customer::factory()->create(['store_id' => $store->id, 'name' => 'Entreprise Alpha']);
    SalesOrder::factory()->create([
        'store_id' => $store->id,
        'customer_id' => $customer->id,
        'payment_method' => 'credit',
        'status' => 'pending',
        'total_amount' => 100000,
        'paid_amount' => 25000,
        'customer_name' => $customer->name,
    ]);

    $response = $this->actingAs($user)->get('/clients');

    $response->assertOk()
        ->assertSee('Entreprise Alpha')
        ->assertSee('Dû : 75 000');
});

test('credit sale requires a customer and payment cannot exceed outstanding balance', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $product = Product::factory()->create(['store_id' => $store->id, 'stock_quantity' => 5, 'unit_price' => 10000]);
    $customer = Customer::factory()->create(['store_id' => $store->id]);

    $missingCustomer = $this->actingAs($user)->post('/caisse/commandes', [
        'source' => 'pos',
        'payment_method' => 'credit',
        'payment_status' => 'pending',
        'due_at' => now()->addDays(30)->toDateString(),
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ]);
    $missingCustomer->assertSessionHasErrors('customer_id');

    $sale = $this->actingAs($user)->post('/caisse/commandes', [
        'source' => 'pos',
        'payment_method' => 'credit',
        'payment_status' => 'pending',
        'customer_id' => $customer->id,
        'due_at' => now()->addDays(30)->toDateString(),
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ]);
    $sale->assertRedirect('/caisse');
    $order = SalesOrder::query()->where('customer_id', $customer->id)->firstOrFail();

    $payment = $this->actingAs($user)->post("/clients/{$customer->id}/paiements", [
        'sales_order_id' => $order->id,
        'amount' => 10001,
        'paid_at' => today()->toDateString(),
    ]);
    $payment->assertSessionHasErrors('amount');
    expect($order->fresh()->paid_amount)->toBe(0);
});

test('customer details cannot be opened across stores', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $otherCustomer = Customer::factory()->create();

    $response = $this->actingAs($user)->get("/clients/{$otherCustomer->id}");

    $response->assertNotFound();
});
