<?php

namespace Database\Factories;

use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'type' => OrganizationType::Organization,
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999999),
            'description' => null,
            'product_id' => Product::factory(),
            'headcount_limit' => null,
        ];
    }

    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => OrganizationType::Individual,
            'headcount_limit' => null,
        ]);
    }

    public function withHeadcountLimit(int $limit): static
    {
        return $this->state(fn (array $attributes) => ['headcount_limit' => $limit]);
    }

    public function deactivated(): static
    {
        return $this->state(fn (array $attributes) => ['deactivated_at' => now()]);
    }
}
