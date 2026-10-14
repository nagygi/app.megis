<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * An activated customer with a password.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'activated_at' => now(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Admin]);
    }

    public function superadmin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Superadmin]);
    }

    /**
     * Invited but not registered yet: no password, not activated.
     */
    public function invited(): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => null,
            'activated_at' => null,
        ]);
    }

    /**
     * Logs in with e-mailed codes only.
     */
    public function withoutPassword(): static
    {
        return $this->state(fn (array $attributes) => ['password' => null]);
    }

    public function deactivated(): static
    {
        return $this->state(fn (array $attributes) => ['deactivated_at' => now()]);
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'locked_at' => now(),
            'failed_login_attempts' => 10,
        ]);
    }
}
