<?php

namespace Database\Factories;

use App\Enums\MirrorStatus;
use App\Models\Material;
use App\Models\MirrorSession;
use App\Models\Organization;
use App\Models\User;
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

    /**
     * A mirror from the own menu (06 · 6.2): the user's, in the organization, outside a diagnosis.
     */
    public function ownMenu(User $user, Organization $organization): static
    {
        return $this->state(fn (array $attributes) => [
            'material_id' => Material::factory(),
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'claimed_at' => now(),
            'expires_at' => null,
        ]);
    }
}
