<?php

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Models\SubscriptionPlan;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('authenticated user can view inventory dashboard', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    Product::factory()->create(['store_id' => $store->id]);

    $response = $this->actingAs($user)->get('/stocks');

    $response->assertOk();
    $response->assertSee('Gestion des stocks');
    $response->assertSee('Catalogue et niveaux de stock');
    $response->assertSee('Voir');
    $response->assertSee('Modifier');
    $response->assertSee('Supprimer');
});

test('user can add a product and initial stock movement', function () {
    $user = User::factory()->create();
    Store::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post('/stocks/produits', [
        'name' => 'Riz parfumé',
        'sku' => 'RIZ-001',
        'category' => 'Alimentaire',
        'stock_quantity' => 25,
        'minimum_stock' => 5,
        'purchase_price' => 9000,
        'unit_price' => 12500,
    ]);

    $response->assertRedirect('/stocks');
    $this->assertDatabaseHas('products', ['sku' => 'RIZ-001', 'stock_quantity' => 25]);
    $this->assertDatabaseHas('stock_movements', ['type' => 'entry', 'quantity' => 25]);
});

test('premium plans allow an unlimited number of products', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $premium = SubscriptionPlan::create([
        'name' => 'Premium',
        'product_limit' => null,
        'is_active' => true,
    ]);
    $store->update(['subscription_plan_id' => $premium->id]);

    foreach (range(1, 2) as $number) {
        $this->actingAs($user)->post('/stocks/produits', [
            'name' => "Produit Premium {$number}",
            'sku' => "PREMIUM-{$number}",
            'stock_quantity' => 1,
            'minimum_stock' => 0,
            'purchase_price' => 100,
            'unit_price' => 200,
        ])->assertRedirect('/stocks');
    }

    expect($store->products()->count())->toBe(2);
});

test('finite plans prevent adding products after reaching their limit', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $plan = SubscriptionPlan::create([
        'name' => 'Essentiel',
        'product_limit' => 1,
        'is_active' => true,
    ]);
    $store->update(['subscription_plan_id' => $plan->id]);
    Product::factory()->create(['store_id' => $store->id]);

    $this->actingAs($user)
        ->post('/stocks/produits', [
            'name' => 'Produit refusé',
            'sku' => 'LIMIT-001',
            'stock_quantity' => 1,
            'minimum_stock' => 0,
            'purchase_price' => 100,
            'unit_price' => 200,
        ])
        ->assertSessionHasErrors('product');

    expect($store->products()->count())->toBe(1);
});

test('stock movement updates quantity and prevents negative stock', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $product = Product::factory()->create(['store_id' => $store->id, 'stock_quantity' => 10]);

    $response = $this->actingAs($user)->post("/stocks/produits/{$product->id}/mouvements", [
        'type' => 'exit',
        'quantity' => 4,
    ]);

    $response->assertRedirect('/stocks');
    expect($product->fresh()->stock_quantity)->toBe(6);

    $response = $this->actingAs($user)->post("/stocks/produits/{$product->id}/mouvements", [
        'type' => 'exit',
        'quantity' => 7,
    ]);

    $response->assertSessionHasErrors('quantity');
    expect($product->fresh()->stock_quantity)->toBe(6);
});

test('user cannot modify another stores product', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherStore = Store::factory()->create(['user_id' => $otherUser->id]);
    $product = Product::factory()->create(['store_id' => $otherStore->id]);

    $response = $this->actingAs($user)->post("/stocks/produits/{$product->id}/mouvements", [
        'type' => 'entry',
        'quantity' => 1,
    ]);

    $response->assertNotFound();
});

test('user can view and delete their product', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $product = Product::factory()->create(['store_id' => $store->id, 'name' => 'Produit détail']);

    $response = $this->actingAs($user)->get("/stocks/produits/{$product->id}");
    $response->assertOk()->assertSee('Produit détail')->assertSee('Détail de la fiche produit');

    $response = $this->actingAs($user)->delete("/stocks/produits/{$product->id}");
    $response->assertRedirect('/stocks');
    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});

