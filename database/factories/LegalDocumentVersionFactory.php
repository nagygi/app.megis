<?php

namespace Database\Factories;

use App\Enums\LegalDocumentType;
use App\Models\LegalDocumentVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LegalDocumentVersion>
 */
class LegalDocumentVersionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => LegalDocumentType::Terms,
            'file_path' => 'legal/'.fake()->uuid().'.pdf',
            'original_name' => 'aszf.pdf',
            'effective_from' => today()->subMonth(),
            'requires_reacceptance' => false,
        ];
    }

    public function ofType(LegalDocumentType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
            'original_name' => $type->value.'.pdf',
        ]);
    }

    public function requiringReacceptance(): static
    {
        return $this->state(fn (array $attributes) => ['requires_reacceptance' => true]);
    }
}
