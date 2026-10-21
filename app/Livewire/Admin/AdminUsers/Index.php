<?php

namespace App\Livewire\Admin\AdminUsers;

use App\Enums\AdminStatus;
use App\Enums\TokenPurpose;
use App\Enums\UserRole;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Models\User;
use App\Services\Admins\AdminUserService;
use App\Support\MailAllowlist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Admin felhasználók (01 · 1.3).
 */
class Index extends Component
{
    use WithDataTable;

    /**
     * Inactive accounts are hidden until this is switched on.
     */
    #[Url(as: 'inaktivak', except: false)]
    public bool $showInactive = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    public function resendInvitation(int $userId, AdminUserService $admins, MailAllowlist $mailAllowlist): void
    {
        $user = $this->findAdmin($userId);
        Gate::authorize('resendInvitation', $user);

        $admins->sendInvitation($user, auth()->user());
        $this->flashSent($user, __('admin.admins.invitation_resent'), $mailAllowlist);
    }

    public function resetPassword(int $userId, AdminUserService $admins, MailAllowlist $mailAllowlist): void
    {
        $user = $this->findAdmin($userId);
        Gate::authorize('resetPassword', $user);

        $admins->resetPassword($user, auth()->user());
        $this->flashSent($user, __('admin.admins.reset_sent', ['email' => $user->email]), $mailAllowlist);
    }

    public function deactivate(int $userId, AdminUserService $admins): void
    {
        $user = $this->findAdmin($userId);
        Gate::authorize('deactivate', $user);

        $admins->deactivate($user);
        session()->flash('status', __('admin.admins.deactivated'));
    }

    public function activate(int $userId, AdminUserService $admins): void
    {
        $user = $this->findAdmin($userId);
        Gate::authorize('activate', $user);

        $admins->activate($user);
        session()->flash('status', __('admin.admins.activated'));
    }

    public function render(): View
    {
        $query = User::query()
            ->admins()
            ->withExists(['tokens as has_pending_admin_invitation' => fn (Builder $tokens) => $tokens
                ->pending()
                ->whereIn('purpose', [TokenPurpose::AdminInvitation, TokenPurpose::AdminPasswordReset])]);

        if ($term = $this->searchTerm()) {
            $query->where(fn (Builder $search) => $search
                ->whereLike('email', $term)
                ->orWhereLike('first_name', $term)
                ->orWhereLike('last_name', $term));
        }

        if ($role = UserRole::tryFrom((string) $this->filter('role'))) {
            $query->where('role', $role);
        }

        $status = AdminStatus::tryFrom((string) $this->filter('status'));
        $this->applyStatusFilter($query, $status);

        if (! $this->showInactive && $status !== AdminStatus::Inactive) {
            $query->whereNull('deactivated_at');
        }

        return view('livewire.admin.admin-users.index', [
            'users' => $this->applySorting($query)->paginate(25),
            'roles' => UserRole::cases(),
            'statuses' => AdminStatus::cases(),
        ])->layout('layouts::admin')->title(__('admin.admins.title'));
    }

    protected function sortableColumns(): array
    {
        return [
            'name' => 'last_name',
            'email' => 'email',
            'role' => 'role',
            'last_login' => 'last_login_at',
        ];
    }

    protected function defaultSort(): array
    {
        return ['name', 'asc'];
    }

    /**
     * @param  Builder<User>  $query
     */
    private function applyStatusFilter(Builder $query, ?AdminStatus $status): void
    {
        $pendingInvitation = fn (Builder $tokens) => $tokens
            ->pending()
            ->whereIn('purpose', [TokenPurpose::AdminInvitation, TokenPurpose::AdminPasswordReset]);

        match ($status) {
            AdminStatus::Inactive => $query->whereNotNull('deactivated_at'),
            AdminStatus::Locked => $query->whereNull('deactivated_at')->whereNotNull('locked_at'),
            AdminStatus::Active => $query->whereNull('deactivated_at')->whereNull('locked_at')->whereNotNull('activated_at'),
            AdminStatus::Invited => $query->whereNull('deactivated_at')->whereNull('activated_at')->whereHas('tokens', $pendingInvitation),
            AdminStatus::InvitationExpired => $query->whereNull('deactivated_at')->whereNull('activated_at')->whereDoesntHave('tokens', $pendingInvitation),
            null => null,
        };
    }

    private function findAdmin(int $userId): User
    {
        return User::query()->admins()->findOrFail($userId);
    }

    /**
     * The mail leaves through the queue, so the success message would hide that
     * the allowlist drops it later.
     */
    private function flashSent(User $user, string $message, MailAllowlist $mailAllowlist): void
    {
        if ($mailAllowlist->allows($user->email)) {
            session()->flash('status', $message);

            return;
        }

        session()->flash('error', __('admin.admins.mail_suppressed', ['email' => $user->email]));
    }
}
