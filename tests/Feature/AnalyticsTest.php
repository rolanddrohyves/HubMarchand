<?php

use App\Models\Expense;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Store;
use App\Models\User;

test('analytics dashboard calculates KPIs and product performance for a selected period', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $product = Product::factory()->create([
        'store_id' => $store->id,
        'name' => 'Produit rentable',
        'purchase_price' => 4000,
        'unit_price' => 7000,
    ]);
    $order = SalesOrder::factory()->create([
        'store_id' => $store->id,
        'status' => 'paid',
        'source' => 'ecommerce',
        'payment_method' => 'mobile_money',
        'total_amount' => 14000,
        'cost_amount' => 8000,
        'ordered_at' => now(),
    ]);
    SalesOrderItem::query()->create([
        'sales_order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 7000,
        'unit_cost' => 4000,
    ]);
    Expense::factory()->create(['store_id' => $store->id, 'amount' => 1000, 'spent_at' => today()]);

    $response = $this->actingAs($user)->get('/analytique?period=day');

    $response->assertOk()
        ->assertSee('14 000 F CFA')
        ->assertSee('Marge nette')
        ->assertSee('Produit rentable')
        ->assertSee('Mobile Money');
});

test('analytics expense register is isolated by store', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $otherStore = Store::factory()->create();
    $otherExpense = Expense::factory()->create(['store_id' => $otherStore->id, 'label' => 'Dépense privée']);

    $response = $this->actingAs($user)->get('/analytique');

    $response->assertOk()->assertDontSee('Dépense privée');

    $this->actingAs($user)->delete("/analytique/depenses/{$otherExpense->id}")->assertNotFound();
});
