<?php

namespace App\Livewire\Admin\Organizations;

use App\Models\Organization;
use App\Models\OrganizationValueSet;
use App\Models\OrganizationValueSetItem;
use App\Models\Value;
use App\Services\Organizations\OrganizationValueSetService;
use App\Services\Values\ValueService;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The organization's "Értékek" tab (08 · 8.5): 3–5 values in the
 * organization's words and the brand base, with the same rules as on the
 * customer side. Every saved change is a new version of the value set.
 */
class Values extends Component
{
    public Organization $organization;

    /**
     * @var list<array<string, mixed>>
     */
    public array $items = [];

    public string $purpose = '';

    public string $value_proposition = '';

    public string $impact = '';

    public ?int $pickedValueId = null;

    public string $newValueName = '';

    #[Url(as: 'verzio')]
    public ?int $viewing = null;

    public function mount(Organization $organization): void
    {
        Gate::authorize('update', $organization);

        $this->organization = $organization;
        $set = $organization->valueSet()->first();

        if ($set !== null) {
            $this->items = $set->items->map(fn ($item) => [
                'key' => $item->key,
                'value_id' => $item->value_id,
                'source_version_id' => $item->source_version_id,
                'name' => $item->name,
                ...collect(OrganizationValueSetItem::TEXT_FIELDS)->mapWithKeys(fn (string $field) => [$field => (string) $item->{$field}])->all(),
            ])->all();

            foreach (OrganizationValueSet::BRAND_FIELDS as $field) {
                $this->{$field} = (string) $set->{$field};
            }
        }
    }

    public function addFromLibrary(OrganizationValueSetService $valueSets): void
    {
        Gate::authorize('update', $this->organization);

        if (count($this->items) >= OrganizationValueSet::MAX_VALUES) {
            return;
        }

        $this->validate(['pickedValueId' => ['required', Rule::in($this->libraryValues()->pluck('id'))]], attributes: [
            'pickedValueId' => __('admin.value_sets.library'),
        ]);

        $value = Value::query()->with('latestVersion')->findOrFail($this->pickedValueId);
        $this->items[] = $valueSets->itemFromValue($value);
        $this->pickedValueId = null;
    }

    public function addNew(ValueService $values): void
    {
        Gate::authorize('update', $this->organization);

        if (count($this->items) >= OrganizationValueSet::MAX_VALUES) {
            return;
        }

        $this->validate(['newValueName' => ['required', 'string', 'min:2', 'max:255', $this->uniqueNewName($values)]], attributes: [
            'newValueName' => __('admin.value_sets.new_value'),
        ]);

        $this->items[] = [
            'key' => (string) Str::uuid(),
            'value_id' => null,
            'source_version_id' => null,
            'name' => trim($this->newValueName),
            'meaning' => '',
            'non_meaning' => '',
            'low_example' => '',
            'high_example' => '',
            'example' => '',
        ];
        $this->newValueName = '';
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->resetValidation();
    }

    public function moveItem(int $index, int $offset): void
    {
        $target = $index + $offset;

        if (! isset($this->items[$index], $this->items[$target])) {
            return;
        }

        [$this->items[$index], $this->items[$target]] = [$this->items[$target], $this->items[$index]];
        $this->resetValidation();
    }

