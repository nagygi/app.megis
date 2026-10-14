<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationValueSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationValueSet>
 */
class OrganizationValueSetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
        ];
    }
}
