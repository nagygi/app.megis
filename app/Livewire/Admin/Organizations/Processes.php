<?php

namespace App\Livewire\Admin\Organizations;

use App\Enums\MaterialContext;
use App\Enums\MembershipStatus;
use App\Enums\ProcessConfidence;
use App\Enums\ProcessKind;
use App\Enums\ProcessRhythm;
use App\Exceptions\DiagnosisException;
use App\Models\CalendarConnection;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\Organization;
use App\Models\Process;
use App\Models\User;
use App\Services\Processes\OccasionService;
use App\Services\Processes\ProcessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * The organization's "Folyamatok" tab (11 · 11.6): the same rules as for
 * the leader, the calendar row, uncertain and other rows apart for review,
 * "A mégis javaslata" for V6·f, and the materials waiting to be classified (12 · 12.6).
 */
class Processes extends Component
{
    public Organization $organization;

    public string $recommendation = '';

    public ?int $editingId = null;

    /**
     * @var array{name: string, kind: string, accepted: bool, responsible_user_id: ?int, parent_id: ?int}
     */
    public array $edit = ['name' => '', 'kind' => 'routine', 'accepted' => false, 'responsible_user_id' => null, 'parent_id' => null];

    public string $newName = '';

    public ?string $newRhythm = null;

    public ?int $newParentId = null;

    /**
     * "Besorolásra vár": material uuid => process id and date (12 · 12.6).
     *
     * @var array<string, array{process_id: ?string, date: string}>
     */
    public array $classify = [];

    public function mount(Organization $organization): void
    {
        Gate::authorize('update', $organization);

        $this->organization = $organization;
        $this->recommendation = (string) $organization->process_recommendation;
    }

    public function saveRecommendation(): void
    {
        Gate::authorize('update', $this->organization);
        $this->validate(['recommendation' => ['nullable', 'string', 'max:255']], attributes: ['recommendation' => __('admin.processes.recommendation')]);

        $this->organization->update(['process_recommendation' => filled($this->recommendation) ? trim($this->recommendation) : null]);
        session()->flash('status', __('admin.common.saved'));
    }

    public function startEdit(int $processId): void
    {
        $process = $this->process($processId);
        $this->editingId = $process->id;
        $this->edit = [
            'name' => $process->name,
            'kind' => $process->kind->value,
            'accepted' => $process->isAccepted(),
            'responsible_user_id' => $process->responsible_user_id,
            'parent_id' => $process->parent_id,
        ];
        $this->resetValidation();
    }

    public function saveEdit(ProcessService $processes): void
    {
        Gate::authorize('update', $this->organization);
        $process = $this->process((int) $this->editingId);

        $this->validate([
            'edit.name' => ['required', 'string', 'min:2', 'max:255'],
            'edit.kind' => ['required', Rule::enum(ProcessKind::class)],
            'edit.responsible_user_id' => ['nullable', Rule::in($this->members()->pluck('id'))],
            'edit.parent_id' => ['nullable', Rule::in(Process::query()->whereBelongsTo($this->organization)->whereNull('parent_id')->whereKeyNot($process->id)->pluck('id'))],
        ], attributes: [
            'edit.name' => __('processes.list.fields.name'),
            'edit.parent_id' => __('processes.list.fields.parent'),
        ]);

        try {
            $processes->rename($process, $this->edit['name'], auth('admin')->user());
            $processes->setKind($process, ProcessKind::from($this->edit['kind']));
            $processes->setResponsible($process, $this->edit['responsible_user_id'] ? User::query()->find($this->edit['responsible_user_id']) : null);

            // A process with subprocesses stays on the top level (11 · 11.2).
            if ($process->children()->doesntExist()) {
                $process->update(['parent_id' => $this->edit['parent_id'] ?: null]);
            }

            $processes->setAccepted($process->refresh(), (bool) $this->edit['accepted'], $this->runningDiagnosis());
        } catch (DiagnosisException $exception) {
            $this->addError('edit.accepted', $exception->getMessage());

            return;
        }

        $this->editingId = null;
        session()->flash('status', __('admin.common.saved'));
    }

