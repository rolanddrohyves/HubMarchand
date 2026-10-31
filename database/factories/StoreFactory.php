<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company(),
            'email' => fake()->companyEmail(),
            'description' => fake()->sentence(),
            'country' => 'SN',
            'currency' => 'XOF',
            'timezone' => 'Africa/Dakar',
            'date_format' => 'd/m/Y',
        ];
    }
}
