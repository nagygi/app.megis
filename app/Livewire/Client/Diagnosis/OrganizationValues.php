<?php

namespace App\Livewire\Client\Diagnosis;

use App\Enums\DiagnosisStep;
use App\Enums\StepMark;
use App\Livewire\Client\Diagnosis\Concerns\InteractsWithMainDiagnosis;
use App\Livewire\Concerns\WithDictation;
use App\Models\Diagnosis;
use App\Models\Organization;
use App\Models\OrganizationValueSet;
use App\Models\OrganizationValueSetItem;
use App\Models\PersonalValueSet;
use App\Models\Value;
use App\Services\MainDiagnoses\StepProgress;
use App\Services\Organizations\OrganizationValueSetService;
use App\Services\Values\ValueService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * V3 · A szervezet értékei (08 · 8.1): 3–5 values in the organization's own
 * words. Only a "szervezet vezetője" edits; any other leader reads them and
 * moves on (17 · 17.3, 23 · 23.3). Fields save when left; "Tovább" records
 * the value set version (08 · 8.4).
 */
class OrganizationValues extends Component
{
    use InteractsWithMainDiagnosis, WithDictation, WithFileUploads;

    /**
     * @var list<array<string, mixed>>
     */
    public array $items = [];

    public ?string $openKey = null;

    public ?int $pickedValueId = null;

    public string $newValueName = '';

    public function mount(Organization $organization, Diagnosis $diagnosis, OrganizationValueSetService $valueSets): void
    {
        $this->bootDiagnosis($organization, $diagnosis);
        abort_unless($this->isLeader() && in_array(DiagnosisStep::OrganizationValues, $this->main()->leaderSteps(), true), 404);

        $this->items = $this->itemsOf($valueSets->forOrganization($organization));
    }

    public function toggle(string $key): void
    {
        $this->openKey = $this->openKey === $key ? null : $key;
    }

    public function addFromLibrary(OrganizationValueSetService $valueSets): void
    {
        $this->authorizeEdit();

        if (count($this->items) >= OrganizationValueSet::MAX_VALUES) {
            return;
        }

        $this->validate(['pickedValueId' => ['required', Rule::in($this->libraryValues()->pluck('id'))]], attributes: [
            'pickedValueId' => __('diagnosis.organization_values.library'),
        ]);

        $item = $valueSets->itemFromValue(Value::query()->with('latestVersion')->findOrFail($this->pickedValueId));
        $this->items[] = $item;
        $this->openKey = $item['key'];
        $this->pickedValueId = null;
        $this->persist($valueSets);
    }

    public function addNew(OrganizationValueSetService $valueSets, ValueService $values): void
    {
        $this->authorizeEdit();

        if (count($this->items) >= OrganizationValueSet::MAX_VALUES) {
            return;
        }

        $this->validate(['newValueName' => ['required', 'string', 'min:2', 'max:255']], attributes: [
            'newValueName' => __('diagnosis.organization_values.new_value'),
        ]);

        $name = trim($this->newValueName);
        $existing = $values->findByName($name);

        if ($existing !== null && $this->libraryValues()->contains('id', $existing->id)) {
            $this->pickedValueId = $existing->id;
            $this->newValueName = '';
            $this->addFromLibrary($valueSets);

            return;
        }

        if (collect($this->items)->contains(fn (array $item) => mb_strtolower($item['name']) === mb_strtolower($name))) {
            throw ValidationException::withMessages(['newValueName' => __('diagnosis.organization_values.name_taken')]);
        }

        // Saved with the value set, it goes to the Értéktár as "Szervezet által felvett" (08 · 8.1).
        // A name taken by a value not choosable here (e.g. another organization's,
        // not reviewed yet) is tied to it with the organization's own words.
        $key = (string) Str::uuid();
        $this->items[] = [
            'key' => $key,
            'value_id' => $existing?->id,
            'source_version_id' => null,
            'name' => $existing?->name ?? $name,
            ...array_fill_keys(OrganizationValueSetItem::TEXT_FIELDS, ''),
        ];
        $this->openKey = $key;
        $this->newValueName = '';
    }

    public function remove(string $key, OrganizationValueSetService $valueSets): void
    {
        $this->authorizeEdit();

        $this->items = array_values(array_filter($this->items, fn (array $item) => $item['key'] !== $key));
        $this->persist($valueSets);
    }

    /**
     * A field was left: the working copy is saved (08 · 8.1, 15 · 15.8).
     */
    public function updatedItems(): void
    {
        if (! $this->canEdit()) {
            return;
        }

        $this->persist(app(OrganizationValueSetService::class));
    }

