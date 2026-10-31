<?php

use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Store;
use App\Models\User;

test('authenticated user can open the register and order list', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    Product::factory()->create(['store_id' => $store->id, 'stock_quantity' => 10]);
    $outOfStock = Product::factory()->create(['store_id' => $store->id, 'name' => 'Produit en rupture', 'stock_quantity' => 0]);

    $response = $this->actingAs($user)->get('/caisse');

    $response->assertOk()
        ->assertSee('Écran de caisse')
        ->assertSee('Commandes')
        ->assertSee('Produit en rupture')
        ->assertSee('data-stock="0"', false);
});

test('pos sale decreases stock and records a paid order', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $product = Product::factory()->create([
        'store_id' => $store->id,
        'stock_quantity' => 10,
        'purchase_price' => 4000,
        'unit_price' => 7000,
    ]);

    $response = $this->actingAs($user)->post('/caisse/commandes', [
        'source' => 'pos',
        'payment_status' => 'paid',
        'customer_name' => 'Client comptoir',
        'items' => [['product_id' => $product->id, 'quantity' => 3]],
    ]);

    $response->assertRedirect('/caisse');
    expect($product->fresh()->stock_quantity)->toBe(7);
    $this->assertDatabaseHas('sales_orders', [
        'store_id' => $store->id,
        'status' => 'paid',
        'total_amount' => 21000,
        'cost_amount' => 12000,
        'source' => 'pos',
    ]);
    $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'exit', 'quantity' => 3, 'stock_after' => 7]);
});

test('pos refuses a quantity greater than available stock', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $product = Product::factory()->create(['store_id' => $store->id, 'stock_quantity' => 2]);

    $response = $this->actingAs($user)->post('/caisse/commandes', [
        'source' => 'pos',
        'payment_status' => 'paid',
        'items' => [['product_id' => $product->id, 'quantity' => 3]],
    ]);

    $response->assertStatus(422);
    expect($product->fresh()->stock_quantity)->toBe(2);
    expect(SalesOrder::count())->toBe(0);
});

test('user can update fulfillment and delivery details', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $order = SalesOrder::factory()->create(['store_id' => $store->id, 'status' => 'paid', 'fulfillment_status' => 'pending']);

    $response = $this->actingAs($user)->put("/caisse/commandes/{$order->id}/statut", [
        'status' => 'paid',
        'fulfillment_status' => 'ready',
        'delivery_person' => 'Moussa',
        'delivery_zone' => 'Dakar Plateau',
        'delivery_address' => 'Rue 10',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('sales_orders', ['id' => $order->id, 'fulfillment_status' => 'ready', 'delivery_person' => 'Moussa', 'delivery_zone' => 'Dakar Plateau']);
});
