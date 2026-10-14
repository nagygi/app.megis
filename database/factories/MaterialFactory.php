<?php

namespace Database\Factories;

use App\Enums\MaterialContext;
use App\Enums\MaterialSource;
use App\Enums\MaterialStatus;
use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'context' => MaterialContext::Mirror,
            'source' => MaterialSource::Text,
            'text' => 'Múlt héten szóltam a csúszásról, mielőtt kérdezték volna.',
            'status' => MaterialStatus::Pending,
        ];
    }

    public function recording(string $path = 'recordings/test.webm', int $seconds = 75): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => MaterialSource::Recording,
            'audio_path' => $path,
            'audio_mime' => 'audio/webm',
            'audio_seconds' => $seconds,
            'text' => null,
        ]);
    }

    /**
     * @param  list<array{speaker: string, text: string}>  $segments
     */
    public function transcribed(array $segments): static
    {
        return $this->state(fn (array $attributes) => [
            'segments' => $segments,
            'text' => implode("\n", array_column($segments, 'text')),
        ]);
    }
}
