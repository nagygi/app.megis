<?php

namespace Database\Factories;

use App\Enums\CapabilityLevel;
use App\Enums\PatternOrigin;
use App\Enums\PatternProduct;
use App\Enums\PatternSubtype;
use App\Enums\PatternType;
use App\Enums\PatternValidity;
use App\Models\Pattern;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pattern>
 */
class PatternFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'M-'.fake()->unique()->numberBetween(500, 999),
            'type' => PatternType::Capability,
            'subtype' => PatternSubtype::Presence,
            'name' => ucfirst(fake()->unique()->words(3, true)),
            'level' => CapabilityLevel::Team,
            'products' => [PatternProduct::Full->value],
            'validity' => PatternValidity::Valid,
            'origin' => PatternOrigin::OwnCase,
            'content' => ['behavior' => fake()->sentence(), 'situation' => fake()->sentence()],
        ];
    }

    public function detector(): static
    {
        return $this->state(fn (array $attributes) => ['type' => PatternType::DetectorError, 'subtype' => null, 'level' => null]);
    }
}
