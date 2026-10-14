<?php

namespace Database\Factories;

use App\Enums\ParticipantRole;
use App\Models\MainDiagnosis;
use App\Models\MainDiagnosisParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MainDiagnosisParticipant>
 */
class MainDiagnosisParticipantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'main_diagnosis_id' => MainDiagnosis::factory(),
            'user_id' => User::factory(),
            'role' => ParticipantRole::Peer,
        ];
    }
}
