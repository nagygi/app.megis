<?php

namespace App\Livewire\Admin\Organizations;

use App\Enums\MembershipStatus;
use App\Enums\ProcessConfidence;
use App\Enums\ProcessKind;
use App\Enums\ProcessRhythm;
use App\Exceptions\DiagnosisException;
use App\Models\CalendarConnection;
use App\Models\MainDiagnosis;
use App\Models\Organization;
use App\Models\Process;
use App\Models\User;
use App\Services\Processes\ProcessService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * The organization's "Folyamatok" tab (11 · 11.6): the same rules as for
 * the leader, the calendar row, uncertain and other rows apart for review,
 * and "A mégis javaslata" for V6·f.
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

        return view('livewire.admin.organizations.processes', [
            'main' => $processes->reject($isForReview)->values(),
            'review' => $processes->filter($isForReview)->values(),
            'connections' => CalendarConnection::query()->whereBelongsTo($this->organization)->with('user')->latest('id')->get(),
            'members' => $this->members(),
            'parents' => $processes->whereNull('parent_id')->values(),
        ])->layout('layouts::admin', ['organization' => $this->organization])->title($this->organization->name);
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
