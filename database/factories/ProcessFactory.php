<?php

namespace Database\Factories;

use App\Enums\ProcessConfidence;
use App\Enums\ProcessKind;
use App\Enums\ProcessRhythm;
use App\Enums\ProcessSource;
use App\Models\Organization;
use App\Models\Process;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Process>
 */
class ProcessFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => ucfirst(fake()->words(2, true)),
            'rhythm' => ProcessRhythm::Weekly,
            'kind' => ProcessKind::Routine,
            'source' => ProcessSource::Manual,
            'confidence' => ProcessConfidence::Manual,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => ['accepted_at' => now()]);
    }

    public function fromCalendar(ProcessConfidence $confidence = ProcessConfidence::High): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => ProcessSource::Calendar,
            'confidence' => $confidence,
            'rhythm_label' => 'keddenként 9:00',
            'headcount' => 4,
            'occurrence_label' => '8/12 hét',
        ]);
    }
}
