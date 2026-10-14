<?php

namespace Database\Factories;

use App\Models\CalendarConnection;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarConnection>
 */
class CalendarConnectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'organization_id' => Organization::factory(),
            'provider' => CalendarConnection::ICS_URL,
            'credentials' => ['url' => 'https://calendar.example.com/secret.ics'],
            'status' => CalendarConnection::ACTIVE,
            'connected_at' => now(),
            'metadata_expires_at' => now()->addDays(90),
        ];
    }
}
