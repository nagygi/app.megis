<?php

namespace App\Livewire\Admin;

use App\Mail\PasswordChangedMail;
use App\Services\Auth\GuardSession;
use App\Support\PasswordRules;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Profilom (01 · 1.5): name, password with the current one, own e-mail for superadmins only.
 */
class Profile extends Component
{
    public string $last_name = '';

    public string $first_name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $admin = auth()->user();

        $this->last_name = (string) $admin->last_name;
        $this->first_name = (string) $admin->first_name;
        $this->email = $admin->email;
    }

    public function saveProfile(): void
    {
        $admin = auth()->user();

        $validated = $this->validate([
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin->id)],
        ]);

        $email = Str::lower(trim($validated['email']));

        if ($email !== $admin->email) {
            Gate::authorize('changeEmail', $admin);
        }

        $admin->fill([
            'last_name' => $validated['last_name'],
            'first_name' => $validated['first_name'],
            'email' => $email,
        ])->save();

        session()->flash('status', __('admin.common.saved'));
    }

    public function changePassword(GuardSession $guardSession): void
    {
        $admin = auth()->user();

        $validated = $this->validate([
            'current_password' => ['required', 'string', 'current_password:'.GuardSession::ADMIN],
            'password' => ['required', 'string', 'confirmed', PasswordRules::rule()],
        ]);

        $admin->forceFill(['password' => $validated['password']])->save();
        $admin->invalidateSessions();
        $guardSession->rememberEpoch(GuardSession::ADMIN, $admin->refresh());

        Mail::to($admin->email)->queue(new PasswordChangedMail($admin, now()->toImmutable()));

        $this->reset(['current_password', 'password', 'password_confirmation']);
        session()->flash('status', __('admin.profile.password_changed'));
    }

    public function render(): View
    {
        $admin = auth()->user();

        return view('livewire.admin.profile', [
            'admin' => $admin,
            'canChangeEmail' => Gate::allows('changeEmail', $admin),
        ])->layout('layouts::admin')->title(__('admin.profile.title'));
    }
}
