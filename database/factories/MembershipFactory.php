<?php

namespace Database\Factories;

use App\Enums\MembershipStatus;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * An active employee by default.
 *
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    protected $model = Membership::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'status' => MembershipStatus::Active,
            'position' => null,
            'is_organization_leader' => false,
            'is_employee' => true,
            'is_partner' => false,
        ];
    }

    public function organizationLeader(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_organization_leader' => true,
            'is_employee' => false,
        ]);
    }

    public function partner(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_partner' => true,
            'is_employee' => false,
        ]);
    }

    /**
     * Recorded by the admin, no e-mail sent yet ("Felvéve").
     */
    public function added(): static
    {
        return $this->state(fn (array $attributes) => ['status' => MembershipStatus::Added]);
    }

    public function invited(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MembershipStatus::Invited,
            'invited_at' => now(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MembershipStatus::Inactive,
            'deactivated_at' => now(),
        ]);
    }
}
