<?php

namespace App\Livewire\Admin\Organizations;

use App\Models\Organization;
use App\Models\OrganizationValueSet;
use App\Services\Organizations\OrganizationValueSetService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The organization's "A márkád alapja" tab (08 · 8.2, 8.5), after "Értékek":
 * the root lists the saved values, the other three are required. It saves
 * into the same value set as the values, so a change is a new version of it.
 */
class Brand extends Component
{
    public Organization $organization;

    public string $purpose = '';

    public string $value_proposition = '';

    public string $impact = '';

    #[Url(as: 'verzio')]
    public ?int $viewing = null;

    public function mount(Organization $organization): void
    {
        Gate::authorize('update', $organization);

        $this->organization = $organization;
        $set = $organization->valueSet()->first();

        foreach (OrganizationValueSet::BRAND_FIELDS as $field) {
            $this->{$field} = (string) $set?->{$field};
        }
    }

    public function save(OrganizationValueSetService $valueSets): void
    {
        Gate::authorize('update', $this->organization);

        // All four brand base elements are required, as on the customer side (08 · 8.2).
        $this->validate(
            collect(OrganizationValueSet::BRAND_FIELDS)->mapWithKeys(fn (string $field) => [$field => ['required', 'string', 'max:500']])->all(),
            attributes: collect(OrganizationValueSet::BRAND_FIELDS)->mapWithKeys(fn (string $field) => [$field => __('admin.value_sets.brand.'.$field)])->all(),
        );

        $set = $valueSets->forOrganization($this->organization);
        $hadVersion = $set->latestVersion !== null;
        $version = $valueSets->saveBrand($set, [
            'purpose' => $this->purpose,
            'value_proposition' => $this->value_proposition,
            'impact' => $this->impact,
        ]);

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

        return view('livewire.admin.organizations.brand', [
            'names' => $set?->items()->pluck('name') ?? collect(),
            'versions' => $versions,
            'viewed' => $this->viewing === null ? null : $versions->firstWhere('number', $this->viewing),
            'hasVersion' => $versions->isNotEmpty(),
        ])->layout('layouts::admin')->title($this->organization->name);
    }
}
