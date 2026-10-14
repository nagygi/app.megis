<?php

namespace App\Livewire\Admin\Capabilities;

use App\Enums\CapabilityLevel;
use App\Enums\CapabilityNature;
use App\Enums\CapabilityStatus;
use App\Livewire\Admin\Capabilities\Concerns\ManagesCapabilityState;
use App\Livewire\Admin\Concerns\ShowsVersionHistory;
use App\Models\Capability;
use App\Models\Version;
use App\Services\Versioning\SnapshotComparison;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Version history of a capability (07 · 7.3), the same as in the Értéktár.
 */
class Versions extends Component
{
    use ManagesCapabilityState, ShowsVersionHistory;

    public Capability $capability;

    public function mount(Capability $capability): void
    {
        Gate::authorize('view', $capability);

        $this->capability = $capability;
    }

    public function render(): View
    {
        return view('livewire.admin.capabilities.versions', $this->versionHistory())
            ->layout('layouts::admin')
            ->title(__('admin.versions.title', ['name' => $this->capability->name]));
    }

    /**
     * @return array<string, string>
     */
    public static function fields(): array
    {
        $text = SnapshotComparison::TEXT;
        $list = SnapshotComparison::LIST;

        return [
            'name' => $text,
            'name_en' => $text,
            'slug' => $text,
            'group' => $text,
            'level' => $text,
            'status' => $text,
            'nature' => $text,
            'tempting' => $text,
            'definition' => $text,
            'statement' => $text,
            'non_examples' => $list,
            'overlaps' => $list,
            ...collect(CapabilityLevel::cases())->mapWithKeys(fn (CapabilityLevel $level) => ['appearance_'.$level->value => $text])->all(),
            'preconditions' => $list,
            'dependencies' => $list,
            ...collect(Capability::STAGES)->mapWithKeys(fn (int $stage) => ['stage_'.$stage => $text])->all(),
            'keywords' => $list,
            'details' => $list,
        ];
    }

    protected function versioned(): Model
    {
        return $this->capability;
    }

    /**
     * The snapshot with names and labels instead of keys.
     *
     * @return array<string, mixed>
     */
    protected function displayable(Version $version): array
    {
        $snapshot = $version->snapshot;
        $snapshot['group'] = $snapshot['group']['name'] ?? '';
        $snapshot['level'] = CapabilityLevel::tryFrom((string) ($snapshot['level'] ?? ''))?->label() ?? '';
        $snapshot['status'] = CapabilityStatus::tryFrom((string) ($snapshot['status'] ?? ''))?->label() ?? '';
        $snapshot['nature'] = CapabilityNature::tryFrom((string) ($snapshot['nature'] ?? ''))?->label() ?? '';
        $snapshot['tempting'] = match ($snapshot['tempting'] ?? null) {
            true => __('admin.capabilities.tempting_yes'),
            false => __('admin.capabilities.tempting_no'),
            default => '',
        };

        return $snapshot;
    }
}
