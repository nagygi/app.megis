<?php

namespace App\Livewire\Admin\Clients\Concerns;

use App\Models\User;
use App\Services\Accounts\AccountDeletionService;
use App\Services\Accounts\ClientAccountService;
use App\Services\Auth\ClientPasswordResetService;
use Illuminate\Support\Facades\Gate;

/**
 * The account actions of the user list and the datasheet (33 · 33.2, 33.8):
 * inactivation, the password link, archiving and why an item is greyed out.
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
     * A 60-minute password link (33 · 33.2, 14 · 14.3). The current password
     * keeps working until the user sets a new one.
     */
    public function sendPasswordReset(int $userId, ClientPasswordResetService $resets): void
    {
        $user = $this->findClient($userId);
        Gate::authorize('sendClientPasswordReset', $user);

        $resets->sendByAdmin($user, auth()->user());
        session()->flash('status', __('admin.clients.password_reset_sent', ['email' => $user->email]));
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
            default => __('admin.members.impersonation_unavailable.user_inactive'),
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
     * Why "Jelszó-beállító link küldése" is greyed out, or null when it works.
     */
    public function passwordResetUnavailableReason(User $client): ?string
    {
        if (Gate::allows('sendClientPasswordReset', $client)) {
            return null;
        }

        return match (true) {
            $client->isAdmin() => __('admin.clients.unavailable.admin_account'),
            $client->isDeletionRequested() => __('admin.clients.unavailable.archived'),
            ! $client->isActive() => __('admin.clients.unavailable.inactive'),
            default => __('admin.clients.unavailable.not_registered'),
        };
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
