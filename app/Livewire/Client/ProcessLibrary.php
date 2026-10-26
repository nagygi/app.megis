<?php

namespace App\Livewire\Client;

use App\Enums\ProcessKind;
use App\Enums\ProcessRhythm;
use App\Models\CalendarConnection;
use App\Models\Organization;
use App\Models\Process;
use App\Services\Processes\ProcessService;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Tárak → Folyamatok (11 · 11.5): the organization's routines, whether they
 * are in the calendar and measured; "+ Rutin" adds one by hand. The list a
 * published diagnosis recorded does not change, the library can.
 */
class ProcessLibrary extends Component
{
    public Organization $organization;

    public bool $adding = false;

    public string $name = '';

    public ?string $rhythm = null;

    public string $description = '';

    public function mount(Organization $organization): void
    {
        $this->organization = $organization;

        abort_unless($this->canView(), 403);
    }

    public function add(ProcessService $processes): void
    {
        abort_unless($this->canView(), 403);

        $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'rhythm' => ['nullable', Rule::enum(ProcessRhythm::class)],
            'description' => ['nullable', 'string', 'max:2000'],
        ], attributes: [
            'name' => __('processes.list.fields.name'),
            'rhythm' => __('processes.list.fields.rhythm'),
            'description' => __('processes.list.fields.description'),
        ]);

        $process = $processes->addManual($this->organization, $this->name, filled($this->rhythm) ? ProcessRhythm::from($this->rhythm) : null, description: $this->description);

        $this->redirect(route('client.processes.show', [$this->organization, $process]), navigate: false);
    }

    public function render(): View
    {
        $processes = Process::query()
            ->whereBelongsTo($this->organization)
            ->whereNull('parent_id')
            ->where('kind', '!=', ProcessKind::OneOff)
            ->with('responsible')
            ->withCount('participants')
            ->orderByRaw('case when accepted_at is null then 1 else 0 end')
            ->orderBy('name')
            ->get();

        return view('livewire.client.process-library', [
            'processes' => $processes,
            'connection' => CalendarConnection::query()->whereBelongsTo($this->organization)->where('status', 'active')->latest('id')->first(),
            'measuredCount' => $processes->filter(fn (Process $process) => $process->kind === ProcessKind::Routine && $process->isAccepted())->count(),
        ])->layout('layouts::client', [
            'title' => __('processes.library.title'),
            'subtitle' => __('processes.library.question'),
            'color' => 'cream',
            'back' => route('client.home', $this->organization),
            'backLabel' => __('client.menu.home'),
        ])->title(__('processes.library.title'));
    }

    /**
     * The organization's leaders and the leaders of its diagnoses (11, 15 · 15.3).
     */
    public function canView(): bool
    {
        return ProcessService::canManageLibrary(auth('web')->user(), $this->organization);
    }
}
