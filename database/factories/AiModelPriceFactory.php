<?php

namespace Database\Factories;

use App\Models\AiModelPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiModelPrice>
 */
class AiModelPriceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'model' => 'claude-sonnet-5-5',
            'provider' => 'anthropic',
            'input' => 2.0,
            'output' => 10.0,
            'cache_read' => 0.1,
            'cache_write' => 2.5,
            'audio_minute' => null,
            'source_key' => 'claude-sonnet-5-5',
            'synced_at' => now(),
        ];
    }
}
