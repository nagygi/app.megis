<?php

namespace App\Livewire\Client\Diagnosis;

use App\Enums\DiagnosisStep;
use App\Enums\MainDiagnosisSubject;
use App\Livewire\Client\Diagnosis\Concerns\InteractsWithMainDiagnosis;
use App\Models\Diagnosis;
use App\Models\Organization;
use App\Models\PersonalValueSet;
use App\Models\PersonalValueSetItem;
use App\Models\Value;
use App\Services\Mirror\MirrorService;
use App\Services\Values\PersonalValueSetService;
use App\Services\Values\ValueService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

/**
 * V2 · Saját értékek (24): where from (V2), the list (V2·b) and adding a
 * value (V2·c); the same component is the peer's T3 (24 · 24.6). Every
 * change saves the working copy; "Tovább" records the version.
 */
class OwnValues extends Component
{
    use InteractsWithMainDiagnosis;

    /** from · list · add */
    public string $screen = 'list';

    /**
     * @var list<array<string, mixed>>
     */
    public array $items = [];

    /**
     * The card being added or edited on V2·c.
     *
     * @var array<string, mixed>
     */
    public array $draft = [];

    public ?string $editingKey = null;

    public string $librarySearch = '';

    public function mount(Organization $organization, Diagnosis $diagnosis, PersonalValueSetService $sets): void
    {
        $this->bootDiagnosis($organization, $diagnosis);

        if (! $this->ensurePeerStarted()) {
            $this->redirect($this->overviewUrl(), navigate: false);

            return;
        }

        $this->items = $this->itemsOf($sets->forUser($this->user()));
        $this->screen = $this->items === [] ? 'from' : 'list';
        $this->resetDraft();
    }

    public function show(string $screen): void
    {
        if (in_array($screen, ['from', 'list', 'add'], true)) {
            $this->screen = $screen;
            $this->resetValidation();

            if ($screen === 'add') {
                $this->editingKey = null;
                $this->resetDraft();
            }
        }
    }

    /**
     * "Az Értéktükörből hozom" and "Új tükröd van · Átnézem" (24 · 24.5, question 4).
     */
    public function importMirror(PersonalValueSetService $sets): void
    {
        $this->authorizeOwnValues();

        $mirror = $sets->latestMirror($this->user());

        if ($mirror === null) {
            return;
        }

        $room = max(0, (int) config('diagnosis.own_values.max') - count($this->items));
        $this->items = [...$this->items, ...$sets->itemsFromMirror($mirror, array_values(array_filter(array_column($this->items, 'value_id'))), $room)];

        $set = $sets->forUser($this->user());
        $set->update(['imported_mirror_session_id' => $mirror->id]);
        $this->persist($sets);
        $this->screen = 'list';
    }

    public function pickFromLibrary(int $valueId, PersonalValueSetService $sets): void
    {
        $value = $sets->libraryFor($this->user(), $this->organization, '', array_values(array_filter(array_column($this->items, 'value_id'))))
            ->firstWhere('id', $valueId)
            ?? Value::query()->availableToUser($this->user(), $this->organization)->whereKey($valueId)->first();

        if ($value === null) {
            return;
        }

        $this->draft = [...$sets->itemFromValue($value->load('latestVersion')), 'key' => $this->draft['key'] ?? null];
        $this->librarySearch = '';
    }

    public function clearPick(): void
    {
        if ($this->editingKey === null) {
            $this->resetDraft();
        }
    }

    public function edit(string $key): void
    {
        $item = collect($this->items)->firstWhere('key', $key);

        if ($item !== null) {
            $this->draft = $item;
            $this->editingKey = $key;
            $this->screen = 'add';
            $this->resetValidation();
        }
    }

