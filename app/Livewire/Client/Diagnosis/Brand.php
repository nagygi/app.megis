<?php

namespace App\Livewire\Client\Diagnosis;

use App\Enums\DiagnosisStep;
use App\Enums\StepMark;
use App\Enums\TreeElement;
use App\Livewire\Client\Diagnosis\Concerns\InteractsWithMainDiagnosis;
use App\Livewire\Concerns\WithDictation;
use App\Models\Diagnosis;
use App\Models\Organization;
use App\Models\OrganizationValueSet;
use App\Services\MainDiagnoses\StepProgress;
use App\Services\Organizations\OrganizationValueSetService;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * V4 · A márkád alapja (08 · 8.2): four boxes; the root lists the values,
 * the other three are answered in at most 500 characters, typed or spoken.
 * Each box saves when left; "Tovább" needs all four and records the version.
 */
class Brand extends Component
{
    use InteractsWithMainDiagnosis, WithDictation, WithFileUploads;

    public string $purpose = '';

    public string $value_proposition = '';

    public string $impact = '';

    public ?string $editing = null;

    public function mount(Organization $organization, Diagnosis $diagnosis, OrganizationValueSetService $valueSets): void
    {
        $this->bootDiagnosis($organization, $diagnosis);
        abort_unless($this->isLeader(), 404);

        $set = $valueSets->forOrganization($organization);

        foreach (OrganizationValueSet::BRAND_FIELDS as $field) {
            $this->{$field} = (string) $set->{$field};
        }
    }

    public function edit(string $field): void
    {
        if (in_array($field, OrganizationValueSet::BRAND_FIELDS, true) && $this->canEdit()) {
            $this->editing = $field;
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, OrganizationValueSet::BRAND_FIELDS, true) && $this->canEdit()) {
            $this->validateOnly($property, [$property => ['nullable', 'string', 'max:500']], attributes: [$property => __('enums.tree_element.'.$this->elementOf($property)->value)]);
            $this->persist(app(OrganizationValueSetService::class));

            if (filled($this->{$property}) && $this->editing === $property) {
                $this->editing = null;
            }
        }
    }

    public function next(OrganizationValueSetService $valueSets): void
    {
        if (! $this->canEdit()) {
            $this->markStep(DiagnosisStep::Brand, StepMark::Viewed);
            $this->redirect($this->nextUrl(DiagnosisStep::Brand), navigate: false);

            return;
        }

        $this->validate(
            collect(OrganizationValueSet::BRAND_FIELDS)->mapWithKeys(fn (string $field) => [$field => ['required', 'string', 'max:500']])->all(),
            attributes: collect(OrganizationValueSet::BRAND_FIELDS)->mapWithKeys(fn (string $field) => [$field => __('enums.tree_element.'.$this->elementOf($field)->value)])->all(),
        );

        $this->persist($valueSets, recordVersion: true);
        $this->redirect($this->nextUrl(DiagnosisStep::Brand), navigate: false);
    }

    public function render(OrganizationValueSetService $valueSets): View
    {
        $set = $valueSets->forOrganization($this->organization);
        $names = $set->items()->pluck('name');

        return view('livewire.client.diagnosis.brand', [
            'canEdit' => $this->canEdit(),
            'names' => $names,
            'doneCount' => ($names->count() >= OrganizationValueSet::MIN_VALUES ? 1 : 0)
                + collect(OrganizationValueSet::BRAND_FIELDS)->filter(fn (string $field) => filled($this->{$field}))->count(),
            'givenBy' => $set->latestVersion?->creator?->name ?? $set->updater?->name,
        ])->layout('layouts::client', $this->stepLayout(DiagnosisStep::Brand))->title(DiagnosisStep::Brand->label());
    }

    public function canEdit(): bool
    {
        return $this->acceptsInput() && ! app(StepProgress::class)->isReadonly($this->main(), $this->user(), DiagnosisStep::Brand);
    }

    public function elementOf(string $field): TreeElement
    {
        foreach (TreeElement::cases() as $element) {
            if ($element->valueSetField() === $field) {
                return $element;
            }
        }

        return TreeElement::Root;
    }

    protected function canDictate(string $field): bool
    {
        return $this->canEdit() && ! $this->isImpersonating() && in_array($field, OrganizationValueSet::BRAND_FIELDS, true);
    }

    protected function dictationOrganization(): ?Organization
    {
        return $this->organization;
    }

    protected function dictated(string $field): void
    {
        $this->{$field} = mb_substr($this->{$field}, 0, 500);
        $this->persist(app(OrganizationValueSetService::class));
    }

    private function persist(OrganizationValueSetService $valueSets, bool $recordVersion = false): void
    {
        $set = $valueSets->forOrganization($this->organization);

        $valueSets->save($set, $set->items()->get()->map(fn ($item) => [
            'key' => $item->key,
            'value_id' => $item->value_id,
            'source_version_id' => $item->source_version_id,
            'name' => $item->name,
            'meaning' => $item->meaning,
            'non_meaning' => $item->non_meaning,
            'low_example' => $item->low_example,
            'high_example' => $item->high_example,
            'example' => $item->example,
        ])->all(), [
            'purpose' => $this->purpose,
            'value_proposition' => $this->value_proposition,
            'impact' => $this->impact,
        ], $recordVersion);

        $this->dispatch('saved');
    }
}
