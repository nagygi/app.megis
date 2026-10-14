<?php

namespace Database\Factories;

use App\Models\ValueCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ValueCategory>
 */
class ValueCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'description' => null,
        ];
    }
}
