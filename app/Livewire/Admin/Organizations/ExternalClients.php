<?php

namespace App\Livewire\Admin\Organizations;

use App\Enums\ExternalClientItemKind;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * The organization's "Ügyfelek" tab (35 · 35.1): the clients it serves, with
 * their users and what we measure at them. Only when Ügyfélmérés is switched
 * on; a client is created and edited on a page of its own (ExternalClientForm).
 */
class ExternalClients extends Component
{
    use WithDataTable;

    public Organization $organization;

    public function mount(Organization $organization): void
    {
        Gate::authorize('update', $organization);
        abort_unless($organization->usesExternalClients(), 404);

        $this->organization = $organization;
    }

    public function deactivate(int $clientId): void
    {
        Gate::authorize('update', $this->organization);

        $this->organization->externalClients()->findOrFail($clientId)->update(['deactivated_at' => now()]);
        session()->flash('status', __('admin.common.saved'));
    }

    public function activate(int $clientId): void
    {
        Gate::authorize('update', $this->organization);

        $this->organization->externalClients()->findOrFail($clientId)->update(['deactivated_at' => null]);
        session()->flash('status', __('admin.common.saved'));
    }

    /**
     * Its users keep their accounts; only their link to the client goes.
     */
    public function delete(int $clientId): void
    {
        Gate::authorize('update', $this->organization);

        $this->organization->externalClients()->findOrFail($clientId)->delete();
        session()->flash('status', __('admin.common.deleted'));
    }

    public function render(): View
    {
        $query = $this->organization->externalClients()
            ->getQuery()
            ->withCount([
                'users',
                'items as values_count' => fn (Builder $items) => $items->where('kind', ExternalClientItemKind::Value),
                'items as processes_count' => fn (Builder $items) => $items->where('kind', ExternalClientItemKind::Process),
            ]);

        if ($term = $this->searchTerm()) {
            $query->where(fn (Builder $search) => $search->whereLike('name', $term)->orWhereLike('description', $term));
        }

        return view('livewire.admin.organizations.external-clients', [
            'clients' => $this->applySorting($query)->paginate(25),
        ])->layout('layouts::admin')->title($this->organization->name);
    }

    protected function sortableColumns(): array
    {
        return [
            'name' => 'name',
            'users' => 'users_count',
        ];
    }

    protected function defaultSort(): array
    {
        return ['name', 'asc'];
    }
}
