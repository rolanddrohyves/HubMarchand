<?php

namespace Database\Factories;

use App\Models\SalesOrder;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SalesOrder> */
class SalesOrderFactory extends Factory
{
    protected $model = SalesOrder::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'reference' => 'CMD-'.fake()->unique()->numerify('######'),
            'customer_name' => fake()->name(),
            'status' => 'paid',
            'total_amount' => 100000,
            'cost_amount' => 60000,
            'ordered_at' => now(),
        ];
    }
}
