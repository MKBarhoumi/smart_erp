<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('PRD-####')),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'unit_price' => fake()->randomFloat(3, 5, 5000),
            'tva_rate' => fake()->randomElement(['0', '7', '13', '19']),
            'unit_of_measure' => fake()->randomElement(['U', 'KG', 'L', 'M', 'H']),
            'is_subject_to_timbre' => fake()->boolean(30),
            'track_inventory' => fake()->boolean(50),
            'current_stock_quantity' => fake()->randomFloat(3, 0, 1000),
            'minimum_stock_quantity' => fake()->randomFloat(3, 0, 50),
        ];
    }

    public function withInventory(): static
    {
        return $this->state(fn () => [
            'track_inventory' => true,
            'current_stock_quantity' => fake()->randomFloat(3, 100, 1000),
            'minimum_stock_quantity' => '10.000',
        ]);
    }
}
