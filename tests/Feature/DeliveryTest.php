<?php

use App\Models\Courier;
use App\Models\SalesOrder;
use App\Models\Store;
use App\Models\User;

test('delivery dashboard shows orders and can assign a courier', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $courier = Courier::create(['store_id' => $store->id, 'name' => 'Moussa Livraison', 'phone' => '770000000']);
    $order = SalesOrder::factory()->create(['store_id' => $store->id, 'fulfillment_status' => 'ready']);

    $this->actingAs($user)->get('/livraisons')->assertOk()->assertSee('Moussa Livraison');
    $response = $this->actingAs($user)->put("/livraisons/commandes/{$order->id}", [
        'fulfillment_status' => 'in_transit',
        'courier_id' => $courier->id,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('sales_orders', ['id' => $order->id, 'courier_id' => $courier->id, 'fulfillment_status' => 'in_transit']);
});

test('courier and delivery zone are isolated to the current store', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $otherStore = Store::factory()->create();
    $otherCourier = Courier::create(['store_id' => $otherStore->id, 'name' => 'Autre livreur']);
    $order = SalesOrder::factory()->create(['store_id' => $store->id]);

    $response = $this->actingAs($user)->put("/livraisons/commandes/{$order->id}", [
        'fulfillment_status' => 'in_transit',
        'courier_id' => $otherCourier->id,
    ]);

    $response->assertSessionHasErrors('courier_id');
});
