<?php

namespace App\Livewire\Client;

use App\Enums\ParticipantRole;
use App\Models\Organization;
use App\Models\PersonalValueSet;
use App\Models\Value;
use App\Models\ValueCategory;
use App\Services\Auth\ImpersonationService;
use App\Services\MainDiagnoses\MainDiagnosisService;
use App\Services\Report\ReportService;
use App\Services\Values\PersonalValueSetService;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Tárak → Értéktár (30): the organization's values in its own words with
 * the score of the viewer's latest report, the own values with their state,
 * and the mégis Értéktár to browse and take from.
 */
class ValueLibrary extends Component
{
    public Organization $organization;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'kategoria', except: '')]
    public string $category = '';

    public ?int $openValueId = null;

    public ?string $openItemKey = null;

    public function mount(Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function toggleValue(int $valueId): void
    {
        $this->openValueId = $this->openValueId === $valueId ? null : $valueId;
    }

    public function toggleItem(string $key): void
    {
        $this->openItemKey = $this->openItemKey === $key ? null : $key;
    }

    /**
     * "Hozzáadom a sajátjaimhoz" (30 · 30.4): the card of the value joins the own list (24 · 24.3).
     */
    public function addToOwn(int $valueId, PersonalValueSetService $ownValues, ImpersonationService $impersonation): void
    {
        abort_if($impersonation->isImpersonating(), 403, __('impersonation.forbidden'));
        $value = Value::query()->availableTo(null)->with('latestVersion')->findOrFail($valueId);
        $set = $ownValues->forUser(auth('web')->user());
        $items = $set->items()->orderBy('position')->get();

        if ($items->contains('value_id', $value->id)) {
            session()->flash('status', __('client.value_library.already_own', ['name' => $value->name]));

            return;
        }

        if ($items->count() >= (int) config('diagnosis.own_values.max')) {
            session()->flash('error', __('client.value_library.own_full', ['max' => config('diagnosis.own_values.max')]));

            return;
        }

        $ownValues->save($set, [...$items->map(fn ($item) => $item->only(['key', 'value_id', 'source_version_id', 'name', 'meaning', 'non_meaning', 'low_example', 'high_example', 'example', 'source', 'quotes']))->all(), $ownValues->itemFromValue($value)], recordVersion: true);
        session()->flash('status', __('client.value_library.added_to_own', ['name' => $value->name]));
    }

    public function render(): View
    {
        $valueSet = $this->organization->valueSet()->with(['items', 'latestVersion'])->first();

        // 30.4: active, reviewed values only, read-only.
        $library = Value::query()
            ->availableTo(null)
            ->with('category')
            ->when(trim($this->search) !== '', fn ($query) => $query->whereLike('search_text', '%'.mb_strtolower(trim($this->search)).'%'))
            ->when($this->category !== '', fn ($query) => $query->where('value_category_id', $this->category))
            ->orderBy('name')
            ->get();

        $user = auth('web')->user();
        $report = app(ReportService::class)->reportsFor($user, $this->organization)->first();
        $snapshot = $report === null ? null : app(ReportService::class)->snapshot($report);
        $ownSet = PersonalValueSet::query()->where('user_id', $user->id)->with('items')->first();
        $ownValues = app(PersonalValueSetService::class);
        $occurrences = $ownSet === null ? ['total' => 0, 'counts' => []] : $ownValues->occurrences($user, $ownSet->items->pluck('value_id')->filter()->values()->all());
        // "Szerkesztem" opens the own values step of a diagnosis that still takes input (24).
        $editable = app(MainDiagnosisService::class)->acceptingInputFor($user, $this->organization)->first();
        $canEditOwn = $editable !== null && ($editable->isLeader($user) || $editable->participantFor($user, ParticipantRole::Peer)?->started_at !== null);
        $isOrganizationLeader = app(MainDiagnosisService::class)->isOrganizationLeader($this->organization, $user);

        return view('livewire.client.value-library', [
            // The score of the viewer's latest published report, team view (30, question 1).
            'scores' => $snapshot === null ? collect() : collect($snapshot->data['chapters']['values']['rows'] ?? [])->keyBy(fn (array $row) => mb_strtolower(trim($row['label']))),
            'report' => $report,
            'olderDefinition' => $report !== null && $valueSet !== null && $report->value_set_version_id !== $valueSet->latestVersion?->id,
            'ownItems' => $this->organization->isIndividual() ? collect() : ($ownSet?->items->sortBy('position')->values() ?? collect()),
            'ownState' => fn ($item) => $ownValues->state($occurrences['counts'][$item->value_id] ?? 0, $occurrences['total']),
            'ownEditUrl' => $canEditOwn ? route('client.diagnosis.sajat-ertekek', [$this->organization, $editable->diagnosis]) : null,
            'organizationEditUrl' => $isOrganizationLeader && $editable !== null && $editable->isLeader($user) && ! $this->organization->isIndividual()
                ? route('client.diagnosis.szervezet-ertekei', [$this->organization, $editable->diagnosis])
                : null,
            'valueSet' => $valueSet !== null && $valueSet->items->isNotEmpty() ? $valueSet : null,
            'library' => $library,
            'libraryCount' => Value::query()->availableTo(null)->count(),
            'categories' => ValueCategory::query()->whereHas('values', fn ($query) => $query->availableTo(null))->orderBy('name')->get(['id', 'name']),
        ])->layout('layouts::client', ['title' => __('client.value_library.title'), 'subtitle' => __('client.value_library.question')]);
    }
}