    /**
     * "Hozzáadom": the name and "Mit jelent számodra?" are required; for a
     * private person also both ends of the scale (24, question 2).
     */
    public function addDraft(PersonalValueSetService $sets, ValueService $values): void
    {
        $this->authorizeOwnValues();
        $this->resetErrorBag();

        $isEditing = $this->editingKey !== null;

        if (! $isEditing && count($this->items) >= (int) config('diagnosis.own_values.max')) {
            throw ValidationException::withMessages(['draft.name' => __('diagnosis.own_values.too_many', ['max' => config('diagnosis.own_values.max')])]);
        }

        $individual = $this->isIndividual();

        $this->validate([
            'draft.name' => ['required', 'string', 'min:2', 'max:255'],
            'draft.meaning' => ['required', 'string', 'max:500'],
            'draft.non_meaning' => ['nullable', 'string', 'max:500'],
            'draft.low_example' => [Rule::requiredIf($individual), 'nullable', 'string', 'max:500'],
            'draft.high_example' => [Rule::requiredIf($individual), 'nullable', 'string', 'max:500'],
        ], attributes: $this->attributeNames());

        $draft = $this->draft;

        // A typed name that is in the Értéktár becomes that value; names are not matched to scores (24 · 24.4).
        if (($draft['value_id'] ?? null) === null) {
            $existing = $values->findByName((string) $draft['name']);

            if ($existing !== null) {
                // A name taken by a value the user may not choose (e.g. another
                // organization's, not reviewed yet) is tied to it with the user's
                // own words; nothing of that value is shown.
                $available = Value::query()->availableToUser($this->user(), $this->organization)->whereKey($existing->id)->exists();

                $draft['value_id'] = $existing->id;
                $draft['source_version_id'] = $available ? $existing->latestVersion?->id : null;
                $draft['source'] = $available ? PersonalValueSetItem::SOURCE_LIBRARY : PersonalValueSetItem::SOURCE_NEW;
            }
        }

        $duplicate = collect($this->items)->contains(fn (array $item) => $item['key'] !== $this->editingKey
            && ((($draft['value_id'] ?? null) !== null && $item['value_id'] === $draft['value_id'])
                || mb_strtolower(trim((string) $item['name'])) === mb_strtolower(trim((string) $draft['name']))));

        if ($duplicate) {
            throw ValidationException::withMessages(['draft.name' => __('diagnosis.own_values.duplicate')]);
        }

        if ($isEditing) {
            $this->items = array_map(fn (array $item) => $item['key'] === $this->editingKey ? [...$item, ...$draft] : $item, $this->items);
        } else {
            $this->items[] = [...$draft, 'key' => (string) Str::uuid(), 'source' => $draft['source'] ?? PersonalValueSetItem::SOURCE_NEW];
        }

        $this->persist($sets);
        $this->editingKey = null;
        $this->resetDraft();
        $this->screen = 'list';
    }

    public function remove(string $key, PersonalValueSetService $sets): void
    {
        $this->authorizeOwnValues();

        $this->items = array_values(array_filter($this->items, fn (array $item) => $item['key'] !== $key));
        $this->persist($sets);
    }

    /**
     * "Tovább": the list becomes the own values, a version is recorded; a
     * private person's organization values follow (08).
     */
    public function next(PersonalValueSetService $sets): void
    {
        $this->authorizeOwnValues();

        $individual = $this->isIndividual();
        $min = (int) config($individual ? 'diagnosis.own_values.individual_min' : 'diagnosis.own_values.min');
        $max = (int) config('diagnosis.own_values.max');

        $this->validate([
            'items' => ['array', 'min:'.$min, 'max:'.$max],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.meaning' => ['required', 'string', 'max:500'],
            'items.*.low_example' => [Rule::requiredIf($individual), 'nullable', 'string', 'max:500'],
            'items.*.high_example' => [Rule::requiredIf($individual), 'nullable', 'string', 'max:500'],
        ], [
            'items.min' => trans_choice('diagnosis.own_values.too_few', $min, ['min' => $min]),
            'items.max' => __('diagnosis.own_values.too_many', ['max' => $max]),
        ], $this->attributeNames());

        $set = $sets->forUser($this->user());
        $sets->save($set, $this->items, recordVersion: true);

        if ($individual && $this->isLeader()) {
            $sets->syncOrganization($set->refresh(), $this->organization);
        }

        $this->redirect($this->nextUrl(DiagnosisStep::OwnValues), navigate: false);
    }

