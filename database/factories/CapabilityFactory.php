<?php

namespace Database\Factories;

use App\Enums\CapabilityLevel;
use App\Enums\CapabilityNature;
use App\Enums\CapabilityStatus;
use App\Models\Capability;
use App\Models\CapabilityGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Capability>
 */
class CapabilityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(3, true));

        return [
            'code' => 'RC-'.fake()->unique()->numberBetween(100, 999),
            'name' => $name,
            'name_en' => null,
            'slug' => Str::slug($name),
            'capability_group_id' => CapabilityGroup::query()->inRandomOrder()->value('id') ?? CapabilityGroup::factory(),
            'level' => CapabilityLevel::Team,
            'status' => CapabilityStatus::Candidate,
            'nature' => CapabilityNature::Capability,
            'tempting' => false,
            'definition' => fake()->sentence(),
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => ['archived_at' => now()]);
    }
}