    /**
     * "Tovább": 3–5 values with both ends; the version is recorded. Read-only, it counts as viewed.
     */
    public function next(OrganizationValueSetService $valueSets): void
    {
        if (! $this->canEdit()) {
            $this->markStep(DiagnosisStep::OrganizationValues, StepMark::Viewed);
            $this->redirect($this->nextUrl(DiagnosisStep::OrganizationValues), navigate: false);

            return;
        }

        $this->validate([
            'items' => ['array', 'min:'.OrganizationValueSet::MIN_VALUES, 'max:'.OrganizationValueSet::MAX_VALUES],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.meaning' => ['nullable', 'string', 'max:500'],
            'items.*.non_meaning' => ['nullable', 'string', 'max:500'],
            'items.*.low_example' => ['required', 'string', 'max:500'],
            'items.*.high_example' => ['required', 'string', 'max:500'],
            'items.*.example' => ['nullable', 'string', 'max:500'],
        ], [
            'items.min' => __('diagnosis.organization_values.too_few', ['min' => OrganizationValueSet::MIN_VALUES]),
            'items.max' => __('diagnosis.organization_values.too_many', ['max' => OrganizationValueSet::MAX_VALUES]),
        ], $this->attributeNames());

        $this->persist($valueSets, recordVersion: true);
        $this->redirect($this->nextUrl(DiagnosisStep::OrganizationValues), navigate: false);
    }

    public function render(OrganizationValueSetService $valueSets): View
    {
        $set = $valueSets->forOrganization($this->organization);
        $ownValueIds = PersonalValueSet::query()->where('user_id', $this->user()->id)->first()?->items()->pluck('value_id')->all() ?? [];

        return view('livewire.client.diagnosis.organization-values', [
            'canEdit' => $this->canEdit(),
            'ownValueIds' => $ownValueIds,
            'library' => $this->canEdit() ? $this->libraryValues()->whereNotIn('id', array_filter(array_column($this->items, 'value_id'))) : collect(),
            'isFull' => count($this->items) >= OrganizationValueSet::MAX_VALUES,
            'hasVersion' => $set->latestVersion !== null,
            'givenBy' => $set->latestVersion?->creator?->name ?? $set->updater?->name,
        ])->layout('layouts::client', $this->stepLayout(DiagnosisStep::OrganizationValues))->title(DiagnosisStep::OrganizationValues->label());
    }

    public function canEdit(): bool
    {
        return $this->acceptsInput() && ! app(StepProgress::class)->isReadonly($this->main(), $this->user(), DiagnosisStep::OrganizationValues);
    }

    protected function canDictate(string $field): bool
    {
        return $this->canEdit()
            && ! $this->isImpersonating()
            && preg_match('/^items\.\d+\.('.implode('|', OrganizationValueSetItem::TEXT_FIELDS).')$/', $field) === 1;
    }

    protected function dictationOrganization(): ?Organization
    {
        return $this->organization;
    }

    protected function dictated(string $field): void
    {
        $this->persist(app(OrganizationValueSetService::class));
    }

    private function persist(OrganizationValueSetService $valueSets, bool $recordVersion = false): void
    {
        $set = $valueSets->forOrganization($this->organization);

        try {
            $valueSets->save($set, array_values(array_filter($this->items, fn (array $item) => $item['value_id'] !== null || filled($item['low_example']) || $recordVersion)), [
                'purpose' => $set->purpose,
                'value_proposition' => $set->value_proposition,
                'impact' => $set->impact,
            ], $recordVersion);
        } catch (\Throwable $exception) {
            report($exception);
            $this->dispatch('save-failed');

            return;
        }

        // New values got their Értéktár ids; unsaved new cards stay as they are.
        $saved = $this->itemsOf($set->refresh());
        $this->items = array_map(
            fn (array $item) => collect($saved)->firstWhere('key', $item['key']) ?? $item,
            $this->items,
        );
        $this->dispatch('saved');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function itemsOf(OrganizationValueSet $set): array
    {
        return $set->items()->get()->map(fn (OrganizationValueSetItem $item) => [
            'key' => $item->key,
            'value_id' => $item->value_id,
            'source_version_id' => $item->source_version_id,
            'name' => $item->name,
            ...collect(OrganizationValueSetItem::TEXT_FIELDS)->mapWithKeys(fn (string $field) => [$field => (string) $item->{$field}])->all(),
        ])->all();
    }

    /**
     * Active values the organization may choose (08 · 8.1, 03 · 3.7).
     *
     * @return Collection<int, Value>
     */
    private function libraryValues(): Collection
    {
        return Value::query()->availableTo($this->organization)->orderBy('name')->get(['id', 'name', 'meanings']);
    }

    private function authorizeEdit(): void
    {
        abort_unless($this->canEdit(), 403, __('diagnosis.locked'));
    }

    /**
     * @return array<string, string>
     */
    private function attributeNames(): array
    {
        return [
            'items' => __('diagnosis.organization_values.values'),
            'items.*.name' => __('diagnosis.organization_values.fields.name'),
            ...collect(OrganizationValueSetItem::TEXT_FIELDS)->mapWithKeys(fn (string $field) => ['items.*.'.$field => __('diagnosis.organization_values.fields.'.$field)])->all(),
        ];
    }
}
