<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
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
            'title' => fake()->words(3, true),
            'price' => fake()->randomFloat(2, 10, 1000),
            'list_price' => fake()->randomFloat(2, 10, 1200),
            'rating' => fake()->randomFloat(2, 1, 5),
            'rating_count' => fake()->numberBetween(0, 10000),
            'vendor_name' => fake()->company(),
            'image_url' => fake()->imageUrl(640, 480, 'products', true),
        ];
    }
}
