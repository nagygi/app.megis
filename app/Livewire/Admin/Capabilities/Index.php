<?php

namespace App\Livewire\Admin\Capabilities;

use App\Enums\CapabilityLevel;
use App\Enums\CapabilityNature;
use App\Enums\CapabilityStatus;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Models\Capability;
use App\Models\CapabilityGroup;
use App\Models\Version;
use App\Services\Capabilities\CapabilityService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Képességtár → Képességek (07 · 7.1). Archived capabilities are hidden by default.
 */
class Index extends Component
{
    use WithDataTable;

    public function mount(): void
    {
        Gate::authorize('viewAny', Capability::class);
    }

    public function archive(int $capabilityId, CapabilityService $capabilities): void
    {
        $capability = Capability::query()->findOrFail($capabilityId);
        Gate::authorize('update', $capability);

        $capabilities->archive($capability);
        session()->flash('status', __('admin.capabilities.archived_flash'));
    }

    public function restore(int $capabilityId, CapabilityService $capabilities): void
    {
        $capability = Capability::query()->findOrFail($capabilityId);
        Gate::authorize('update', $capability);

        $capabilities->restore($capability);
        session()->flash('status', __('admin.capabilities.restored_flash'));
    }

    public function render(): View
    {
        $query = Capability::query()->with(['group', 'latestVersion.creator']);

        if ($term = $this->searchTerm()) {
            $query->whereLike('search_text', mb_strtolower($term));
        }

        match ($this->filter('state')) {
            'archived' => $query->whereNotNull('archived_at'),
            'all' => null,
            default => $query->whereNull('archived_at'),
        };

        if ($group = $this->filter('group')) {
            $query->where('capability_group_id', $group);
        }

        foreach (['level' => CapabilityLevel::class, 'status' => CapabilityStatus::class, 'nature' => CapabilityNature::class] as $column => $enum) {
            if (($value = $enum::tryFrom((string) $this->filter($column))) !== null) {
                $query->where($column, $value);
            }
        }

        $options = fn (string $enum): array => collect($enum::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all();

        return view('livewire.admin.capabilities.index', [
            'capabilities' => $this->applySorting($query)->paginate(25),
            'groups' => CapabilityGroup::query()->orderBy('name')->pluck('name', 'id')->all(),
            'levels' => $options(CapabilityLevel::class),
            'statuses' => $options(CapabilityStatus::class),
            'natures' => $options(CapabilityNature::class),
        ])->layout('layouts::admin')->title(__('admin.capabilities.title'));
    }

    protected function sortableColumns(): array
    {
        $latestVersion = fn (string $column) => Version::query()
            ->select($column)
            ->whereColumn('versionable_id', 'capabilities.id')
            ->where('versionable_type', (new Capability)->getMorphClass())
            ->orderByDesc('number')
            ->limit(1);

        return [
            'code' => 'code',
            'name' => 'name',
            'group' => CapabilityGroup::query()->select('name')->whereColumn('capability_groups.id', 'capabilities.capability_group_id'),
            'level' => 'level',
            'status' => 'status',
            'version' => $latestVersion('number'),
            'updated' => $latestVersion('created_at'),
            'state' => 'archived_at',
        ];
    }

    protected function defaultSort(): array
    {
        return ['code', 'asc'];
    }
}
