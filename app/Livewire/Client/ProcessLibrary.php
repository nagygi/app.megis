<?php

namespace App\Livewire\Client;

use App\Enums\ProcessKind;
use App\Enums\ProcessRhythm;
use App\Livewire\Concerns\WithDictation;
use App\Models\CalendarConnection;
use App\Models\Organization;
use App\Models\Process;
use App\Services\Auth\ImpersonationService;
use App\Services\Processes\ProcessService;
use App\Services\Report\ReportService;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Tárak → Folyamatok (11 · 11.5): the organization's routines, whether they
 * are in the calendar and measured; "+ Rutin" adds one by hand, its
 * description written, told or uploaded. The list a published diagnosis
 * recorded does not change, the library can.
 */
class ProcessLibrary extends Component
{
    use WithDictation, WithFileUploads;

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
            'description' => ['nullable', 'string', 'max:'.ProcessSheet::DESCRIPTION_MAX],
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

        $connection = CalendarConnection::query()->whereBelongsTo($this->organization)->where('status', 'active')->latest('id')->first();
        $measuredCount = $processes->filter(fn (Process $process) => $process->kind === ProcessKind::Routine && $process->isAccepted())->count();

        // The score of the viewer's latest published report, as in the Értéktár (30, question 1).
        $reports = app(ReportService::class);
        $report = $reports->reportsFor(auth('web')->user(), $this->organization)->first();
        $snapshot = $report === null ? null : $reports->snapshot($report);

        return view('livewire.client.process-library', [
            'processes' => $processes,
            'canDictate' => $this->canDictate('description'),
            'scores' => collect($snapshot?->data['chapters']['processes']['rows'] ?? [])->keyBy(fn (array $row) => mb_strtolower(trim($row['label']))),
        ])->layout('layouts::client', [
            // M4: cream with the yellow line, the mycelium, "naptár összekötve · 03.02. · 7 rutin · 5 mérve".
            'title' => __('processes.library.title'),
            'subtitle' => __('processes.library.question'),
            'note' => collect([
                $connection ? __('processes.library.calendar_connected', ['date' => $connection->connected_at?->format('m.d.')]) : null,
                __('processes.library.routines', ['count' => $processes->count()]),
                __('processes.library.measured_count', ['count' => $measuredCount]),
            ])->filter()->implode(' · '),
            'color' => 'cream-line',
            'symbol' => 'mycelium',
            'label' => __('processes.library.title'),
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

    protected function canDictate(string $field): bool
    {
        return $field === 'description' && $this->canView() && ! app(ImpersonationService::class)->isImpersonating();
    }

    protected function dictationOrganization(): ?Organization
    {
        return $this->organization;
    }

    protected function dictationLimit(string $field): ?int
    {
        return ProcessSheet::DESCRIPTION_MAX;
    }
}
