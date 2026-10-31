<?php

use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Store;
use App\Models\SubscriptionPlan;
use App\Models\User;

test('copilot dashboard shows stock recommendations and is isolated by store', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $plan = SubscriptionPlan::create(['name' => 'Premium', 'ai_included' => true, 'is_active' => true]);
    $store->update(['subscription_plan_id' => $plan->id]);
    Product::factory()->create(['store_id' => $store->id, 'name' => 'Produit local', 'stock_quantity' => 2, 'minimum_stock' => 5]);
    $otherProduct = Product::factory()->create(['name' => 'Produit autre boutique']);

    $response = $this->actingAs($user)->get('/copilot');

    $response->assertOk()->assertSee('Produit local')->assertDontSee('Produit autre boutique');
});

test('copilot generates product content and faq responses', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $plan = SubscriptionPlan::create(['name' => 'Premium', 'ai_included' => true, 'is_active' => true]);
    $store->update(['subscription_plan_id' => $plan->id]);
    $product = Product::factory()->create(['store_id' => $store->id, 'name' => 'T-shirt noir', 'category' => 'Mode']);

    $this->actingAs($user)->post('/copilot/contenu', ['product_id' => $product->id, 'tone' => 'vendeur'])
        ->assertRedirect()->assertSessionHas('generated_content');
    $this->actingAs($user)->post('/copilot/faq', ['question' => 'Quels produits sont disponibles ?'])
        ->assertRedirect()->assertSessionHas('faq_response');
});

test('copilot cannot prepare a relance for another store order', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $plan = SubscriptionPlan::create(['name' => 'Premium', 'ai_included' => true, 'is_active' => true]);
    $store->update(['subscription_plan_id' => $plan->id]);
    $order = SalesOrder::factory()->create();

    $this->actingAs($user)->post("/copilot/paniers/{$order->id}/relance")->assertNotFound();
});

test('free plans cannot access the copilot module', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $plan = SubscriptionPlan::create(['name' => 'Gratuit', 'ai_included' => false, 'is_active' => true]);
    $store->update(['subscription_plan_id' => $plan->id]);

    $this->actingAs($user)
        ->get('/copilot')
        ->assertRedirect('/accueil')
        ->assertSessionHas('error');
});
