<?php

namespace App\Livewire\Admin\AdminUsers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Admins\AdminUserService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Inviting (create) and editing (name, e-mail, role) an admin (01 · 1.3).
 */
class Form extends Component
{
    public ?User $user = null;

    public string $last_name = '';

    public string $first_name = '';

    public string $email = '';

    public string $role = UserRole::Admin->value;

    public function mount(?User $user = null): void
    {
        if ($user?->exists) {
            abort_unless($user->isAdmin(), 404);
            Gate::authorize('view', $user);

            $this->user = $user;
            $this->last_name = (string) $user->last_name;
            $this->first_name = (string) $user->first_name;
            $this->email = $user->email;
            $this->role = $user->role->value;

            return;
        }

        Gate::authorize('inviteAdmin', [User::class, UserRole::Admin]);
    }

    public function save(AdminUserService $admins): void
    {
        $actor = auth()->user();
        $isEditing = $this->user !== null;

        if ($isEditing) {
            Gate::authorize('updateAdmin', $this->user);
        }

        $validated = $this->validate([
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        $role = UserRole::from($validated['role']);
        $email = Str::lower(trim($validated['email']));

        if (! $isEditing) {
            Gate::authorize('inviteAdmin', [User::class, $role]);
            $this->ensureNotAdminAlready($email);

            $admins->invite($validated, $role, $actor);
            session()->flash('status', __('admin.admins.invited', ['email' => $email]));
            $this->redirectRoute('admin.admins.index', navigate: false);

            return;
        }

        Gate::authorize('assignRole', [$this->user, $role]);

        $emailChanged = $email !== $this->user->email;

        if ($emailChanged) {
            Gate::authorize('changeEmail', $this->user);
            $this->validate(['email' => [Rule::unique('users', 'email')->ignore($this->user->id)]]);
        }

        $this->user->fill([
            'last_name' => $validated['last_name'],
            'first_name' => $validated['first_name'],
            'email' => $email,
        ]);
        $this->user->role = $role;
        $this->user->save();

        session()->flash('status', __('admin.common.saved'));
        $this->redirectRoute('admin.admins.index', navigate: false);
    }

    public function render(): View
    {
        $actor = auth()->user();

        return view('livewire.admin.admin-users.form', [
            'isEditing' => $this->user !== null,
            'canEdit' => $this->user === null || Gate::allows('updateAdmin', $this->user),
            'canChangeEmail' => $this->user === null || Gate::allows('changeEmail', $this->user),
            'roles' => collect(UserRole::cases())->filter(
                fn (UserRole $role): bool => $this->user === null
                    ? $actor->can('inviteAdmin', [User::class, $role])
                    : $actor->can('assignRole', [$this->user, $role]),
            ),
        ])->layout('layouts::admin')->title($this->user ? __('admin.admins.edit_title') : __('admin.admins.invite_title'));
    }

    /**
     * @throws ValidationException
     */
    private function ensureNotAdminAlready(string $email): void
    {
        if (User::query()->admins()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => __('admin.admins.already_admin')]);
        }
    }
}
