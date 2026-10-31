<?php

use App\Models\B2bDocument;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;

test('professional customer can receive a quote and convert it to invoice', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $customer = Customer::factory()->create(['store_id' => $store->id, 'segment' => 'professionnel']);
    $product = Product::factory()->create(['store_id' => $store->id, 'unit_price' => 10000]);

    $response = $this->actingAs($user)->post('/facturation-b2b', [
        'customer_id' => $customer->id,
        'type' => 'quote',
        'discount_percent' => 10,
        'items' => [['product_id' => $product->id, 'quantity' => 2, 'discount_percent' => 0]],
    ]);
    $response->assertRedirect();
    $quote = B2bDocument::query()->where('type', 'quote')->firstOrFail();
    expect($quote->total_amount)->toBe(18000);

    $this->actingAs($user)->post("/facturation-b2b/{$quote->id}/convertir")->assertRedirect();
    $this->assertDatabaseHas('b2b_documents', ['source_document_id' => $quote->id, 'type' => 'invoice', 'status' => 'sent', 'total_amount' => 18000]);
});

test('b2b documents cannot cross store boundaries', function () {
    $user = User::factory()->create();
    $otherStore = Store::factory()->create();
    $customer = Customer::factory()->create(['store_id' => $otherStore->id, 'segment' => 'professionnel']);
    $document = B2bDocument::create([
        'store_id' => $otherStore->id, 'customer_id' => $customer->id, 'number' => 'INV-OTHER-1',
        'type' => 'invoice', 'status' => 'sent', 'issued_at' => today(), 'total_amount' => 1000,
    ]);

    $this->actingAs($user)->post("/facturation-b2b/{$document->id}/convertir")->assertNotFound();
});
