<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
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
            'name' => fake()->words(3, true),
            'sku' => fake()->unique()->bothify('SKU-####'),
            'category' => fake()->randomElement(['Alimentaire', 'Mode', 'Maison', 'Électronique']),
            'stock_quantity' => fake()->numberBetween(0, 100),
            'minimum_stock' => fake()->numberBetween(2, 15),
            'purchase_price' => fake()->numberBetween(300, 150000),
            'unit_price' => fake()->numberBetween(500, 250000),
            'barcode' => fake()->unique()->ean13(),
            'is_active' => true,
        ];
    }
}
