<?php

namespace Database\Factories;

use App\Enums\TokenPurpose;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Stores the hash of a random token; tests that need the plain token issue it
 * through App\Services\Auth\TokenService instead.
 *
 * @extends Factory<UserToken>
 */
class UserTokenFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'purpose' => TokenPurpose::LoginCode,
            'token_hash' => hash('sha256', Str::random(64)),
            'code_hash' => null,
            'expires_at' => now()->addMinutes(15),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => ['expires_at' => now()->subMinute()]);
    }
}
