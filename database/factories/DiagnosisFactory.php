<?php

namespace Database\Factories;

use App\Enums\DiagnosisCreator;
use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisStatus;
use App\Models\Diagnosis;
use App\Models\DiagnosisType;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Diagnosis>
 */
class DiagnosisFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->words(3, true)),
            'organization_id' => Organization::factory(),
            'level' => DiagnosisLevel::Organization,
            'diagnosis_type_id' => fn () => DiagnosisType::byKey('workshop')->id,
            'diagnosed_on' => today(),
            'creator_kind' => DiagnosisCreator::Admin,
            'status' => DiagnosisStatus::Draft,
        ];
    }

    public function forTeam(Team $team): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $team->organization_id,
            'level' => DiagnosisLevel::Team,
            'team_id' => $team->id,
        ]);
    }

    public function forUser(User $user, ?Organization $organization = null): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $organization?->id ?? $attributes['organization_id'],
            'level' => DiagnosisLevel::User,
            'user_id' => $user->id,
        ]);
    }

    public function ofType(string $key): static
    {
        return $this->state(fn (array $attributes) => ['diagnosis_type_id' => DiagnosisType::byKey($key)->id]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => ['status' => DiagnosisStatus::Published]);
    }
}
