<?php

namespace App\Livewire\Admin\Patterns;

use App\Enums\CapabilityLevel;
use App\Enums\PatternMaturity;
use App\Enums\PatternProduct;
use App\Enums\PatternSubtype;
use App\Enums\PatternType;
use App\Enums\PatternValidity;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Models\Capability;
use App\Models\Pattern;
use App\Models\Version;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Képességtár → Minták (NM-19): the patterns of the mégis tár, read only.
 * Only the valid ones by default; the obsolete and rejected stay findable.
 */
class Index extends Component
{
    use WithDataTable;

    public function mount(): void
    {
        Gate::authorize('viewAny', Pattern::class);
    }

    public function render(): View
    {
        $query = Pattern::query()->with(['capabilities:id,code', 'latestVersion']);

        if ($term = $this->searchTerm()) {
            $query->whereLike('search_text', mb_strtolower($term));
        }

        $maturity = PatternMaturity::tryFrom((string) $this->filter('maturity'));

        // An obsolete or rejected maturity is a validity: the default "only valid" would hide it.
        if (! in_array($maturity, [PatternMaturity::Obsolete, PatternMaturity::Rejected], true)) {
            match ($this->filter('validity')) {
                'obsolete' => $query->where('validity', PatternValidity::Obsolete),
                'rejected' => $query->where('validity', PatternValidity::Rejected),
                'all' => null,
                default => $query->where('validity', PatternValidity::Valid),
            };
        }

        if ($capability = $this->filter('capability')) {
            $query->whereHas('capabilities', fn ($capabilities) => $capabilities->whereKey($capability));
        }

        foreach (['type' => PatternType::class, 'subtype' => PatternSubtype::class, 'level' => CapabilityLevel::class] as $column => $enum) {
            if (($value = $enum::tryFrom((string) $this->filter($column))) !== null) {
                $query->where($column, $value);
            }
        }

        if (($product = PatternProduct::tryFrom((string) $this->filter('product'))) !== null) {
            $query->whereJsonContains('products', $product->value);
        }

        if ($maturity !== null) {
            $this->whereMaturity($query, $maturity);
        }

        $options = fn (string $enum): array => collect($enum::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all();

        return view('livewire.admin.patterns.index', [
            'patterns' => $this->applySorting($query)->paginate(50),
            'capabilities' => Capability::query()->orderBy('code')->get(['id', 'code', 'name'])
                ->mapWithKeys(fn (Capability $capability) => [$capability->id => $capability->code.' · '.$capability->name])->all(),
            'types' => $options(PatternType::class),
            'subtypes' => $options(PatternSubtype::class),
            'maturities' => $options(PatternMaturity::class),
            'levels' => $options(CapabilityLevel::class),
            'products' => $options(PatternProduct::class),
        ])->layout('layouts::admin')->title(__('admin.patterns.title'));
    }

    protected function sortableColumns(): array
    {
        return [
            'code' => 'code',
            'name' => 'name',
            'type' => 'type',
            'counts' => 'sheet_approved_clients',
            'version' => Version::query()
                ->select('number')
                ->whereColumn('versionable_id', 'patterns.id')
                ->where('versionable_type', (new Pattern)->getMorphClass())
                ->orderByDesc('number')
                ->limit(1),
        ];
    }

    protected function defaultSort(): array
    {
        return ['code', 'asc'];
    }

    /**
     * The maturity is computed (PatternMaturity::compute), so the filter is its formula in SQL.
     *
     * @param  Builder<Pattern>  $query
     */
    private function whereMaturity(Builder $query, PatternMaturity $maturity): void
    {
        $literature = ['external', 'both'];

        match ($maturity) {
            PatternMaturity::Rejected => $query->where('validity', PatternValidity::Rejected),
            PatternMaturity::Obsolete => $query->where('validity', PatternValidity::Obsolete),
            PatternMaturity::Used => $query->where('validity', PatternValidity::Valid)->whereRaw('(sheet_approved_clients + approved_organizations) >= 2'),
            PatternMaturity::SeenOnce => $query->where('validity', PatternValidity::Valid)->whereRaw('(sheet_approved_clients + approved_organizations) = 1'),
            PatternMaturity::HypothesisLiterature => $query->where('validity', PatternValidity::Valid)->whereRaw('(sheet_approved_clients + approved_organizations) = 0')->whereIn('origin', $literature),
            PatternMaturity::HypothesisOwn => $query->where('validity', PatternValidity::Valid)->whereRaw('(sheet_approved_clients + approved_organizations) = 0')
                ->where(fn ($origin) => $origin->whereNull('origin')->orWhereNotIn('origin', $literature)),
        };
    }
}
