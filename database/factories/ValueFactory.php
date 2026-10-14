<?php

namespace Database\Factories;

use App\Enums\ValueOrigin;
use App\Models\Organization;
use App\Models\Value;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Value>
 */
class ValueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'meanings' => [fake()->sentence()],
            'non_meanings' => [],
            'high_examples' => [fake()->sentence()],
            'low_examples' => [fake()->sentence()],
            'examples' => [],
            'keywords' => [],
            'origin' => ValueOrigin::Admin,
            'reviewed_at' => now(),
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => ['archived_at' => now()]);
    }

    /**
     * Created outside the admin form, in the given organization, not reviewed yet (03 · 3.7).
     */
    public function from(ValueOrigin $origin, ?Organization $organization = null): static
    {
        return $this->state(fn (array $attributes) => [
            'origin' => $origin,
            'origin_organization_id' => $organization?->id,
            'reviewed_at' => null,
        ]);
    }

    public function withoutEndpoints(): static
    {
        return $this->state(fn (array $attributes) => ['high_examples' => [], 'low_examples' => []]);
    }
}
