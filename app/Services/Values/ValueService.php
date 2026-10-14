<?php

namespace App\Services\Values;

use App\Enums\ValueOrigin;
use App\Enums\VersionEventType;
use App\Models\Organization;
use App\Models\User;
use App\Models\Value;
use App\Models\Version;
use App\Support\UniqueSlug;
use Illuminate\Support\Facades\DB;

/**
 * Writes to the Értéktár (03): every content change is a new version, and
 * values from outside the admin form wait for review (03 · 3.7).
 */
class ValueService
{
    /**
     * @param  array<string, mixed>  $attributes  name, optional slug and category, the lists
     */
    public function create(array $attributes, ValueOrigin $origin = ValueOrigin::Admin, ?Organization $organization = null): Value
    {
        return DB::transaction(function () use ($attributes, $origin, $organization): Value {
            $value = new Value([
                ...$attributes,
                'slug' => filled($attributes['slug'] ?? null) ? $attributes['slug'] : $this->slugFor($attributes['name']),
                'origin' => $origin,
                'origin_organization_id' => $origin->needsReview() ? $organization?->id : null,
                'reviewed_at' => $origin->needsReview() ? null : now(),
            ]);
            $value->save();
            $value->recordVersion();

            return $value;
        });
    }

    /**
     * A value found where it was not in the Értéktár yet: a diagnosis (04 · 4.4),
     * the measuring engine (06) or an organization's values (08 · 8.1).
     *
     * @param  array<string, mixed>  $attributes  the fields known so far
     */
    public function createElsewhere(string $name, ValueOrigin $origin, ?Organization $organization, array $attributes = []): Value
    {
        return $this->create(['name' => trim($name), ...$attributes], $origin, $organization);
    }

    /**
     * Returns the new version, or null when nothing changed.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Value $value, array $attributes): ?Version
    {
        return DB::transaction(function () use ($value, $attributes): ?Version {
            $value->fill($attributes)->save();

            return $value->recordVersion();
        });
    }

    public function archive(Value $value): void
    {
        $value->forceFill(['archived_at' => now()])->save();
        $value->recordVersionEvent(VersionEventType::Archived);
    }

    public function restore(Value $value): void
    {
        $value->forceFill(['archived_at' => null])->save();
        $value->recordVersionEvent(VersionEventType::Restored);
    }

    /**
     * After review the value is selectable everywhere (03 · 3.7).
     */
    public function markReviewed(Value $value): void
    {
        $value->forceFill(['reviewed_at' => now()])->save();
        $value->recordVersionEvent(VersionEventType::Reviewed);
    }

    /**
     * A user's new own value: "Felhasználó által felvett", only theirs until
     * an admin reviews it (24, question 3).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createForUser(string $name, User $user, array $attributes = []): Value
    {
        return $this->create(['name' => trim($name), ...$attributes, 'origin_user_id' => $user->id], ValueOrigin::User);
    }

    /**
     * Names are unique regardless of case: "bátorság" is the same value as "Bátorság".
     */
    public function findByName(string $name, ?int $ignoreValueId = null): ?Value
    {
        return Value::query()
            ->whereRaw('lower(name) = ?', [mb_strtolower(trim($name))])
            ->when($ignoreValueId, fn ($query) => $query->whereKeyNot($ignoreValueId))
            ->first();
    }

    public function slugFor(string $name, ?int $ignoreValueId = null): string
    {
        return UniqueSlug::make($name, 'ertek', fn (string $slug): bool => ! Value::query()
            ->where('slug', $slug)
            ->when($ignoreValueId, fn ($query) => $query->whereKeyNot($ignoreValueId))
            ->exists());
    }
}
