<?php

namespace Database\Factories;

use App\Enums\AiCallStatus;
use App\Models\AiCall;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiCall>
 */
class AiCallFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task' => 'mirror_detection',
            'provider' => 'anthropic',
            'model' => 'claude-sonnet-5-5',
            'model_version' => 'claude-sonnet-5-5-20260901',
            'prompt' => 'ertektukor-detektalas',
            'prompt_version' => 1,
            'status' => AiCallStatus::Succeeded,
            'input_tokens' => 1000,
            'output_tokens' => 200,
            'cost_usd' => 0.006,
            'duration_ms' => 1500,
            'created_at' => now(),
        ];
    }

    public function failed(string $error = 'Timeout'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AiCallStatus::Failed,
            'error' => $error,
            'output_tokens' => 0,
        ]);
    }
}
