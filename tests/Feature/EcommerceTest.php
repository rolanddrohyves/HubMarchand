<?php

use App\Models\Product;
use App\Models\Store;
use App\Models\User;

test('merchant can configure ecommerce and publish a product', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $product = Product::factory()->create(['store_id' => $store->id]);

    $this->actingAs($user)->put('/ecommerce', [
        'shop_theme' => 'mode',
        'primary_color' => '#112233',
        'subdomain' => 'ma-boutique',
        'web_payment_methods' => ['mobile_money'],
        'cash_on_delivery' => '1',
        'homepage_blocks' => ['banner', 'featured'],
        'shop_tagline' => 'La qualité au meilleur prix',
        'shop_announcement' => 'Livraison offerte cette semaine.',
    ])->assertRedirect();

    $this->actingAs($user)->put("/ecommerce/produits/{$product->id}/publication", ['is_published' => '1'])
        ->assertRedirect();

    expect($store->fresh()->shop_theme)->toBe('mode')
        ->and($store->fresh()->shop_tagline)->toBe('La qualité au meilleur prix')
        ->and($product->fresh()->is_published)->toBeTrue();
});

test('merchant can assign a web collection and storefront displays it', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id, 'enabled_modules' => null]);
    $product = Product::factory()->create(['store_id' => $store->id, 'is_published' => true, 'web_collection' => 'Nouveautés']);

    $this->get("/boutique/{$store->id}")->assertOk()->assertSee('Nouveautés')->assertSee($product->name);
});

test('public shop only shows published products and checkout updates stock', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id, 'enabled_modules' => null, 'web_payment_methods' => ['mobile_money']]);
    $published = Product::factory()->create(['store_id' => $store->id, 'is_published' => true, 'stock_quantity' => 5, 'unit_price' => 2500]);
    $hidden = Product::factory()->create(['store_id' => $store->id, 'is_published' => false, 'name' => 'Produit caché']);

    $this->get("/boutique/{$store->id}")->assertOk()->assertSee($published->name)->assertDontSee($hidden->name);

    $this->post("/boutique/{$store->id}/commander", [
        'items' => json_encode([['product_id' => $published->id, 'quantity' => 2]]),
        'customer_name' => 'Client Web',
        'customer_phone' => '770000000',
        'delivery_address' => 'Dakar',
        'payment_method' => 'mobile_money',
    ])->assertRedirect();

    expect($published->fresh()->stock_quantity)->toBe(3);
    $this->assertDatabaseHas('sales_orders', ['store_id' => $store->id, 'source' => 'ecommerce', 'customer_name' => 'Client Web - 770000000']);
});
