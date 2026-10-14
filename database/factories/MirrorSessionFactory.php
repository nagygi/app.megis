<?php

namespace Database\Factories;

use App\Enums\MirrorStatus;
use App\Models\Material;
use App\Models\MirrorSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MirrorSession>
 */
class MirrorSessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'material_id' => Material::factory()->state(['context' => 'public_mirror']),
            'status' => MirrorStatus::Ready,
            'expires_at' => now()->addHours(24),
        ];
    }
}
