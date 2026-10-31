<?php

use App\Models\Expense;
use App\Models\SalesOrder;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;

test('dashboard shows daily sales, pending orders, stock alerts, and net profit', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $store->products()->create([
        'name' => 'Produit presque épuisé',
        'sku' => 'ALERTE-001',
        'stock_quantity' => 2,
        'minimum_stock' => 5,
        'purchase_price' => 4000,
        'unit_price' => 7000,
    ]);

    SalesOrder::factory()->create([
        'store_id' => $store->id,
        'status' => 'paid',
        'total_amount' => 100000,
        'cost_amount' => 60000,
        'ordered_at' => now(),
    ]);
    SalesOrder::factory()->create([
        'store_id' => $store->id,
        'status' => 'pending',
        'total_amount' => 50000,
        'ordered_at' => now(),
    ]);
    SalesOrder::factory()->create([
        'store_id' => $store->id,
        'status' => 'paid',
        'total_amount' => 30000,
        'cost_amount' => 15000,
        'ordered_at' => Carbon::yesterday(),
    ]);
    Expense::factory()->create([
        'store_id' => $store->id,
        'amount' => 10000,
        'spent_at' => today(),
    ]);

    $response = $this->actingAs($user)->get('/accueil');

    $response->assertOk()
        ->assertSee('100 000 F CFA')
        ->assertSee('Finances du jour')
        ->assertSee('30 000 F CFA')
        ->assertSee('Commandes à traiter')
        ->assertSee('Urgences stock')
        ->assertSee('Ventes et marges')
        ->assertSee('Dépenses');
});

test('dashboard only shows metrics for the authenticated users store', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $otherStore = Store::factory()->create();

    SalesOrder::factory()->create([
        'store_id' => $store->id,
        'status' => 'paid',
        'total_amount' => 25000,
        'cost_amount' => 10000,
        'ordered_at' => now(),
    ]);
    SalesOrder::factory()->create([
        'store_id' => $otherStore->id,
        'status' => 'paid',
        'total_amount' => 999999,
        'cost_amount' => 0,
        'ordered_at' => now(),
    ]);

    $response = $this->actingAs($user)->get('/accueil');

    $response->assertOk()->assertSee('25 000 F CFA')->assertDontSee('999 999 F CFA');
});

test('dashboard supports a negative margin when cost exceeds sales price', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);

    SalesOrder::factory()->create([
        'store_id' => $store->id,
        'status' => 'paid',
        'total_amount' => 300000,
        'cost_amount' => 2000000,
        'ordered_at' => now(),
    ]);

    $response = $this->actingAs($user)->get('/accueil');

    $response->assertOk()
        ->assertSee('Bénéfice net')
        ->assertSee('-1 700 000 F CFA');
});

test('dashboard shows flash KPIs, operational alerts, channels, and visitors', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $store->products()->create([
        'name' => 'Stock critique',
        'sku' => 'FLASH-001',
        'stock_quantity' => 0,
        'minimum_stock' => 2,
        'purchase_price' => 1000,
        'unit_price' => 2000,
    ]);
    SalesOrder::factory()->create([
        'store_id' => $store->id,
        'source' => 'ecommerce',
        'status' => 'paid',
        'fulfillment_status' => 'preparing',
        'customer_name' => 'Client Web',
        'total_amount' => 12000,
        'ordered_at' => now(),
    ]);
    $store->shopVisits()->create([
        'visitor_key' => 'visitor-1',
        'visited_at' => now(),
    ]);

    $response = $this->actingAs($user)->get('/accueil');

    $response->assertOk()
        ->assertSee('Vue d’ensemble')
        ->assertSee('Panier moyen du jour')
        ->assertSee('Visiteurs boutique en ligne')
        ->assertSee('Client Web')
        ->assertSee('Site web')
        ->assertSee('Stock critique');
});
