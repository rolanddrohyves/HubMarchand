<?php

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('authenticated user can view boutique settings', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/parametres');

    $response->assertOk();
    $response->assertSee('Paramètres de la boutique');
    $this->assertDatabaseHas('stores', [
        'user_id' => $user->id,
        'email' => $user->email,
    ]);
});

test('maintenance mode blocks merchants but keeps super admin access', function () {
    $merchant = User::factory()->create();
    $admin = User::factory()->create(['is_super_admin' => true]);
    SystemSetting::current()->update(['maintenance_mode' => true]);

    $this->actingAs($merchant)->get('/accueil')
        ->assertStatus(503)
        ->assertSee('Maintenance en cours');
    $this->actingAs($admin)->get('/super-admin/parametres')->assertOk();
});

test('maintenance mode keeps login accessible', function () {
    SystemSetting::current()->update(['maintenance_mode' => true]);

    $this->get('/login')->assertOk();
});

test('authenticated user can update boutique settings', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put('/parametres', [
        'name' => 'Boutique Rolan',
        'email' => 'boutique@example.com',
        'description' => 'Une boutique de démonstration.',
        'phone' => '+33 1 00 00 00 00',
        'website' => 'https://boutique.example.com',
        'address' => '1 rue du Commerce',
        'country' => 'SN',
        'currency' => 'XOF',
        'timezone' => 'Africa/Dakar',
        'date_format' => 'd/m/Y',
    ]);

    $response->assertRedirect('/parametres');
    $response->assertSessionHas('status');
    $this->assertDatabaseHas('stores', [
        'user_id' => $user->id,
        'name' => 'Boutique Rolan',
        'website' => 'https://boutique.example.com',
    ]);
});

test('authenticated user can upload a boutique logo', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put('/parametres', [
        'name' => 'Boutique Rolan',
        'email' => $user->email,
        'country' => 'SN',
        'currency' => 'XOF',
        'timezone' => 'Africa/Dakar',
        'date_format' => 'd/m/Y',
        'logo' => UploadedFile::fake()->image('logo.png'),
    ]);

    $response->assertRedirect('/parametres');
    $store = $user->fresh()->store;

    expect($store->logo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($store->logo_path);
});

test('authenticated user can configure payment methods and marketplace modules', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put('/parametres', [
        'name' => 'Boutique Rolan',
        'email' => $user->email,
        'country' => 'SN',
        'currency' => 'XOF',
        'timezone' => 'Africa/Dakar',
        'date_format' => 'd/m/Y',
        'payment_methods' => ['mobile_money', 'cash'],
        'enabled_modules' => ['stock', 'pos'],
    ]);

    $response->assertRedirect('/parametres');
    expect($user->fresh()->store->payment_methods)->toBe(['mobile_money', 'cash'])
        ->and($user->fresh()->store->enabled_modules)->toBe(['stock', 'pos']);
});

test('authenticated user can disable every marketplace module', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put('/parametres', [
        'name' => 'Boutique Rolan',
        'email' => $user->email,
        'country' => 'SN',
        'currency' => 'XOF',
        'timezone' => 'Africa/Dakar',
        'date_format' => 'd/m/Y',
        'payment_methods' => ['cash'],
    ])->assertRedirect('/parametres');

    expect($user->fresh()->store->enabled_modules)->toBe([]);
});

test('authenticated user can add and remove a team member', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/parametres/equipe', [
        'name' => 'Vendeur Boutique',
        'email' => 'vendeur@example.com',
        'password' => 'password123',
        'role' => 'seller',
    ]);

    $response->assertRedirect();
    $member = User::query()->where('email', 'vendeur@example.com')->firstOrFail();
    expect($member->store_id)->toBe($user->fresh()->store->id)
        ->and($member->role)->toBe('seller');

    $this->actingAs($user)->delete("/parametres/equipe/{$member->id}")->assertRedirect();
    $this->assertDatabaseMissing('users', ['id' => $member->id]);
});
