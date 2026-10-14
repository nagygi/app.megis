<?php

namespace App\Livewire\Admin\Values;

use App\Livewire\Admin\Concerns\ShowsVersionHistory;
use App\Livewire\Admin\Values\Concerns\ManagesValueState;
use App\Models\Value;
use App\Models\Version;
use App\Services\Versioning\SnapshotComparison;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Version history of a value (03 · 3.3).
 */
class Versions extends Component
{
    use ManagesValueState, ShowsVersionHistory;

    public Value $value;

    public function mount(Value $value): void
    {
        Gate::authorize('view', $value);

        $this->value = $value;
    }

    public function render(): View
    {
        return view('livewire.admin.values.versions', $this->versionHistory())
            ->layout('layouts::admin')
            ->title(__('admin.versions.title', ['name' => $this->value->name]));
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

    protected function versioned(): Model
    {
        return $this->value;
    }

    /**
     * The snapshot with the category as its name, as the comparison shows it.
     *
     * @return array<string, mixed>
     */
    protected function displayable(Version $version): array
    {
        $snapshot = $version->snapshot;
        $snapshot['category'] = $snapshot['category']['name'] ?? '';

        return $snapshot;
    }
}
