<?php

namespace Database\Factories;

use App\Models\CapabilityGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CapabilityGroup>
 */
class CapabilityGroupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'name_en' => null,
            'description' => null,
        ];
    }
}
