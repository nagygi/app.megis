<?php

namespace Database\Factories;

use App\Enums\MainDiagnosisStage;
use App\Enums\MainDiagnosisSubject;
use App\Models\Diagnosis;
use App\Models\MainDiagnosis;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Bare rows for lists; tests of the flow start diagnoses through the service.
 *
 * @extends Factory<MainDiagnosis>
 */
class MainDiagnosisFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'diagnosis_id' => Diagnosis::factory()->ofType('main'),
            'subject' => MainDiagnosisSubject::Team,
            'product_key' => Product::FULL,
            'product_name' => 'Teljes',
            'leader_id' => User::factory(),
            'stage' => MainDiagnosisStage::Draft,
            'starts_on' => today()->addWeek(),
            'publishes_on' => today()->addWeeks(7),
            'show_peer_quotes' => true,
        ];
    }
}