    public function addProcess(ProcessService $processes): void
    {
        Gate::authorize('update', $this->organization);
        $this->validate([
            'newName' => ['required', 'string', 'min:2', 'max:255'],
            'newRhythm' => ['nullable', Rule::enum(ProcessRhythm::class)],
            'newParentId' => ['nullable', Rule::in(Process::query()->whereBelongsTo($this->organization)->whereNull('parent_id')->pluck('id'))],
        ], attributes: ['newName' => __('processes.list.fields.name')]);

        $processes->addManual(
            $this->organization,
            $this->newName,
            filled($this->newRhythm) ? ProcessRhythm::from($this->newRhythm) : null,
            $this->newParentId ? $this->process($this->newParentId) : null,
        );

        $this->reset(['newName', 'newRhythm', 'newParentId']);
    }

    /**
     * An "Egyikhez sem" material goes to a process and a date (12 · 12.6).
     */
    public function classifyMaterial(string $uuid, OccasionService $occasions): void
    {
        Gate::authorize('update', $this->organization);
        $material = $this->unclassifiedMaterial($uuid);

        $this->validate([
            "classify.{$uuid}.process_id" => ['required', Rule::in(Process::query()->whereBelongsTo($this->organization)->pluck('id')->map(fn ($id) => (string) $id))],
            "classify.{$uuid}.date" => ['required', 'date', 'before_or_equal:today'],
        ], attributes: [
            "classify.{$uuid}.process_id" => __('admin.occasions.process'),
            "classify.{$uuid}.date" => __('admin.occasions.date'),
        ]);

        $occasions->classify($material, $this->process((int) $this->classify[$uuid]['process_id']), $this->classify[$uuid]['date'], auth('admin')->user());
        unset($this->classify[$uuid]);
        session()->flash('status', __('admin.occasions.classified'));
    }

    /**
     * "Egyéb": processed and kept as a trace, without a process diagnosis (13 · 13.2).
     */
    public function markOther(string $uuid, OccasionService $occasions): void
    {
        Gate::authorize('update', $this->organization);

        $occasions->markOther($this->unclassifiedMaterial($uuid));
        unset($this->classify[$uuid]);
        session()->flash('status', __('admin.occasions.marked_other'));
    }

    public function render(): View
    {
        $processes = Process::query()
            ->whereBelongsTo($this->organization)
            ->with(['parent', 'responsible', 'renames'])
            ->withCount('occasions')
            ->orderByRaw('coalesce(parent_id, id)')
            ->orderBy('parent_id')
            ->orderBy('id')
            ->get();

        $isForReview = fn (Process $process) => $process->kind === ProcessKind::OneOff
            || $process->unclear
            || in_array($process->confidence, [ProcessConfidence::Medium, ProcessConfidence::Low], true);

        $unclassified = $this->unclassifiedMaterials()->with(['user', 'creator'])->get();

        foreach ($unclassified as $material) {
            $this->classify[$material->uuid] ??= ['process_id' => null, 'date' => $material->placed_at->toDateString()];
        }

        return view('livewire.admin.organizations.processes', [
            'unclassified' => $unclassified,
            'main' => $processes->reject($isForReview)->values(),
            'review' => $processes->filter($isForReview)->values(),
            'connections' => CalendarConnection::query()->whereBelongsTo($this->organization)->with('user')->latest('id')->get(),
            'members' => $this->members(),
            'parents' => $processes->whereNull('parent_id')->values(),
        ])->layout('layouts::admin', ['organization' => $this->organization])->title($this->organization->name);
    }

    /**
     * @return Builder<Material>
     */
    private function unclassifiedMaterials(): Builder
    {
        return Material::query()
            ->where('context', MaterialContext::Occasion)
            ->whereNotNull('placed_at')
            ->whereNull('process_occasion_id')
            ->whereNull('classified_other_at')
            ->whereHas('parentDiagnosis', fn ($query) => $query->where('organization_id', $this->organization->id))
            ->oldest('placed_at');
    }

    private function unclassifiedMaterial(string $uuid): Material
    {
        return $this->unclassifiedMaterials()->where('uuid', $uuid)->firstOrFail();
    }

    private function process(int $id): Process
    {
        return Process::query()->whereBelongsTo($this->organization)->findOrFail($id);
    }

    /**
     * @return Collection<int, User>
     */
    private function members(): Collection
    {
        return User::query()
            ->whereHas('memberships', fn ($query) => $query->where('organization_id', $this->organization->id)->where('status', '!=', MembershipStatus::Inactive))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    private function runningDiagnosis(): ?MainDiagnosis
    {
        return MainDiagnosis::query()
            ->acceptingInput()
            ->whereHas('diagnosis', fn ($query) => $query->where('organization_id', $this->organization->id))
            ->latest('id')
            ->first();
    }
}
