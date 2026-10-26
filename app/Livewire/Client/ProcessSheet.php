<?php

namespace App\Livewire\Client;

use App\Enums\MembershipStatus;
use App\Enums\MetricRhythm;
use App\Enums\ParticipantRole;
use App\Models\Diagnosis;
use App\Models\MainDiagnosis;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Process;
use App\Models\ProcessMetric;
use App\Models\ProcessParticipant;
use App\Models\User;
use App\Services\Processes\ProcessService;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * V6·e · the data sheet of a process (11 · 11.3): responsible, participants,
 * teams, own measures. Opens from the diagnosis (V6·d, V6·f) and from
 * Tárak → Folyamatok (11 · 11.5).
 */
class ProcessSheet extends Component
{
    public Organization $organization;

    public Process $process;

    public ?Diagnosis $diagnosis = null;

    public string $description = '';

    public ?int $responsibleId = null;

    public ?int $newParticipantId = null;

    /**
     * @var list<int>
     */
    public array $teamIds = [];

    /**
     * @var array{name: string, unit: string, rhythm: string, target: string, description: string}
     */
    public array $metric = ['name' => '', 'unit' => '', 'rhythm' => 'monthly', 'target' => '', 'description' => ''];

    public ?int $recordingMetricId = null;

    public string $metricValue = '';

    public string $metricDate = '';

    public function mount(Organization $organization, Process $process, ?Diagnosis $diagnosis = null): void
    {
        abort_unless($process->organization_id === $organization->id, 404);

        $this->organization = $organization;
        $this->process = $process;
        $this->diagnosis = $diagnosis?->exists ? $diagnosis : null;

        abort_unless($this->canView(), 403);

        $this->description = (string) $process->description;
        $this->responsibleId = $process->responsible_user_id;
        $this->teamIds = $process->teams()->pluck('teams.id')->all();
        $this->metricDate = today()->toDateString();
    }

    public function updatedDescription(): void
    {
        $processes = app(ProcessService::class);
        $this->authorizeEdit();
        $this->validate(['description' => ['nullable', 'string', 'max:2000']], attributes: ['description' => __('processes.list.fields.description')]);

        $processes->setDescription($this->process, $this->description);
        $this->dispatch('saved');
    }

    public function updatedResponsibleId(): void
    {
        $processes = app(ProcessService::class);
        $this->authorizeEdit();
        $this->validate(['responsibleId' => ['nullable', Rule::in($this->members()->pluck('id'))]]);

        $processes->setResponsible($this->process, $this->responsibleId === null ? null : User::query()->find($this->responsibleId));
        $this->dispatch('saved');
    }

    public function updatedTeamIds(): void
    {
        $processes = app(ProcessService::class);
        $this->authorizeEdit();

        $processes->syncTeams($this->process, array_map('intval', $this->teamIds));
        $this->dispatch('saved');
    }

    public function addParticipant(ProcessService $processes): void
    {
        $this->authorizeEdit();
        $this->validate(['newParticipantId' => ['required', Rule::in($this->members()->pluck('id'))]], attributes: [
            'newParticipantId' => __('processes.sheet.add_participant'),
        ]);

        $processes->addParticipant($this->process, User::query()->findOrFail($this->newParticipantId));
        $this->newParticipantId = null;
    }

    public function removeParticipant(int $participantId, ProcessService $processes): void
    {
        $this->authorizeEdit();

        $processes->removeParticipant($this->process, ProcessParticipant::query()->findOrFail($participantId));
    }

    public function addMetric(ProcessService $processes): void
    {
        $this->authorizeEdit();
        $this->validate([
            'metric.name' => ['required', 'string', 'max:255'],
            'metric.unit' => ['nullable', 'string', 'max:50'],
            'metric.rhythm' => ['required', Rule::enum(MetricRhythm::class)],
            'metric.target' => ['nullable', 'string', 'max:100'],
            'metric.description' => ['nullable', 'string', 'max:1000'],
        ], attributes: collect(['name', 'unit', 'rhythm', 'target', 'description'])
            ->mapWithKeys(fn (string $field) => ['metric.'.$field => __('processes.sheet.metric_fields.'.$field)])->all());

        $processes->addMetric($this->process, $this->metric);
        $this->reset('metric');
    }

