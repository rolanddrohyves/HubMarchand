<?php

use App\Models\PlatformAuditLog;
use App\Models\Store;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\SubscriptionExpiredNotification;
use Illuminate\Support\Facades\Notification;

test('only super admins can access the platform panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/super-admin')->assertForbidden();
});

test('super admin is redirected to the platform panel from the merchant home', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    $this->actingAs($admin)
        ->get(route('accueil'))
        ->assertRedirect(route('super-admin.index'));

    expect($admin->fresh()->store)->toBeNull();
});

test('super admins cannot create a merchant store implicitly', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    expect(fn () => $admin->currentStore())
        ->toThrow(LogicException::class, 'Un Super Admin ne possède pas de boutique marchand.');
});

test('super admins can suspend a merchant and the action is audited', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $merchant = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $merchant->id]);

    $this->actingAs($admin)
        ->put(route('super-admin.merchants.toggle', $store))
        ->assertRedirect()
        ->assertSessionHas('status', 'Boutique suspendue.');

    expect($store->fresh()->status)->toBe('suspended');
    expect(PlatformAuditLog::query()->where('store_id', $store->id)->where('action', 'merchant.suspended')->exists())->toBeTrue();
});

test('super admins can create a merchant with a subscription plan', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $plan = SubscriptionPlan::create(['name' => 'Premium', 'monthly_price' => 15000, 'is_active' => true]);

    $this->actingAs($admin)
        ->post(route('super-admin.merchants.store'), [
            'name' => 'Nouveau marchand',
            'email' => 'marchand@example.com',
            'password' => 'password123',
            'store_name' => 'Boutique Premium',
            'plan_id' => $plan->id,
        ])
        ->assertRedirect();

    $merchant = User::where('email', 'marchand@example.com')->firstOrFail();
    expect($merchant->is_super_admin)->toBeFalse();
    expect($merchant->store->subscription_plan_id)->toBe($plan->id);
});

test('super admins can view and edit a merchant', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $merchant = User::factory()->create(['name' => 'Marchand Original']);
    $store = Store::factory()->create(['user_id' => $merchant->id, 'name' => 'Boutique Original']);
    $plan = SubscriptionPlan::create(['name' => 'Pro', 'monthly_price' => 3000, 'annual_price' => 30000, 'product_limit' => 50, 'payment_methods' => ['mobile_money'], 'is_renewable' => true]);

    $this->actingAs($admin)->get(route('super-admin.merchants.show', $store))->assertOk()->assertSee('Boutique Original');
    $this->actingAs($admin)->put(route('super-admin.merchants.update', $store), [
        'name' => 'Marchand Modifié',
        'email' => $merchant->email,
        'store_name' => 'Boutique Modifiée',
        'plan_id' => $plan->id,
    ])->assertRedirect();

    expect($store->fresh()->name)->toBe('Boutique Modifiée')
        ->and($merchant->fresh()->name)->toBe('Marchand Modifié');
});

test('super admins can delete a merchant account and its store', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $merchant = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $merchant->id]);

    $this->actingAs($admin)
        ->delete(route('super-admin.merchants.destroy', $store))
        ->assertRedirect(route('super-admin.merchants'))
        ->assertSessionHas('status', 'Compte marchand supprimé.');

    expect(User::find($merchant->id))->toBeNull()
        ->and(Store::find($store->id))->toBeNull();
});

test('super admins can search merchants by store name or owner email', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $merchant = User::factory()->create(['name' => 'Awa Ndiaye', 'email' => 'awa@example.com']);
    Store::factory()->create(['user_id' => $merchant->id, 'name' => 'Boutique Soleil']);
    $otherMerchant = User::factory()->create(['name' => 'Autre Marchand', 'email' => 'autre@example.com']);
    Store::factory()->create(['user_id' => $otherMerchant->id, 'name' => 'Boutique Nord']);

    $this->actingAs($admin)
        ->get(route('super-admin.merchants', ['search' => 'awa@example.com']))
        ->assertOk()
        ->assertSee('Boutique Soleil')
        ->assertDontSee('Boutique Nord');
});

test('super admins can create a plan with monthly annual and manual payment options', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    $this->actingAs($admin)
        ->post(route('super-admin.plans.store'), [
            'name' => 'Essentiel',
            'monthly_price' => 3000,
            'annual_price' => 30000,
            'product_limit' => 50,
            'payment_methods' => ['mobile_money', 'card'],
        ])
        ->assertRedirect();

    $plan = SubscriptionPlan::where('name', 'Essentiel')->firstOrFail();
    expect($plan->monthly_price)->toBe(3000)
        ->and($plan->annual_price)->toBe(30000)
        ->and($plan->product_limit)->toBe(50)
        ->and($plan->payment_methods)->toBe(['mobile_money', 'card']);
});

