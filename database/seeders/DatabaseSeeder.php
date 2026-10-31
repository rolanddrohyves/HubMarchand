<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Store;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_super_admin' => false,
                'two_factor_enabled' => false,
            ],
        );

        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@hubmarchand.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_super_admin' => true,
                'two_factor_enabled' => true,
            ],
        );

        // Le compte plateforme ne doit jamais apparaître comme un marchand.
        $superAdmin->store()->delete();

        $freePlan = SubscriptionPlan::updateOrCreate(
            ['name' => 'Gratuit'],
            [
                'monthly_price' => 0,
                'annual_price' => 0,
                'product_limit' => 20,
                'ai_included' => false,
                'payment_methods' => ['mobile_money', 'card'],
                'included_modules' => ['stock', 'pos', 'analytics', 'shop', 'crm', 'delivery', 'invoice'],
                'is_renewable' => false,
                'is_active' => true,
            ],
        );

        SubscriptionPlan::updateOrCreate(
            ['name' => 'Essentiel'],
            [
                'monthly_price' => 3000,
                'annual_price' => 30000,
                'product_limit' => 50,
                'ai_included' => false,
                'payment_methods' => ['mobile_money', 'card'],
                'included_modules' => ['stock', 'pos', 'analytics', 'shop', 'crm', 'delivery', 'invoice'],
                'is_renewable' => true,
                'is_active' => true,
            ],
        );

        $store = Store::updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => 'Ma boutique',
                'email' => $user->email,
                'description' => 'Une boutique de démonstration.',
                'country' => 'SN',
                'currency' => 'XOF',
                'timezone' => 'Africa/Dakar',
                'date_format' => 'd/m/Y',
                'enabled_modules' => ['stock', 'pos', 'analytics', 'shop', 'crm', 'delivery', 'invoice', 'copilot'],
                'web_payment_methods' => ['mobile_money'],
                'cash_on_delivery' => true,
                'subscription_plan_id' => $freePlan->id,
                'free_plan_used_at' => now(),
            ],
        );

        if ($store->products()->doesntExist()) {
            Product::factory()->count(8)->create([
                'store_id' => $store->id,
                'is_published' => true,
            ]);
        }
    }
}