    public function openRecord(int $metricId): void
    {
        $this->recordingMetricId = $this->metricOf($metricId)->id;
        $this->metricValue = '';
    }

    /**
     * The responsible, the leader or an admin records a value (11 · 11.3).
     */
    public function recordValue(ProcessService $processes): void
    {
        abort_unless($this->canRecordValues(), 403);
        $this->validate([
            'metricValue' => ['required', 'numeric', 'between:-999999999,999999999'],
            'metricDate' => ['required', 'date', 'before_or_equal:today'],
        ], attributes: ['metricValue' => __('processes.sheet.value'), 'metricDate' => __('processes.sheet.measured_on')]);

        $processes->recordMetricValue($this->metricOf((int) $this->recordingMetricId), (float) $this->metricValue, $this->metricDate, auth('web')->user());
        $this->reset(['recordingMetricId', 'metricValue']);
    }

    public function render(): View
    {
        $this->process->refresh()->load(['participants.user', 'metrics.values', 'renames.creator', 'parent']);

        return view('livewire.client.process-sheet', [
            'editable' => $this->canEdit(),
            'canRecord' => $this->canRecordValues(),
            'members' => $this->members(),
            'teams' => $this->organization->teams()->orderBy('name')->get(['id', 'name']),
            'occasionCount' => $this->process->occasions()->count(),
            'backUrl' => $this->backUrl(),
        ])->layout('layouts::client', [
            'title' => $this->process->name,
            'subtitle' => __('processes.sheet.lead'),
            'back' => $this->backUrl(),
            'backLabel' => $this->diagnosis ? __('processes.list.title') : __('processes.library.title'),
            'color' => 'cream',
        ])->title($this->process->name);
    }

    private function backUrl(): string
    {
        return $this->diagnosis
            ? route('client.diagnosis.process-list', [$this->organization, $this->diagnosis])
            : route('client.processes', $this->organization);
    }

    /**
     * The leader of the diagnosis while it takes input (11); in the library the
     * organization's leaders and the leaders of a running diagnosis (11 · 11.5).
     */
    private function canEdit(): bool
    {
        $main = $this->main();

        if ($main !== null) {
            return $main->isLeader(auth('web')->user()) && $main->acceptsInput();
        }

        return $this->isOrganizationLeader() || $this->leadsActiveDiagnosis();
    }

    private function canView(): bool
    {
        $main = $this->main();

        if ($main !== null) {
            return $main->diagnosis->organization_id === $this->organization->id
                && $main->participantFor(auth('web')->user(), ParticipantRole::Leader) !== null;
        }

        return $this->isOrganizationLeader() || $this->leadsActiveDiagnosis();
    }

    private function canRecordValues(): bool
    {
        return $this->canEdit() || $this->process->responsible_user_id === auth('web')->id();
    }

    private function authorizeEdit(): void
    {
        abort_unless($this->canEdit(), 403, __('processes.locked'));
    }

    private function main(): ?MainDiagnosis
    {
        return $this->diagnosis?->main;
    }

    private function isOrganizationLeader(): bool
    {
        return Membership::query()
            ->whereBelongsTo($this->organization)
            ->where('user_id', auth('web')->id())
            ->where('is_organization_leader', true)
            ->exists();
    }

    private function leadsActiveDiagnosis(): bool
    {
        return MainDiagnosis::query()
            ->acceptingInput()
            ->withParticipant(auth('web')->user(), ParticipantRole::Leader)
            ->whereHas('diagnosis', fn ($query) => $query->where('organization_id', $this->organization->id))
            ->exists();
    }

    /**
     * @return Collection<int, User>
     */
    private function members(): Collection
    {
        return User::query()
            ->whereHas('memberships', fn ($query) => $query
                ->where('organization_id', $this->organization->id)
                ->where('status', '!=', MembershipStatus::Inactive))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    private function metricOf(int $id): ProcessMetric
    {
        return $this->process->metrics()->findOrFail($id);
    }
}