    public function render(PersonalValueSetService $sets, MirrorService $mirrors): View
    {
        $set = $sets->forUser($this->user());
        $mirror = $sets->latestMirror($this->user());
        $occurrences = $sets->occurrences($this->user(), array_values(array_filter(array_column($this->items, 'value_id'))));

        return view('livewire.client.diagnosis.own-values', [
            'editable' => $this->main()->ownValuesEditable(),
            'mirror' => $mirror,
            'mirrorCount' => $mirror === null ? 0 : $mirrors->groups($mirror)->reject(fn ($group) => $group->excluded)->count(),
            'newMirror' => $mirror !== null && $set->imported_mirror_session_id !== null && $set->imported_mirror_session_id !== $mirror->id ? $mirror : null,
            'occurrences' => $occurrences,
            'states' => collect($this->items)->mapWithKeys(fn (array $item) => [
                $item['key'] => $sets->state($occurrences['counts'][$item['value_id'] ?? 0] ?? 0, $occurrences['total']),
            ])->all(),
            'library' => $this->screen === 'add' && ($this->draft['value_id'] ?? null) === null
                ? $sets->libraryFor($this->user(), $this->organization, $this->librarySearch, array_values(array_filter(array_column($this->items, 'value_id'))))
                : collect(),
            'isIndividual' => $this->isIndividual(),
            'max' => (int) config('diagnosis.own_values.max'),
            'recordUrl' => route('client.mirror.record', ['organization' => $this->organization, 'diagnozis' => $this->diagnosis->id]),
            'canRecord' => ! $this->isImpersonating(),
        ])->layout('layouts::client', $this->stepLayout(DiagnosisStep::OwnValues))->title(DiagnosisStep::OwnValues->label());
    }

    private function persist(PersonalValueSetService $sets): void
    {
        $set = $sets->forUser($this->user());
        $sets->save($set, $this->items, recordVersion: false);
        $this->items = $this->itemsOf($set->refresh());
        $this->dispatch('saved');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function itemsOf(PersonalValueSet $set): array
    {
        return $set->items()->get()->map(fn (PersonalValueSetItem $item) => [
            'key' => $item->key,
            'value_id' => $item->value_id,
            'source_version_id' => $item->source_version_id,
            'name' => $item->name,
            'meaning' => (string) $item->meaning,
            'non_meaning' => (string) $item->non_meaning,
            'low_example' => (string) $item->low_example,
            'high_example' => (string) $item->high_example,
            'example' => (string) $item->example,
            'source' => $item->source,
            'quotes' => $item->quotes,
        ])->all();
    }

    private function resetDraft(): void
    {
        $this->draft = [
            'key' => null,
            'value_id' => null,
            'source_version_id' => null,
            'name' => '',
            'meaning' => '',
            'non_meaning' => '',
            'low_example' => '',
            'high_example' => '',
            'example' => '',
            'source' => PersonalValueSetItem::SOURCE_NEW,
            'quotes' => null,
        ];
    }

    private function isIndividual(): bool
    {
        return $this->main()->subject === MainDiagnosisSubject::Individual;
    }

    /**
     * The own values can change until publishing (21 · 21.3).
     */
    private function authorizeOwnValues(): void
    {
        abort_unless($this->main()->ownValuesEditable() && $this->ensurePeerStarted(), 403, __('diagnosis.locked'));
    }

    /**
     * @return array<string, string>
     */
    private function attributeNames(): array
    {
        return [
            'items' => __('diagnosis.own_values.values'),
            'draft.name' => __('diagnosis.own_values.fields.name'),
            'items.*.name' => __('diagnosis.own_values.fields.name'),
            'draft.meaning' => __('diagnosis.own_values.fields.meaning'),
            'items.*.meaning' => __('diagnosis.own_values.fields.meaning'),
            'draft.non_meaning' => __('diagnosis.own_values.fields.non_meaning'),
            'draft.low_example' => __('diagnosis.own_values.fields.low_example'),
            'items.*.low_example' => __('diagnosis.own_values.fields.low_example'),
            'draft.high_example' => __('diagnosis.own_values.fields.high_example'),
            'items.*.high_example' => __('diagnosis.own_values.fields.high_example'),
        ];
    }
}
