<?php

namespace Database\Factories;

use App\Models\SupportQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportQuestion>
 */
class SupportQuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'text' => rtrim(fake()->sentence(), '.').'?',
            'is_active' => true,
            'position' => 100,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
