<?php

namespace App\Livewire\Admin\Clients;

use App\Enums\MembershipStatus;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Models\MainDiagnosisParticipant;
use App\Models\MirrorSession;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Ügyfelek (33 · 33.8). New users are added through an organization, not here.
 */
class Index extends Component
{
    use WithDataTable;

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    public function render(): View
    {
        $query = User::query()
            ->where(fn (Builder $clients) => $clients->whereNull('role')->orWhereHas('memberships'))
            ->with('memberships.organization');

        // Every word on the e-mail or a part of the name, so "Kovács Anna" finds her too.
        foreach (preg_split('/\s+/', trim($this->search), flags: PREG_SPLIT_NO_EMPTY) as $word) {
            $term = '%'.$word.'%';
            $query->where(fn (Builder $search) => $search
                ->whereLike('email', $term)
                ->orWhereLike('first_name', $term)
                ->orWhereLike('last_name', $term));
        }

        if ($organizationId = $this->filter('organization')) {
            $query->whereHas('memberships', fn (Builder $memberships) => $memberships->where('organization_id', $organizationId));
        }

        // "Csak Értéktükör": an Értéktükör of their own and no diagnosis to lead or join.
        if ($this->filter('mirror') === 'only') {
            $query
                ->whereExists(MirrorSession::query()->whereColumn('mirror_sessions.user_id', 'users.id')->whereNotNull('claimed_at'))
                ->whereNotExists(MainDiagnosisParticipant::query()->whereColumn('main_diagnosis_participants.user_id', 'users.id'));
        }

        match ($this->filter('status')) {
            'active' => $query->whereNull('deactivated_at')->whereNotNull('activated_at')
                ->whereHas('memberships', fn (Builder $memberships) => $memberships->where('status', '!=', MembershipStatus::Inactive)),
            'pending' => $query->whereNull('deactivated_at')->whereNull('activated_at'),
            'inactive' => $query->where(fn (Builder $inactive) => $inactive
                ->whereNotNull('deactivated_at')
                ->orWhere(fn (Builder $withoutAccess) => $withoutAccess
                    ->whereNotNull('activated_at')
                    ->whereDoesntHave('memberships', fn (Builder $memberships) => $memberships->where('status', '!=', MembershipStatus::Inactive)))),
            default => null,
        };

        return view('livewire.admin.clients.index', [
            'clients' => $this->applySorting($query)->paginate(25),
            'organizationOptions' => Organization::query()->orderBy('name')->pluck('name', 'id'),
            'statusOptions' => __('admin.clients.status_values'),
            'mirrorOptions' => ['only' => __('admin.clients.only_mirror')],
        ])->layout('layouts::admin')->title(__('admin.clients.title'));
    }

    protected function sortableColumns(): array
    {
        return [
            'name' => 'last_name',
            'email' => 'email',
            'last_login' => 'last_login_at',
        ];
    }

    protected function defaultSort(): array
    {
        return ['name', 'asc'];
    }
}
