<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'supplier_id' => Supplier::factory(),
            'user_id' => User::factory(),
            'reference' => 'BC-'.fake()->unique()->numerify('########'),
            'status' => 'ordered',
            'expected_at' => now()->addDays(7)->toDateString(),
            'received_at' => null,
            'total_amount' => 100000,
            'notes' => null,
        ];
    }
}
