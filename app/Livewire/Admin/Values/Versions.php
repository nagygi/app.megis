<?php

namespace App\Livewire\Admin\Values;

use App\Livewire\Admin\Values\Concerns\ManagesValueState;
use App\Models\Value;
use App\Models\Version;
use App\Services\Versioning\SnapshotComparison;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Version history of a value (03 · 3.3): versions with author, date and
 * changed fields, archive and review events, a read-only view of any
 * version, and a side by side comparison of two.
 */
class Versions extends Component
{
    use ManagesValueState;

    public Value $value;

    #[Url(as: 'verzio')]
    public ?int $viewing = null;

    #[Url(as: 'bal')]
    public ?int $left = null;

    #[Url(as: 'jobb')]
    public ?int $right = null;

    public function mount(Value $value): void
    {
        Gate::authorize('view', $value);

        $this->value = $value;
    }

    public function compare(): void
    {
        $this->viewing = null;

        if ($this->left !== null && $this->right !== null && $this->left > $this->right) {
            [$this->left, $this->right] = [$this->right, $this->left];
        }
    }

    public function backToList(): void
    {
        $this->viewing = null;
        $this->left = null;
        $this->right = null;
    }

    public function render(): View
    {
        $versions = $this->value->versions()->with('creator')->orderByDesc('number')->get();
        $byNumber = $versions->keyBy('number');

        $viewed = $this->viewing !== null ? $byNumber->get($this->viewing) : null;
        $comparison = null;

        if ($viewed === null && $this->left !== null && $this->right !== null
            && $byNumber->has($this->left) && $byNumber->has($this->right)) {
            $comparison = SnapshotComparison::compare(
                $this->displayable($byNumber->get($this->left)),
                $this->displayable($byNumber->get($this->right)),
                self::fields(),
            );
        }

        return view('livewire.admin.values.versions', [
            'versions' => $versions,
            'timeline' => $this->timeline($versions),
            'viewed' => $viewed,
            'viewedSnapshot' => $viewed ? $this->displayable($viewed) : null,
            'comparison' => $comparison,
            'fields' => self::fields(),
        ])->layout('layouts::admin')->title(__('admin.versions.title', ['name' => $this->value->name]));
    }

    /**
     * @return array<string, string>
     */
    public static function fields(): array
    {
        return [
            'name' => SnapshotComparison::TEXT,
            'slug' => SnapshotComparison::TEXT,
            'category' => SnapshotComparison::TEXT,
            ...array_fill_keys(Value::LISTS, SnapshotComparison::LIST),
        ];
    }

    /**
     * The snapshot with the category as its name, as the comparison shows it.
     *
     * @return array<string, mixed>
     */
    private function displayable(Version $version): array
    {
        $snapshot = $version->snapshot;
        $snapshot['category'] = $snapshot['category']['name'] ?? '';

        return $snapshot;
    }

    /**
     * Versions and events in one list, newest first.
     *
     * @param  Collection<int, Version>  $versions
     * @return Collection<int, array{kind: string, at: mixed, item: mixed}>
     */
    private function timeline(Collection $versions): Collection
    {
        $events = $this->value->versionEvents()->with('creator')->get();

        return $versions->map(fn (Version $version) => ['kind' => 'version', 'at' => $version->created_at, 'item' => $version])
            ->concat($events->map(fn ($event) => ['kind' => 'event', 'at' => $event->created_at, 'item' => $event]))
            ->sortByDesc(fn (array $entry) => [$entry['at']->getTimestamp(), $entry['kind'] === 'event' ? 1 : 0])
            ->values();
    }
}
