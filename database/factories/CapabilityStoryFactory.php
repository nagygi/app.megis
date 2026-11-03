<?php

namespace Database\Factories;

use App\Models\CapabilityStory;
use App\Models\MainDiagnosis;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CapabilityStory>
 */
class CapabilityStoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'main_diagnosis_id' => MainDiagnosis::factory(),
            'user_id' => User::factory(),
            'question' => __('capabilities.step.prompt'),
            'answer' => null,
        ];
    }

    public function answered(?string $answer = null): static
    {
        return $this->state(fn () => [
            'answer' => $answer ?? 'Kedden a Vezetői körön csak a végén derült ki, hogy a pénteki átadás csúszik, pedig hétfőn már tudtuk.',
        ]);
    }

    public function submitted(): static
    {
        return $this->answered()->state(fn () => ['submitted_at' => now()]);
    }
}
