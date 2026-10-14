<?php

namespace Database\Factories;

use App\Enums\InboxItemType;
use App\Models\InboxItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InboxItem>
 */
class InboxItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => InboxItemType::ProcessingError,
            'summary' => fake()->sentence(),
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'closed_at' => now(),
            'close_reason' => InboxItem::CLOSED_MANUALLY,
        ]);
    }
}
