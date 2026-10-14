<?php

namespace App\Livewire\Admin\ValueCategories;

use App\Livewire\Admin\Concerns\WithDataTable;
use App\Models\ValueCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Értéktár → Kategóriák (03 · 3.4).
 */
class Index extends Component
{
    use WithDataTable;

    public function mount(): void
    {
        Gate::authorize('viewAny', ValueCategory::class);
    }

    /**
     * Only a category without values may be deleted.
     */
    public function delete(int $categoryId): void
    {
        $category = ValueCategory::query()->withCount('values')->findOrFail($categoryId);
        Gate::authorize('update', $category);

        if ($category->values_count > 0) {
            session()->flash('error', __('admin.value_categories.cannot_delete'));

            return;
        }

        $category->delete();
        session()->flash('status', __('admin.common.deleted'));
    }

    public function render(): View
    {
        $query = ValueCategory::query()->withCount('values');

        if ($term = $this->searchTerm()) {
            $query->where(fn (Builder $search) => $search->whereLike('name', $term)->orWhereLike('description', $term));
        }

        return view('livewire.admin.value-categories.index', [
            'categories' => $this->applySorting($query)->paginate(25),
        ])->layout('layouts::admin')->title(__('admin.value_categories.title'));
    }

    protected function sortableColumns(): array
    {
        return [
            'name' => 'name',
            'values' => 'values_count',
        ];
    }

    protected function defaultSort(): array
    {
        return ['name', 'asc'];
    }
}
