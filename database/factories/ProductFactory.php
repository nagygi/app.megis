<?php

namespace Database\Factories;

use App\Models\Product;
use Database\Seeders\ProductSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
        ];
    }

    /**
     * One of the starting products (02 · 2.1, 17 · 17.3).
     */
    public function starting(string $key): static
    {
        $names = [
            Product::SNAPSHOT_EXTERNAL => ProductSeeder::SNAPSHOT_EXTERNAL,
            Product::SNAPSHOT_INTERNAL => ProductSeeder::SNAPSHOT_INTERNAL,
            Product::FULL => ProductSeeder::FULL,
        ];

        return $this->state(fn (array $attributes) => ['key' => $key, 'name' => $names[$key] ?? $key]);
    }
}
