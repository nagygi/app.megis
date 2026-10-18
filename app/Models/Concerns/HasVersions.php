<?php

namespace App\Models\Concerns;

use App\Enums\VersionEventType;
use App\Models\Version;
use App\Models\VersionEvent;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;

/**
 * Content versions (03 · 3.3): a save that changes the content records a new
 * version with the full content, a save without change records none.
 */
trait HasVersions
{
    /**
     * The full content a version keeps, lists in their order.
     *
     * @return array<string, mixed>
     */
    abstract public function versionSnapshot(): array;

    /**
     * @return MorphMany<Version, $this>
     */
    public function versions(): MorphMany
    {
        return $this->morphMany(Version::class, 'versionable');
    }

    /**
     * @return MorphOne<Version, $this>
     */
    public function latestVersion(): MorphOne
    {
        return $this->morphOne(Version::class, 'versionable')->latestOfMany('number');
    }

    /**
     * @return MorphMany<VersionEvent, $this>
     */
    public function versionEvents(): MorphMany
    {
        return $this->morphMany(VersionEvent::class, 'versionable');
    }

    /**
     * Records a new version when the content differs from the latest one,
     * with an optional note on what changed and why (07 · 7.3).
     */
    public function recordVersion(?string $note = null): ?Version
    {
        return DB::transaction(function () use ($note): ?Version {
            // Serialises concurrent saves of the same entity, so numbers do not collide.
            static::query()->whereKey($this->getKey())->lockForUpdate()->first();

            $content = $this->versionSnapshot();
            $snapshot = static::comparable($content);
            $latest = $this->versions()->orderByDesc('number')->first();
            $previous = $latest === null ? null : static::comparable($latest->snapshot);

            if ($previous === $snapshot) {
                return null;
            }

            $version = $this->versions()->create([
                'number' => ($latest->number ?? 0) + 1,
                'snapshot' => $content,
                'changed_fields' => static::changedFields($previous, $content),
                'note' => filled($note) ? $note : null,
                'created_by' => auth()->id(),
            ]);

            $this->unsetRelation('latestVersion');

            return $version;
        });
    }

    public function recordVersionEvent(VersionEventType $type): VersionEvent
    {
        return $this->versionEvents()->create([
            'type' => $type,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Keys in a stable order (jsonb reorders them), lists untouched.
     *
     * @param  array<array-key, mixed>  $snapshot
     * @return array<array-key, mixed>
     */
    protected static function normalizeSnapshot(array $snapshot): array
    {
        if (! array_is_list($snapshot)) {
            ksort($snapshot);
        }

        return array_map(
            fn (mixed $value): mixed => is_array($value) ? static::normalizeSnapshot($value) : $value,
            $snapshot,
        );
    }

    /**
     * Normalized, without the empty fields: a field added to the entity later
     * is no change while it stays empty.
     *
     * @param  array<array-key, mixed>  $snapshot
     * @return array<array-key, mixed>
     */
    protected static function comparable(array $snapshot): array
    {
        return static::normalizeSnapshot(array_filter($snapshot, fn (mixed $value): bool => $value !== null && $value !== []));
    }

    /**
     * Changed top-level fields, in the order the entity lists them.
     *
     * @param  array<string, mixed>|null  $previous  comparable
     * @param  array<string, mixed>  $current
     * @return list<string>
     */
    protected static function changedFields(?array $previous, array $current): array
    {
        if ($previous === null) {
            return array_keys($current);
        }

        $order = array_keys($current);
        $current = static::comparable($current);
        $keys = array_unique([...$order, ...array_keys($previous)]);

        return array_values(array_filter(
            $keys,
            fn (string $key): bool => ($previous[$key] ?? null) !== ($current[$key] ?? null),
        ));
    }
}
