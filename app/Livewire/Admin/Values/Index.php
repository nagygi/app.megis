<?php

namespace App\Livewire\Admin\Values;

use App\Enums\ValueGrade;
use App\Enums\ValueOrigin;
use App\Enums\ValueSvsStatus;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Models\Value;
use App\Models\ValueCategory;
use App\Models\Version;
use App\Services\Values\ValueService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Értéktár → Értékek (03 · 3.1). Archived values are hidden by default.
 */
class Index extends Component
{
    use WithDataTable;

    public function mount(): void
    {
        Gate::authorize('viewAny', Value::class);
    }

    public function archive(int $valueId, ValueService $values): void
    {
        $value = Value::query()->findOrFail($valueId);
        Gate::authorize('update', $value);

        $values->archive($value);
        session()->flash('status', __('admin.values.archived_flash'));
    }

    public function restore(int $valueId, ValueService $values): void
    {
        $value = Value::query()->findOrFail($valueId);
        Gate::authorize('update', $value);

        $values->restore($value);
        session()->flash('status', __('admin.values.restored_flash'));
    }

    public function markReviewed(int $valueId, ValueService $values): void
    {
        $value = Value::query()->findOrFail($valueId);
        Gate::authorize('update', $value);

        $values->markReviewed($value);
        session()->flash('status', __('admin.values.reviewed_flash'));
    }

    public function render(): View
    {
        $query = Value::query()->with(['category', 'latestVersion.creator', 'originOrganization']);

        if ($term = $this->searchTerm()) {
            $query->where(fn ($search) => $search
                ->whereLike('search_text', mb_strtolower($term))
                ->orWhere('code', trim($this->search)));
        }

        match ($this->filter('status')) {
            'archived' => $query->whereNotNull('archived_at'),
            'all' => null,
            default => $query->whereNull('archived_at'),
        };

        if ($category = $this->filter('category')) {
            $query->where('value_category_id', $category);
        }

        if ($higherOrder = $this->filter('higher_order')) {
            $query->whereHas('category', fn ($categories) => $categories->where('higher_order', $higherOrder));
        }

        if (($evidence = ValueGrade::tryFrom((string) $this->filter('evidence'))) !== null) {
            $query->where('evidence', $evidence);
        }

        if (($svs = ValueSvsStatus::tryFrom((string) $this->filter('svs'))) !== null) {
            $query->where('svs_status', $svs);
        }

        if (($origin = ValueOrigin::tryFrom((string) $this->filter('origin'))) !== null) {
            $query->where('origin', $origin);
        }

        match ($this->filter('review')) {
            'awaiting' => $query->awaitingReview(),
            'reviewed' => $query->whereNotNull('reviewed_at'),
            default => null,
        };

        return view('livewire.admin.values.index', [
            'values' => $this->applySorting($query)->paginate(25),
            'categories' => ValueCategory::query()->orderBy('name')->pluck('name', 'id')->all(),
            'higherOrders' => ValueCategory::query()->whereNotNull('higher_order')->distinct()->orderBy('higher_order')->pluck('higher_order', 'higher_order')->all(),
            'grades' => collect(ValueGrade::cases())->mapWithKeys(fn (ValueGrade $grade) => [$grade->value => $grade->label()])->all(),
            'svsStatuses' => collect(ValueSvsStatus::cases())->mapWithKeys(fn (ValueSvsStatus $status) => [$status->value => $status->label()])->all(),
            'origins' => collect(ValueOrigin::cases())->mapWithKeys(fn (ValueOrigin $origin) => [$origin->value => $origin->label()])->all(),
        ])->layout('layouts::admin')->title(__('admin.values.title'));
    }

    protected function sortableColumns(): array
    {
        $latestVersion = fn (string $column) => Version::query()
            ->select($column)
            ->whereColumn('versionable_id', 'values.id')
            ->where('versionable_type', (new Value)->getMorphClass())
            ->orderByDesc('number')
            ->limit(1);

        return [
            'name' => 'name',
            'category' => ValueCategory::query()->select('name')->whereColumn('value_categories.id', 'values.value_category_id'),
            'version' => $latestVersion('number'),
            'updated' => $latestVersion('created_at'),
            'status' => 'archived_at',
            'origin' => 'origin',
        ];
    }

    protected function defaultSort(): array
    {
        return ['name', 'asc'];
    }
}