    public function save(OrganizationValueSetService $valueSets, ValueService $values): void
    {
        Gate::authorize('update', $this->organization);

        $this->validate([
            'items' => ['array', 'min:'.OrganizationValueSet::MIN_VALUES, 'max:'.OrganizationValueSet::MAX_VALUES],
            'items.*.value_id' => ['nullable', 'distinct', Rule::in($this->libraryValues(includeInSet: true)->pluck('id'))],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.meaning' => ['nullable', 'string', 'max:500'],
            'items.*.non_meaning' => ['nullable', 'string', 'max:500'],
            'items.*.low_example' => ['required', 'string', 'max:500'],
            'items.*.high_example' => ['required', 'string', 'max:500'],
            'items.*.example' => ['nullable', 'string', 'max:500'],
            'purpose' => ['nullable', 'string', 'max:500'],
            'value_proposition' => ['nullable', 'string', 'max:500'],
            'impact' => ['nullable', 'string', 'max:500'],
        ], [
            'items.min' => __('admin.value_sets.too_few', ['min' => OrganizationValueSet::MIN_VALUES]),
            'items.max' => __('admin.value_sets.too_many', ['max' => OrganizationValueSet::MAX_VALUES]),
        ], $this->validationAttributes());

        foreach ($this->items as $index => $item) {
            if ($item['value_id'] === null && $values->findByName($item['name']) !== null) {
                $this->addError('items.'.$index.'.name', __('admin.value_sets.name_in_library'));

                return;
            }
        }

        $set = $valueSets->forOrganization($this->organization);
        $hadVersion = $set->latestVersion !== null;
        $version = $valueSets->save($set, $this->items, [
            'purpose' => $this->purpose,
            'value_proposition' => $this->value_proposition,
            'impact' => $this->impact,
        ]);

        // New values got their Értéktár ids; keep the form in step with what was saved.
        $this->items = $set->items()->get()->map(fn ($item) => [
            'key' => $item->key,
            'value_id' => $item->value_id,
            'source_version_id' => $item->source_version_id,
            'name' => $item->name,
            ...collect(OrganizationValueSetItem::TEXT_FIELDS)->mapWithKeys(fn (string $field) => [$field => (string) $item->{$field}])->all(),
        ])->all();

        session()->flash('status', match (true) {
            $version === null => __('admin.value_sets.saved_unchanged'),
            $hadVersion => __('admin.value_sets.saved_version_changed', ['version' => $version->label()]),
            default => __('admin.value_sets.saved_version', ['version' => $version->label()]),
        });
    }

    public function render(): View
    {
        $set = $this->organization->valueSet()->first();
        $versions = $set?->versions()->with('creator')->orderByDesc('number')->get() ?? collect();

        return view('livewire.admin.organizations.values', [
            'libraryValues' => $this->libraryValues()->whereNotIn('id', array_filter(array_column($this->items, 'value_id'))),
            'versions' => $versions,
            'viewed' => $this->viewing === null ? null : $versions->firstWhere('number', $this->viewing),
            'hasVersion' => $versions->isNotEmpty(),
            'isFull' => count($this->items) >= OrganizationValueSet::MAX_VALUES,
        ])->layout('layouts::admin', ['organization' => $this->organization])->title($this->organization->name);
    }

    /**
     * Active Értéktár values the organization may choose (08 · 8.1, 03 · 3.7).
     * Values already in the set stay valid even if they were archived since.
     *
     * @return Collection<int, Value>
     */
    private function libraryValues(bool $includeInSet = false): Collection
    {
        $available = Value::query()->availableTo($this->organization)->orderBy('name')->get(['id', 'name']);

        if (! $includeInSet) {
            return $available;
        }

        $inSet = $this->organization->valueSet()->first()?->items()->pluck('value_id') ?? collect();

        return $available->concat(Value::query()->whereIn('id', $inSet)->get(['id', 'name']))->unique('id');
    }

    private function uniqueNewName(ValueService $values): Closure
    {
        return function (string $attribute, mixed $name, Closure $fail) use ($values): void {
            if ($values->findByName((string) $name) !== null) {
                $fail(__('admin.value_sets.name_in_library'));
            }

            if (collect($this->items)->contains(fn (array $item) => mb_strtolower($item['name']) === mb_strtolower(trim((string) $name)))) {
                $fail(__('admin.value_sets.name_in_set'));
            }
        };
    }

    /**
     * @return array<string, string>
     */
    private function validationAttributes(): array
    {
        return [
            'items' => __('admin.value_sets.values'),
            'items.*.value_id' => __('admin.value_sets.value'),
            'items.*.name' => __('admin.value_sets.fields.name'),
            ...collect(OrganizationValueSetItem::TEXT_FIELDS)->mapWithKeys(fn (string $field) => ['items.*.'.$field => __('admin.value_sets.fields.'.$field)])->all(),
            ...collect(OrganizationValueSet::BRAND_FIELDS)->mapWithKeys(fn (string $field) => [$field => __('admin.value_sets.brand.'.$field)])->all(),
        ];
    }
}