test('user can modify their product from the catalogue action', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $product = Product::factory()->create(['store_id' => $store->id, 'name' => 'Ancien nom']);

    $response = $this->actingAs($user)->put("/stocks/produits/{$product->id}", [
        'name' => 'Nouveau nom',
        'sku' => $product->sku,
        'barcode' => '987654321',
        'category' => 'Nouvelle catégorie',
        'minimum_stock' => 4,
        'purchase_price' => 3000,
        'unit_price' => 6000,
        'is_active' => 1,
    ]);

    $response->assertRedirect('/stocks');
    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Nouveau nom',
        'category' => 'Nouvelle catégorie',
        'unit_price' => 6000,
    ]);
});

test('user cannot delete another stores product', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherStore = Store::factory()->create(['user_id' => $otherUser->id]);
    $product = Product::factory()->create(['store_id' => $otherStore->id]);

    $response = $this->actingAs($user)->delete("/stocks/produits/{$product->id}");

    $response->assertNotFound();
    $this->assertDatabaseHas('products', ['id' => $product->id]);
});

test('user can add a product with catalog details and variants', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    Store::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post('/stocks/produits', [
        'name' => 'Polo marchand',
        'sku' => 'POLO-001',
        'barcode' => '1234567890123',
        'category' => 'Mode',
        'stock_quantity' => 10,
        'minimum_stock' => 2,
        'purchase_price' => 5000,
        'unit_price' => 8500,
        'image' => UploadedFile::fake()->image('polo.png'),
        'variants' => [
            ['size' => 'M', 'color' => 'Bleu', 'sku' => 'POLO-001-M-BLEU', 'stock_quantity' => 5, 'price' => 8500],
        ],
    ]);

    $response->assertRedirect('/stocks');
    $product = Product::query()->where('sku', 'POLO-001')->firstOrFail();
    expect($product->purchase_price)->toBe(5000);
    expect($product->variants)->toHaveCount(1);
    Storage::disk('public')->assertExists($product->image_path);
});

test('user can add a supplier and create a purchase order', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $product = Product::factory()->create(['store_id' => $store->id]);

    $response = $this->actingAs($user)->post('/stocks/fournisseurs', [
        'name' => 'Grossiste Dakar',
        'contact_name' => 'Awa Ndiaye',
        'email' => 'grossiste@example.com',
        'phone' => '+221770000000',
    ]);

    $response->assertRedirect('/stocks');
    $supplier = Supplier::query()->where('name', 'Grossiste Dakar')->firstOrFail();

    $response = $this->actingAs($user)->post('/stocks/bons-de-commande', [
        'supplier_id' => $supplier->id,
        'product_id' => $product->id,
        'quantity' => 20,
        'unit_price' => 4500,
        'expected_at' => now()->addDays(5)->toDateString(),
    ]);

    $response->assertRedirect('/stocks');
    $this->assertDatabaseHas('purchase_orders', [
        'store_id' => $store->id,
        'supplier_id' => $supplier->id,
        'status' => 'ordered',
        'total_amount' => 90000,
    ]);
    expect($product->fresh()->suppliers->pluck('id')->all())->toContain($supplier->id);

    $catalogue = $this->actingAs($user)->get('/stocks');
    $catalogue->assertSee('Grossiste Dakar');
});

test('receiving a purchase order updates stock and records movements', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $supplier = Supplier::factory()->create(['store_id' => $store->id]);
    $product = Product::factory()->create(['store_id' => $store->id, 'stock_quantity' => 3]);
    $order = PurchaseOrder::factory()->create([
        'store_id' => $store->id,
        'supplier_id' => $supplier->id,
        'user_id' => $user->id,
        'status' => 'ordered',
    ]);
    $order->items()->create(['product_id' => $product->id, 'quantity' => 7, 'unit_price' => 4200]);

    $response = $this->actingAs($user)->post("/stocks/bons-de-commande/{$order->id}/receptionner");

    $response->assertRedirect('/stocks');
    expect($product->fresh()->stock_quantity)->toBe(10);
    expect($product->fresh()->purchase_price)->toBe(4200);
    expect($order->fresh()->status)->toBe('received');
    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'type' => 'entry',
        'quantity' => 7,
        'stock_after' => 10,
    ]);
});
