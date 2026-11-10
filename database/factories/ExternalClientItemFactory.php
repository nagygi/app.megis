<?php

namespace Database\Factories;

use App\Enums\ExternalClientItemKind;
use App\Models\ExternalClient;
use App\Models\ExternalClientItem;
use App\Models\Process;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExternalClientItem>
 */
class ExternalClientItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'external_client_id' => ExternalClient::factory(),
            'kind' => ExternalClientItemKind::Value,
            'value_key' => (string) Str::uuid(),
            'process_id' => null,
            'question' => null,
            'position' => 0,
        ];
    }

    public function forValue(string $valueKey): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => ExternalClientItemKind::Value,
            'value_key' => $valueKey,
            'process_id' => null,
        ]);
    }

    public function forProcess(Process $process): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => ExternalClientItemKind::Process,
            'value_key' => null,
            'process_id' => $process->id,
            'question' => fake()->sentence().'?',
        ]);
    }
}
