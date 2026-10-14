<?php

namespace App\Livewire\Admin\Clients;

use App\Enums\AuditEventType;
use App\Exceptions\HeadcountLimitReachedException;
use App\Mail\EmailChangedMail;
use App\Models\AuditEvent;
use App\Models\LegalAcceptance;
use App\Models\Membership;
use App\Models\MirrorSession;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Organizations\MembershipService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * The customer's datasheet (33): data, organizations, accepted documents and
 * the account log. Diagnoses, Értéktükör and consents join with their tickets.
 */
class Show extends Component
{
    public User $user;

    public string $last_name = '';

    public string $first_name = '';

    public string $email = '';

    public bool $editing = false;

    public function mount(User $user): void
    {
        Gate::authorize('view', $user);

        $this->user = $user;
        $this->fillForm();
    }

    public function startEditing(): void
    {
        $this->fillForm();
        $this->resetValidation();
        $this->editing = true;
    }

    public function cancel(): void
    {
        $this->editing = false;
    }

    /**
     * Any admin edits the name; only a superadmin edits the e-mail, and both
     * the old and the new address are told (33 · 2. kérdés).
     */
    public function save(AuditLogger $audit): void
    {
        $validated = $this->validate([
            'last_name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user->id)],
        ]);

        $newEmail = Str::lower(trim($validated['email']));
        $oldEmail = $this->user->email;

        if ($newEmail !== $oldEmail) {
            Gate::authorize('changeEmail', $this->user);
        }

        $this->user->fill([
            'last_name' => $validated['last_name'] ?: null,
            'first_name' => $validated['first_name'] ?: null,
            'email' => $newEmail,
        ])->save();

        if ($newEmail !== $oldEmail) {
            Mail::to($oldEmail)->queue(new EmailChangedMail($newEmail, toOldAddress: true));
            Mail::to($newEmail)->queue(new EmailChangedMail($newEmail, toOldAddress: false));
            $audit->record(AuditEventType::EmailChanged, $this->user, auth()->user(), ['old' => $oldEmail, 'new' => $newEmail]);
            session()->flash('status', __('admin.clients.email_changed'));
        } else {
            session()->flash('status', __('admin.common.saved'));
        }

        $this->editing = false;
    }

    public function sendInvitation(int $membershipId, MembershipService $memberships): void
    {
        $membership = $this->findMembership($membershipId);
        Gate::authorize('update', $membership);

        $memberships->sendInvitation($membership, auth()->user());
        session()->flash('status', __('admin.members.invitation_sent', ['email' => $this->user->email]));
    }

    public function deactivateMembership(int $membershipId, MembershipService $memberships): void
    {
        $membership = $this->findMembership($membershipId);
        Gate::authorize('update', $membership);

        $memberships->deactivate($membership);
        session()->flash('status', __('admin.common.saved'));
    }

    public function activateMembership(int $membershipId, MembershipService $memberships): void
    {
        $membership = $this->findMembership($membershipId);
        Gate::authorize('update', $membership);

        try {
            $memberships->activate($membership);
            session()->flash('status', __('admin.common.saved'));
        } catch (HeadcountLimitReachedException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function render(): View
    {
        $memberships = $this->user->memberships()->with('organization')->get();
        $teamsByOrganization = $this->user->teams()->get()->groupBy('organization_id');

        return view('livewire.admin.clients.show', [
            'memberships' => $memberships,
            'teamsByOrganization' => $teamsByOrganization,
            'acceptances' => LegalAcceptance::query()
                ->whereBelongsTo($this->user)
                ->with('version')
                ->latest('accepted_at')
                ->get(),
            'events' => AuditEvent::query()
                ->where('subject_user_id', $this->user->id)
                ->with('actor')
                ->latest('id')
                ->limit(100)
                ->get(),
            'mirrors' => MirrorSession::query()
                ->whereBelongsTo($this->user)
                ->whereNotNull('claimed_at')
                ->with('diagnosis')
                ->latest('id')
                ->get(),
            'canChangeEmail' => Gate::allows('changeEmail', $this->user),
            'canImpersonate' => Gate::allows('impersonate', $this->user),
            'status' => $this->user->load('memberships')->clientStatus(),
        ])->layout('layouts::admin')->title($this->user->name);
    }

    private function fillForm(): void
    {
        $this->last_name = (string) $this->user->last_name;
        $this->first_name = (string) $this->user->first_name;
        $this->email = $this->user->email;
    }

    private function findMembership(int $membershipId): Membership
    {
        return $this->user->memberships()->with(['user', 'organization'])->findOrFail($membershipId);
    }
}
