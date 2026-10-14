<?php

namespace App\Services\Capabilities;

use App\Enums\CapabilityLevel;
use App\Enums\VersionEventType;
use App\Models\Capability;
use App\Models\Version;
use App\Support\UniqueSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Writes to the Képességtár (07): every content change is a new version,
 * with an optional note on what changed and why (07 · 7.3).
 */
class CapabilityService
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string|null>  $preconditions  precondition id => note
     * @param  array<int, string|null>  $dependencies  capability id => note
     */
    public function create(array $attributes, array $preconditions = [], array $dependencies = [], ?string $note = null): Capability
    {
        return DB::transaction(function () use ($attributes, $preconditions, $dependencies, $note): Capability {
            $capability = new Capability([
                ...$attributes,
                'slug' => filled($attributes['slug'] ?? null) ? $attributes['slug'] : $this->slugFor($attributes['name']),
            ]);
            $capability->save();

            $this->syncPreconditions($capability, $preconditions);
            $this->syncDependencies($capability, $dependencies);
            $capability->recordVersion($note);

            return $capability;
        });
    }

    /**
     * A save from the form. Returns the new version, or null when nothing changed.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $preconditionIds
     * @param  list<int>  $dependencyIds
     */
    public function update(Capability $capability, array $attributes, array $preconditionIds, array $dependencyIds, ?string $note = null): ?Version
    {
        return DB::transaction(function () use ($capability, $attributes, $preconditionIds, $dependencyIds, $note): ?Version {
            $before = $this->markable($capability);
            $capability->fill($attributes);
            $after = $this->markable($capability);

            // A field the admin changes loses its "[JAVASLAT]" mark and its empty state from the Sheet.
            $touched = array_keys(array_filter($after, fn (mixed $value, string $field): bool => $value != $before[$field], ARRAY_FILTER_USE_BOTH));
            $currentPreconditions = $capability->preconditions()->get();

            if (collect($currentPreconditions->modelKeys())->sort()->values()->all() != collect($preconditionIds)->sort()->values()->all()) {
                $touched[] = 'preconditions';
            }

            if ($touched !== []) {
                $capability->proposed_fields = array_values(array_diff($capability->proposed_fields, $touched));
                $capability->field_states = array_diff_key($capability->field_states, array_flip($touched));
            }

            $capability->save();

            // Notes of the links already there stay, new links come without one.
            $this->syncPreconditions($capability, $this->keepNotes($currentPreconditions, $preconditionIds));
            $this->syncDependencies($capability, $this->keepNotes($capability->dependencies()->get(), array_values(array_diff($dependencyIds, [$capability->id]))));

            return $capability->recordVersion($note);
        });
    }

    /**
     * @param  array<int, string|null>  $preconditions  id => note, in order
     */
    public function syncPreconditions(Capability $capability, array $preconditions): void
    {
        $capability->preconditions()->sync($this->pivotRows($preconditions));
    }

    /**
     * @param  array<int, string|null>  $dependencies  id => note, in order
     */
    public function syncDependencies(Capability $capability, array $dependencies): void
    {
        unset($dependencies[$capability->id]);

        $capability->dependencies()->sync($this->pivotRows($dependencies));
    }

    /**
     * The archived capability is not selectable in a new place, and stays
     * where it is used (07 · 7.5). No new version, an event.
     */
    public function archive(Capability $capability): void
    {
        $capability->forceFill(['archived_at' => now()])->save();
        $capability->recordVersionEvent(VersionEventType::Archived);
    }

    public function restore(Capability $capability): void
    {
        $capability->forceFill(['archived_at' => null])->save();
        $capability->recordVersionEvent(VersionEventType::Restored);
    }

    /**
     * Names are unique regardless of case.
     */
    public function findByName(string $name, ?int $ignoreCapabilityId = null): ?Capability
    {
        return Capability::query()
            ->whereRaw('lower(name) = ?', [mb_strtolower(trim($name))])
            ->when($ignoreCapabilityId, fn ($query) => $query->whereKeyNot($ignoreCapabilityId))
            ->first();
    }

    public function slugFor(string $name, ?int $ignoreCapabilityId = null): string
    {
        return UniqueSlug::make($name, 'kepesseg', fn (string $slug): bool => ! Capability::query()
            ->where('slug', $slug)
            ->when($ignoreCapabilityId, fn ($query) => $query->whereKeyNot($ignoreCapabilityId))
            ->exists());
    }

    /**
     * The form fields by the keys of the marks and the version snapshot.
     *
     * @return array<string, mixed>
     */
    private function markable(Capability $capability): array
    {
        return [
            'name' => $capability->name,
            'name_en' => $capability->name_en,
            'group' => $capability->capability_group_id,
            'level' => $capability->level?->value,
            'status' => $capability->status?->value,
            'nature' => $capability->nature?->value,
            'tempting' => $capability->tempting,
            'definition' => $capability->definition,
            'statement' => $capability->statement,
            'non_examples' => $capability->non_examples,
            'overlaps' => $capability->overlaps,
            'keywords' => $capability->keywords,
            ...collect(CapabilityLevel::cases())->mapWithKeys(fn (CapabilityLevel $level) => ['appearance_'.$level->value => $capability->appearances[$level->value] ?? null])->all(),
            ...collect(Capability::STAGES)->mapWithKeys(fn (int $stage) => ['stage_'.$stage => $capability->stages[$stage] ?? null])->all(),
        ];
    }

    /**
     * @param  iterable<int, Model>  $linked  with the pivot
     * @param  list<int>  $ids
     * @return array<int, string|null>
     */
    private function keepNotes(iterable $linked, array $ids): array
    {
        $notes = collect($linked)->mapWithKeys(fn ($model) => [$model->getKey() => $model->pivot->note])->all();

        return collect($ids)->mapWithKeys(fn (int $id) => [$id => $notes[$id] ?? null])->all();
    }

    /**
     * @param  array<int, string|null>  $links
     * @return array<int, array{note: string|null, position: int}>
     */
    private function pivotRows(array $links): array
    {
        $rows = [];
        $position = 0;

        foreach ($links as $id => $note) {
            $rows[$id] = ['note' => filled($note) ? $note : null, 'position' => $position++];
        }

        return $rows;
    }
}
