<?php

namespace Database\Factories;

use App\Enums\ExternalClientItemKind;
use App\Models\ExternalClient;
use App\Models\ExternalClientAnswer;
use App\Models\MainDiagnosis;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExternalClientAnswer>
 */
class ExternalClientAnswerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'main_diagnosis_id' => MainDiagnosis::factory(),
            'external_client_id' => ExternalClient::factory(),
            'user_id' => User::factory(),
            'external_client_item_id' => null,
            'kind' => ExternalClientItemKind::Value,
            'value_key' => (string) Str::uuid(),
            'process_id' => null,
            'question' => fake()->sentence().'?',
            'answer' => fake()->paragraph(),
            'material_id' => null,
            'submitted_at' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => ['submitted_at' => now()]);
    }
}
