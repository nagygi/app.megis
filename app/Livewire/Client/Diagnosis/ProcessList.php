<?php

namespace App\Livewire\Client\Diagnosis;

use App\Enums\DiagnosisStep;
use App\Enums\ProcessKind;
use App\Enums\ProcessRhythm;
use App\Exceptions\DiagnosisException;
use App\Livewire\Client\Diagnosis\Concerns\InteractsWithMainDiagnosis;
use App\Livewire\Concerns\WithDictation;
use App\Livewire\Concerns\WithProcessImport;
use App\Models\CalendarConnection;
use App\Models\Diagnosis;
use App\Models\Organization;
use App\Models\Process;
use App\Services\Calendar\CalendarDetector;
use App\Services\Processes\ProcessService;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * V6·d · Detektált folyamatok (11 · 11.2): a machine suggestion to approve,
 * in four groups. Rename, type, subprocess, accept; add by hand, or several
 * at once from a written or spoken text.
 */
class ProcessList extends Component
{
    use InteractsWithMainDiagnosis, WithDictation, WithFileUploads, WithProcessImport;

    public ?int $renamingId = null;

    public string $renameValue = '';

    public ?int $addingSubTo = null;

    public string $subName = '';

    public string $name = '';

    public ?string $rhythm = null;

    public ?int $parentId = null;

    public string $description = '';

    public function mount(Organization $organization, Diagnosis $diagnosis): void
    {
        $this->bootDiagnosis($organization, $diagnosis);
        abort_unless($this->isLeader(), 403);
    }

    public function startRename(int $processId): void
    {
        $process = $this->process($processId);
        $this->renamingId = $process->id;
        $this->renameValue = $process->name;
    }

    public function saveRename(ProcessService $processes): void
    {
        $this->authorizeInput();
        $this->validate(['renameValue' => ['required', 'string', 'min:2', 'max:255']], attributes: ['renameValue' => __('processes.list.fields.name')]);

        $processes->rename($this->process((int) $this->renamingId), $this->renameValue, $this->user());
        $this->reset(['renamingId', 'renameValue']);
    }

    public function setKind(int $processId, string $kind, ProcessService $processes): void
    {
        $this->authorizeInput();

        $processes->setKind($this->process($processId), ProcessKind::from($kind));
    }

    public function toggleAccept(int $processId, ProcessService $processes): void
    {
        $this->authorizeInput();
        $process = $this->process($processId);

        try {
            $processes->setAccepted($process, ! $process->isAccepted(), $this->main());
        } catch (DiagnosisException $exception) {
            $this->addError('accept', $exception->getMessage());
        }
    }

    public function openSub(int $processId): void
    {
        $this->addingSubTo = $this->process($processId)->id;
        $this->subName = '';
    }

    public function addSub(ProcessService $processes): void
    {
        $this->authorizeInput();
        $this->validate(['subName' => ['required', 'string', 'min:2', 'max:255']], attributes: ['subName' => __('processes.list.sub_name')]);

        try {
            $processes->addManual($this->organization, $this->subName, null, $this->process((int) $this->addingSubTo));
        } catch (DiagnosisException $exception) {
            $this->addError('subName', $exception->getMessage());

            return;
        }

        $this->reset(['addingSubTo', 'subName']);
    }

    /**
     * "Folyamat vagy alfolyamat hozzáadása": "nincs a naptárban · 0 alkalom" (11 · 11.2).
     */
    public function addManual(ProcessService $processes): void
    {
        $this->authorizeInput();

        $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'rhythm' => ['nullable', Rule::enum(ProcessRhythm::class)],
            'parentId' => ['nullable', Rule::in(Process::query()->whereBelongsTo($this->organization)->whereNull('parent_id')->pluck('id'))],
            'description' => ['nullable', 'string', 'max:2000'],
        ], attributes: [
            'name' => __('processes.list.fields.name'),
            'rhythm' => __('processes.list.fields.rhythm'),
            'parentId' => __('processes.list.fields.parent'),
            'description' => __('processes.list.fields.description'),
        ]);

        $processes->addManual(
            $this->organization,
            $this->name,
            $this->rhythm === null || $this->rhythm === '' ? null : ProcessRhythm::from($this->rhythm),
            $this->parentId === null ? null : $this->process($this->parentId),
            $this->description,
        );

        $this->reset(['name', 'rhythm', 'parentId', 'description']);
    }

    public function acceptAll(ProcessService $processes): void
    {
        $this->authorizeInput();

        $processes->acceptAll($this->organization, $this->main());
    }

    /**
     * 10 · 10.4: the calendar pattern is the same as a process from the conversation.
     */
    public function merge(int $processId, CalendarDetector $detector): void
    {
        $this->authorizeInput();
        $process = $this->process($processId);

        if ($process->merge_candidate_id !== null) {
            $detector->merge($process->mergeCandidate, $process);
            session()->flash('status', __('processes.list.merged'));
        }
    }

    public function dismissMerge(int $processId): void
    {
        $this->authorizeInput();

        $this->process($processId)->update(['merge_candidate_id' => null]);
    }

    public function render(ProcessService $processes): View
    {
        $groups = $processes->groups($this->organization);
        $connection = CalendarConnection::query()->whereBelongsTo($this->organization)->where('user_id', $this->user()->id)->latest('id')->first();
        $unaccepted = Process::query()->whereBelongsTo($this->organization)->where('kind', ProcessKind::Routine)->whereNull('accepted_at')->count();

        return view('livewire.client.diagnosis.process-list', [
            'groups' => $groups,
            'connection' => $connection,
            'unaccepted' => $unaccepted,
            'room' => $processes->room($this->organization, $this->main()),
            'parents' => Process::query()->whereBelongsTo($this->organization)->whereNull('parent_id')->orderBy('name')->get(['id', 'name']),
            'goingOn' => Process::query()->whereBelongsTo($this->organization)->whereNull('parent_id')->where('kind', '!=', ProcessKind::OneOff)->withCount('children')->orderBy('id')->get(),
            'editable' => $this->acceptsInput(),
            'canDictate' => $this->canDictate('importText'),
            'sheetUrl' => fn (Process $process) => route('client.diagnosis.process', [$this->organization, $this->diagnosis, $process]),
        ])->layout('layouts::client', [
            ...$this->stepLayout(DiagnosisStep::Processes),
            'title' => __('processes.list.title'),
            'back' => route('client.diagnosis.folyamatok', [$this->organization, $this->diagnosis]),
            'backLabel' => DiagnosisStep::Processes->label(),
        ])->title(__('processes.list.title'));
    }

    protected function authorizeImport(): void
    {
        $this->authorizeInput();
    }

    protected function importOrganization(): Organization
    {
        return $this->organization;
    }

    protected function canDictate(string $field): bool
    {
        return $this->acceptsInput() && ! $this->isImpersonating() && $field === 'importText';
    }

    protected function dictationOrganization(): ?Organization
    {
        return $this->organization;
    }

    private function process(int $id): Process
    {
        return Process::query()->whereBelongsTo($this->organization)->findOrFail($id);
    }
}
