<?php

namespace App\Livewire\Admin\Diagnoses;

use App\Enums\DiagnosisLevel;
use App\Enums\MembershipStatus;
use App\Exceptions\DiagnosisException;
use App\Models\Diagnosis;
use App\Models\DiagnosisType;
use App\Models\Membership;
use App\Models\Organization;
use App\Services\Diagnoses\DiagnosisService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * New diagnosis and its basic data (04 · 4.3). The organization is chosen
 * once; the creator is set by the system.
 */
class Form extends Component
{
    public ?Diagnosis $diagnosis = null;

    public string $name = '';

    public string $description = '';

    public ?int $organization_id = null;

    public string $level = DiagnosisLevel::Organization->value;

    public ?int $team_id = null;

    public ?int $user_id = null;

    public ?int $process_id = null;

    public ?int $diagnosis_type_id = null;

    public string $diagnosed_on = '';

    public bool $isPeriod = false;

    public string $diagnosed_until = '';

    /**
     * The main diagnosis this one becomes a subdiagnosis of (17 · 17.4).
     */
    public ?int $parent_id = null;

    public function mount(?Diagnosis $diagnosis = null): void
    {
        if ($diagnosis?->exists) {
            Gate::authorize('update', $diagnosis);

            // A main diagnosis is set on A1 (17 · 17.2).
            if ($diagnosis->main !== null) {
                $this->redirectRoute('admin.main-diagnoses.edit', $diagnosis, navigate: false);

                return;
            }

            $this->diagnosis = $diagnosis;
            $this->name = $diagnosis->name;
            $this->description = (string) $diagnosis->description;
            $this->organization_id = $diagnosis->organization_id;
            $this->level = $diagnosis->level->value;
            $this->team_id = $diagnosis->team_id;
            $this->user_id = $diagnosis->user_id;
            $this->process_id = $diagnosis->process_id;
            $this->diagnosis_type_id = $diagnosis->diagnosis_type_id;
            $this->diagnosed_on = $diagnosis->diagnosed_on->toDateString();
            $this->isPeriod = $diagnosis->diagnosed_until !== null;
            $this->diagnosed_until = (string) $diagnosis->diagnosed_until?->toDateString();
            $this->parent_id = $diagnosis->parent_id;

            return;
        }

        Gate::authorize('create', Diagnosis::class);

        $this->diagnosed_on = today()->toDateString();
    }

    public function updatedOrganizationId(): void
    {
        $this->team_id = null;
        $this->user_id = null;
        $this->process_id = null;
        $this->parent_id = null;
    }

