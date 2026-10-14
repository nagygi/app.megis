<?php

namespace App\Livewire\Admin\Products;

use App\Livewire\Admin\Concerns\WithDataTable;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Termékek (02 · 2.1).
 */
class Index extends Component
{
    use WithDataTable;

    public function mount(): void
    {
        Gate::authorize('viewAny', Product::class);
    }

    /**
     * Only a product without organizations may be deleted.
     */
    public function delete(int $productId): void
    {
        $product = Product::query()->withCount('organizations')->findOrFail($productId);
        Gate::authorize('update', $product);

        if ($product->organizations_count > 0) {
            session()->flash('error', __('admin.products.cannot_delete'));

            return;
        }

        $product->delete();
        session()->flash('status', __('admin.common.deleted'));
    }

    public function render(): View
    {
        $query = Product::query()->withCount('organizations');

        if ($term = $this->searchTerm()) {
            $query->where(fn (Builder $search) => $search->whereLike('name', $term)->orWhereLike('description', $term));
        }

        return view('livewire.admin.products.index', [
            'products' => $this->applySorting($query)->paginate(25),
        ])->layout('layouts::admin')->title(__('admin.products.title'));
    }

    protected function sortableColumns(): array
    {
        return [
            'name' => 'name',
            'organizations' => 'organizations_count',
        ];
    }

    protected function defaultSort(): array
    {
        return ['name', 'asc'];
    }
}
