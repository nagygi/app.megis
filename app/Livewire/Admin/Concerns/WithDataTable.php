<?php

namespace App\Livewire\Admin\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Shared behaviour of admin lists (NFR · 3): search, filters on column values
 * and sorting by any listed column. The state lives in the query string, so a
 * list can be bookmarked or shared.
 */
trait WithDataTable
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'rendezes', except: '')]
    public string $sortField = '';

    #[Url(as: 'irany', except: '')]
    public string $sortDirection = '';

    /**
     * @var array<string, string>
     */
    #[Url(as: 'szuro')]
    public array $filters = [];

    /**
     * Sortable column keys mapped to the column or subquery that sorts them.
     * Only these may be sorted, so user input never reaches the query as an identifier.
     *
     * @return array<string, string|Builder<Model>>
     */
    abstract protected function sortableColumns(): array;

    /**
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    abstract protected function defaultSort(): array;

    public function sortBy(string $field): void
    {
        if (! array_key_exists($field, $this->sortableColumns())) {
            return;
        }

        [$currentField, $currentDirection] = $this->currentSort();

        $this->sortField = $field;
        $this->sortDirection = $currentField === $field && $currentDirection === 'asc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilters(): void
    {
        $this->resetPage();
    }

    /**
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    public function currentSort(): array
    {
        $field = array_key_exists($this->sortField, $this->sortableColumns()) ? $this->sortField : $this->defaultSort()[0];
        $direction = in_array($this->sortDirection, ['asc', 'desc'], true)
            ? $this->sortDirection
            : ($field === $this->defaultSort()[0] ? $this->defaultSort()[1] : 'asc');

        return [$field, $direction];
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function applySorting(Builder $query): Builder
    {
        [$field, $direction] = $this->currentSort();

        return $query
            ->orderBy($this->sortableColumns()[$field], $direction)
            ->orderBy($query->getModel()->getQualifiedKeyName());
    }

    protected function filter(string $key): ?string
    {
        $value = $this->filters[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * A "contains" pattern for whereLike(), which is case-insensitive.
     */
    protected function searchTerm(): ?string
    {
        $term = trim($this->search);

        return $term === '' ? null : '%'.$term.'%';
    }
}