test('super admins can update an unused plan', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $plan = SubscriptionPlan::create(['name' => 'Ancien', 'monthly_price' => 1000, 'annual_price' => 10000, 'product_limit' => 10, 'payment_methods' => ['card']]);

    $this->actingAs($admin)
        ->put(route('super-admin.plans.update', $plan), [
            'name' => 'Essentiel',
            'monthly_price' => 3000,
            'annual_price' => 30000,
            'product_limit' => 50,
            'payment_methods' => ['mobile_money', 'card'],
        ])
        ->assertRedirect();

    expect($plan->fresh()->name)->toBe('Essentiel')
        ->and($plan->fresh()->annual_price)->toBe(30000);
});

test('assigned plans cannot be deleted', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $merchant = User::factory()->create();
    $plan = SubscriptionPlan::create(['name' => 'Utilisé', 'monthly_price' => 3000, 'annual_price' => 30000, 'product_limit' => 50, 'payment_methods' => ['mobile_money']]);
    Store::factory()->create(['user_id' => $merchant->id, 'subscription_plan_id' => $plan->id]);

    $this->actingAs($admin)
        ->delete(route('super-admin.plans.destroy', $plan))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($plan->fresh())->not->toBeNull();
});

test('super admins can delete an unused plan', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $plan = SubscriptionPlan::create(['name' => 'Temporaire', 'monthly_price' => 3000, 'annual_price' => 30000, 'product_limit' => 50, 'payment_methods' => ['mobile_money']]);

    $this->actingAs($admin)
        ->delete(route('super-admin.plans.destroy', $plan))
        ->assertRedirect()
        ->assertSessionHas('status', 'Forfait supprimé.');

    expect(SubscriptionPlan::find($plan->id))->toBeNull();
});

test('a non renewable free plan cannot be assigned twice to the same merchant', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $merchant = User::factory()->create();
    $plan = SubscriptionPlan::create([
        'name' => 'Gratuit',
        'monthly_price' => 0,
        'annual_price' => 0,
        'product_limit' => 20,
        'payment_methods' => ['mobile_money'],
        'is_renewable' => false,
    ]);
    $store = Store::factory()->create([
        'user_id' => $merchant->id,
        'subscription_plan_id' => $plan->id,
        'free_plan_used_at' => now()->subMonth(),
        'status' => 'suspended',
    ]);

    $this->actingAs($admin)
        ->put(route('super-admin.merchants.plan', $store), ['plan_id' => $plan->id])
        ->assertRedirect()
        ->assertSessionHas('error', 'Le forfait gratuit est utilisable une seule fois et ne peut pas être renouvelé.');

    expect($store->fresh()->status)->toBe('suspended');
});

test('an inactive subscription blocks merchant access', function () {
    $user = User::factory()->create();
    $plan = SubscriptionPlan::create(['name' => 'Suspendu', 'monthly_price' => 10000, 'is_active' => false]);
    Store::factory()->create(['user_id' => $user->id, 'subscription_plan_id' => $plan->id]);

    $this->actingAs($user)->get(route('accueil'))->assertForbidden();
});

test('expired subscriptions are suspended and notify the merchant', function () {
    Notification::fake();
    $merchant = User::factory()->create();
    $store = Store::factory()->create([
        'user_id' => $merchant->id,
        'subscription_ends_at' => now()->subMinute(),
        'status' => 'active',
    ]);

    $this->artisan('subscriptions:expire')->assertSuccessful();

    expect($store->fresh()->status)->toBe('suspended');
    Notification::assertSentTo($merchant, SubscriptionExpiredNotification::class);
});

test('an expired merchant is suspended immediately on the next protected request', function () {
    Notification::fake();
    $merchant = User::factory()->create();
    $store = Store::factory()->create([
        'user_id' => $merchant->id,
        'subscription_ends_at' => now()->subMinute(),
        'status' => 'active',
    ]);

    $this->actingAs($merchant)->get(route('accueil'))->assertForbidden();

    expect($store->fresh()->status)->toBe('suspended');
    Notification::assertSentTo($merchant, SubscriptionExpiredNotification::class);
});

test('subscription notifications are visible to the merchant account', function () {
    $merchant = User::factory()->create();
    $store = Store::factory()->create([
        'user_id' => $merchant->id,
        'subscription_ends_at' => now()->subMinute(),
        'status' => 'active',
    ]);

    $this->artisan('subscriptions:expire')->assertSuccessful();

    $this->actingAs($merchant)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Abonnement expiré')
        ->assertSee($store->name);
});

test('a subscription plan controls merchant module access', function () {
    $merchant = User::factory()->create();
    $plan = SubscriptionPlan::create([
        'name' => 'Stocks uniquement',
        'monthly_price' => 1000,
        'annual_price' => 10000,
        'payment_methods' => ['mobile_money'],
        'included_modules' => ['stock'],
        'is_active' => true,
    ]);
    Store::factory()->create([
        'user_id' => $merchant->id,
        'subscription_plan_id' => $plan->id,
        'enabled_modules' => ['stock', 'pos'],
    ]);

    $this->actingAs($merchant)->get(route('inventory.index'))->assertOk();
    $this->actingAs($merchant)->get(route('pos.index'))
        ->assertRedirect(route('accueil'))
        ->assertSessionHas('error');
});
