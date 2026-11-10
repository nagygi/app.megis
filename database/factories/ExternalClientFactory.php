<?php

namespace Database\Factories;

use App\Models\ExternalClient;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalClient>
 */
class ExternalClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory()->withExternalClients(),
            'name' => fake()->unique()->company(),
            'description' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['deactivated_at' => now()]);
    }
}
