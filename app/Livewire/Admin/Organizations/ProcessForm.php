<?php

namespace App\Livewire\Admin\Organizations;

use App\Enums\ProcessRhythm;
use App\Models\Organization;
use App\Models\Process;
use App\Services\Processes\ProcessService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * A process or subprocess added by hand, on a page of its own under the
 * Folyamatok tab (11 · 11.6), with the same rules as for the leader.
 */
class ProcessForm extends Component
{
    public Organization $organization;

    public string $name = '';

    public ?string $rhythm = null;

    public ?int $parentId = null;

    public string $description = '';

    public function mount(Organization $organization): void
    {
        Gate::authorize('update', $organization);

        $this->organization = $organization;
    }

    public function save(ProcessService $processes): void
    {
        Gate::authorize('update', $this->organization);
        $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'rhythm' => ['nullable', Rule::enum(ProcessRhythm::class)],
            'parentId' => ['nullable', Rule::in($this->parents()->modelKeys())],
            'description' => ['nullable', 'string', 'max:2000'],
        ], attributes: [
            'name' => __('processes.list.fields.name'),
            'parentId' => __('processes.list.fields.parent'),
            'description' => __('processes.list.fields.description'),
        ]);

        $processes->addManual(
            $this->organization,
            $this->name,
            filled($this->rhythm) ? ProcessRhythm::from($this->rhythm) : null,
            $this->parentId ? $this->parents()->find($this->parentId) : null,
            $this->description,
        );

        session()->flash('status', __('admin.processes.added', ['name' => trim($this->name)]));
        $this->redirectRoute('admin.organizations.processes', $this->organization, navigate: false);
    }

    public function render(): View
    {
        return view('livewire.admin.organizations.process-form', [
            'parents' => $this->parents(),
        ])->layout('layouts::admin')->title(__('admin.processes.add'));
    }

    /**
     * The top-level processes: two levels at most (11 · 11.2).
     *
     * @return Collection<int, Process>
     */
    private function parents(): Collection
    {
        return Process::query()->whereBelongsTo($this->organization)->whereNull('parent_id')->orderBy('id')->get();
    }
}
