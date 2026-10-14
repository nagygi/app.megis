<?php

namespace Database\Factories;

use App\Enums\DiagnosisRowKind;
use App\Enums\ExperienceState;
use App\Enums\ScoreState;
use App\Enums\TreeElement;
use App\Models\Diagnosis;
use App\Models\DiagnosisRow;
use App\Models\Value;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiagnosisRow>
 */
class DiagnosisRowFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'diagnosis_id' => Diagnosis::factory(),
            'kind' => DiagnosisRowKind::Value,
            'value_id' => Value::factory(),
            'assigned_on' => today(),
        ];
    }

    public function scored(float $score, ?float $experience = null): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $score,
            'score_state' => ScoreState::Scored,
            'experience' => $experience,
            'experience_state' => $experience === null ? ExperienceState::Missing : ExperienceState::Answered,
        ]);
    }

    public function noTrace(): static
    {
        return $this->state(fn (array $attributes) => ['score' => null, 'score_state' => ScoreState::NoTrace]);
    }

    public function dontKnow(): static
    {
        return $this->state(fn (array $attributes) => ['experience' => null, 'experience_state' => ExperienceState::DontKnow]);
    }

    public function tree(TreeElement $element): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => DiagnosisRowKind::TreeElement,
            'value_id' => null,
            'tree_element' => $element,
        ]);
    }
}
