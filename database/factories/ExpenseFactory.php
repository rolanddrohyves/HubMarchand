<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Expense> */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'label' => fake()->sentence(3),
            'amount' => fake()->numberBetween(1000, 100000),
            'category' => fake()->randomElement(['Transport', 'Loyer', 'Marketing', 'Fournitures']),
            'spent_at' => now()->toDateString(),
        ];
    }
}
