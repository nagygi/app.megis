<?php

namespace App\Livewire\Admin\Concerns;

use App\Models\Version;
use App\Services\Versioning\SnapshotComparison;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/**
 * Version history of a versioned entity (03 · 3.3, 07 · 7.3): versions with
 * author, date, changed fields and note, archive and review events, a
 * read-only view of any version, and a side by side comparison of two.
 */
trait ShowsVersionHistory
{
    #[Url(as: 'verzio')]
    public ?int $viewing = null;

    #[Url(as: 'bal')]
    public ?int $left = null;

    #[Url(as: 'jobb')]
    public ?int $right = null;

    /**
     * The entity whose history is shown; it uses HasVersions.
     */
    abstract protected function versioned(): Model;

    /**
     * Snapshot fields as SnapshotComparison::TEXT or LIST, in display order.
     *
     * @return array<string, string>
     */
    abstract public static function fields(): array;

    /**
     * The snapshot as the page shows it, e.g. a category by its name.
     *
     * @return array<string, mixed>
     */
    abstract protected function displayable(Version $version): array;

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

    /**
     * @return array<string, mixed>
     */
    protected function versionHistory(): array
    {
        $versions = $this->versioned()->versions()->with('creator')->orderByDesc('number')->get();
        $byNumber = $versions->keyBy('number');

        $viewed = $this->viewing !== null ? $byNumber->get($this->viewing) : null;
        $comparison = null;

        if ($viewed === null && $this->left !== null && $this->right !== null
            && $byNumber->has($this->left) && $byNumber->has($this->right)) {
            $comparison = SnapshotComparison::compare(
                $this->displayable($byNumber->get($this->left)),
                $this->displayable($byNumber->get($this->right)),
                static::fields(),
            );
        }

        return [
            'versions' => $versions,
            'timeline' => $this->timeline($versions),
            'viewed' => $viewed,
            'viewedSnapshot' => $viewed ? $this->displayable($viewed) : null,
            'comparison' => $comparison,
            'fields' => static::fields(),
        ];
    }

    /**
     * Versions and events in one list, newest first.
     *
     * @param  Collection<int, Version>  $versions
     * @return Collection<int, array{kind: string, at: mixed, item: mixed}>
     */
    private function timeline(Collection $versions): Collection
    {
        $events = $this->versioned()->versionEvents()->with('creator')->get();

        return $versions->map(fn (Version $version) => ['kind' => 'version', 'at' => $version->created_at, 'item' => $version])
            ->concat($events->map(fn ($event) => ['kind' => 'event', 'at' => $event->created_at, 'item' => $event]))
            ->sortByDesc(fn (array $entry) => [$entry['at']->getTimestamp(), $entry['kind'] === 'event' ? 1 : 0])
            ->values();
    }
}