    public function save(DiagnosisService $diagnoses): void
    {
        $this->diagnosis === null
            ? Gate::authorize('create', Diagnosis::class)
            : Gate::authorize('update', $this->diagnosis);

        $organization = $this->selectedOrganization();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'organization_id' => ['required', Rule::exists('organizations', 'id')->whereNull('deactivated_at')],
            'level' => [
                'required',
                Rule::enum(DiagnosisLevel::class),
                Rule::when($organization?->isIndividual(), [Rule::notIn([DiagnosisLevel::Team->value])]),
            ],
            'team_id' => [
                Rule::requiredIf($this->level === DiagnosisLevel::Team->value),
                'nullable',
                Rule::exists('teams', 'id')->where('organization_id', $this->organization_id),
            ],
            'user_id' => [
                Rule::requiredIf($this->level === DiagnosisLevel::User->value),
                'nullable',
                Rule::exists('organization_user', 'user_id')->where('organization_id', $this->organization_id),
            ],
            'process_id' => [
                Rule::requiredIf($this->level === DiagnosisLevel::Process->value),
                'nullable',
                Rule::exists('processes', 'id')->where('organization_id', $this->organization_id),
            ],
            'diagnosis_type_id' => ['required', Rule::exists('diagnosis_types', 'id')->where(fn ($query) => $query->whereNull('key')->orWhere('key', '!=', DiagnosisType::MAIN))],
            'parent_id' => ['nullable', Rule::in($this->mainDiagnosisOptions()->pluck('id'))],
            'diagnosed_on' => ['required', 'date'],
            'diagnosed_until' => [Rule::requiredIf($this->isPeriod), 'nullable', 'date', 'after_or_equal:diagnosed_on'],
        ], [
            'level.not_in' => __('admin.diagnoses.level_team_individual'),
        ], [
            'name' => __('admin.diagnoses.name'),
            'organization_id' => __('admin.diagnoses.organization'),
            'level' => __('admin.diagnoses.level'),
            'team_id' => __('admin.diagnoses.team'),
            'user_id' => __('admin.diagnoses.user'),
            'process_id' => __('admin.diagnoses.process'),
            'diagnosis_type_id' => __('admin.diagnoses.type'),
            'diagnosed_on' => $this->isPeriod ? __('admin.diagnoses.date_from') : __('admin.diagnoses.date'),
            'diagnosed_until' => __('admin.diagnoses.date_until'),
            'parent_id' => __('admin.main_diagnoses.parent'),
        ]);

        $level = DiagnosisLevel::from($validated['level']);

        $attributes = [
            'name' => trim($validated['name']),
            'description' => filled($validated['description']) ? $validated['description'] : null,
            'level' => $level,
            'team_id' => $level === DiagnosisLevel::Team ? $validated['team_id'] : null,
            'user_id' => $level === DiagnosisLevel::User ? $validated['user_id'] : null,
            'process_id' => $level === DiagnosisLevel::Process ? $validated['process_id'] : null,
            'diagnosis_type_id' => $validated['diagnosis_type_id'],
            'diagnosed_on' => $validated['diagnosed_on'],
            'diagnosed_until' => $this->isPeriod ? $validated['diagnosed_until'] : null,
            'parent_id' => $validated['parent_id'] ?? null,
        ];

        if ($this->diagnosis === null) {
            $diagnosis = $diagnoses->create([...$attributes, 'organization_id' => $validated['organization_id']]);

            session()->flash('status', __('admin.diagnoses.created'));
            $this->redirectRoute('admin.diagnoses.show', $diagnosis, navigate: false);

            return;
        }

        try {
            $diagnoses->update($this->diagnosis, $attributes);
        } catch (DiagnosisException $exception) {
            session()->flash('error', $exception->getMessage());

            return;
        }

        session()->flash('status', __('admin.common.saved'));
        $this->redirectRoute('admin.diagnoses.show', $this->diagnosis, navigate: false);
    }

    public function render(): View
    {
        $organization = $this->selectedOrganization();
        $title = $this->diagnosis ? __('admin.diagnoses.edit_title') : __('admin.diagnoses.create_title');

        return view('livewire.admin.diagnoses.form', [
            'title' => $title,
            'organizations' => Organization::query()->whereNull('deactivated_at')->orderBy('name')->get(['id', 'name', 'slug', 'type']),
            'organization' => $organization,
            'levels' => collect(DiagnosisLevel::cases())
                ->reject(fn (DiagnosisLevel $level) => $level === DiagnosisLevel::Team && $organization?->isIndividual())
                ->values(),
            'teams' => $organization?->teams()->orderBy('name')->get(['id', 'name']) ?? collect(),
            'processes' => $organization?->processes()->orderBy('name')->get(['id', 'name']) ?? collect(),
            'members' => $organization === null ? collect() : Membership::query()
                ->where('organization_id', $organization->id)
                ->where('status', '!=', MembershipStatus::Inactive)
                ->with('user')
                ->get()
                ->sortBy(fn (Membership $membership) => $membership->user->name, SORT_NATURAL | SORT_FLAG_CASE),
            'types' => DiagnosisType::query()->whereNot('key', DiagnosisType::MAIN)->orWhereNull('key')->orderBy('name')->get(['id', 'name']),
            'mainDiagnoses' => $this->mainDiagnosisOptions(),
        ])->layout('layouts::admin')->title($title);
    }

    /**
     * Main diagnoses of the organization still taking input, and the current one (17 · 17.4).
     *
     * @return Collection<int, Diagnosis>
     */
    private function mainDiagnosisOptions(): Collection
    {
        if ($this->organization_id === null) {
            return collect();
        }

        return Diagnosis::query()
            ->where('organization_id', $this->organization_id)
            ->where(fn ($query) => $query
                ->whereHas('main', fn ($main) => $main->acceptingInput())
                ->when($this->diagnosis?->parent_id, fn ($current, $parentId) => $current->orWhere('id', $parentId)))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function selectedOrganization(): ?Organization
    {
        return $this->organization_id === null ? null : Organization::query()->find($this->organization_id);
    }
}
