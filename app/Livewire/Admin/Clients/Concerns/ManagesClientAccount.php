<?php

namespace App\Livewire\Admin\Clients\Concerns;

use App\Models\User;
use App\Services\Accounts\AccountDeletionService;
use App\Services\Accounts\ClientAccountService;
use Illuminate\Support\Facades\Gate;

/**
 * The account actions of the user list and the datasheet (33 · 33.2, 33.8):
 * inactivation, archiving and why an item is greyed out.
 */
trait ManagesClientAccount
{
    public function deactivate(int $userId, ClientAccountService $accounts): void
    {
        $user = $this->findClient($userId);
        Gate::authorize('deactivateClient', $user);

        $accounts->deactivate($user, auth()->user());
        session()->flash('status', __('admin.clients.deactivated'));
    }

    public function activate(int $userId, ClientAccountService $accounts): void
    {
        $user = $this->findClient($userId);
        Gate::authorize('activateClient', $user);

        $accounts->activate($user, auth()->user());
        session()->flash('status', __('admin.clients.activated'));
    }

    /**
     * The account deletion of 31 · 31.7, started by a superadmin.
     */
    public function archive(int $userId, AccountDeletionService $deletions): void
    {
        $user = $this->findClient($userId);
        Gate::authorize('archive', $user);

        $deletions->request($user, auth()->user());
        session()->flash('status', __('admin.clients.archived_flash'));
    }

    /**
     * Why "Belépek a nevében" is greyed out, or null when it works (14 · 14.6).
     */
    public function impersonationUnavailableReason(User $client): ?string
    {
        if (Gate::allows('impersonate', $client)) {
            return null;
        }

        return match (true) {
            $client->isAdmin() => __('admin.clients.unavailable.admin_impersonation'),
            $client->isDeletionRequested() => __('admin.clients.unavailable.archived'),
            ! $client->isActive() => __('admin.members.impersonation_unavailable.user_inactive'),
            default => __('admin.members.impersonation_unavailable.not_registered'),
        };
    }

    /**
     * Why "Inaktiválom" or "Aktiválom" is greyed out, or null when it works.
     */
    public function statusChangeUnavailableReason(User $client): ?string
    {
        if (Gate::allows($client->isActive() ? 'deactivateClient' : 'activateClient', $client)) {
            return null;
        }

        return $client->isAdmin()
            ? __('admin.clients.unavailable.admin_account')
            : __('admin.clients.unavailable.archived');
    }

    /**
     * Why "Archiválom" is greyed out, or null when it works.
     */
    public function archiveUnavailableReason(User $client): ?string
    {
        if (Gate::allows('archive', $client)) {
            return null;
        }

        return match (true) {
            $client->isDeletionRequested() => __('admin.clients.unavailable.archived'),
            $client->isAdmin() => __('admin.clients.unavailable.admin_account'),
            default => __('admin.clients.unavailable.superadmin_only'),
        };
    }

    abstract protected function findClient(int $userId): User;
}
