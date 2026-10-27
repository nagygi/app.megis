<?php

namespace App\Http\Controllers\Client;

use App\Enums\LegalDocumentType;
use App\Enums\MembershipStatus;
use App\Enums\TokenPurpose;
use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\UserToken;
use App\Rules\Turnstile;
use App\Services\Auth\CustomerAuthenticator;
use App\Services\Auth\LandingResolver;
use App\Services\Auth\TokenService;
use App\Services\Legal\LegalDocuments;
use App\Support\PasswordRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Registration from an invitation (14 · 14.1). The link lives 14 days and is
 * used up by saving the form, not by opening it.
 */
class InvitationController extends Controller
{
    public function __construct(
        private TokenService $tokens,
        private LegalDocuments $legalDocuments,
    ) {}

    public function show(string $token): View|RedirectResponse
    {
        $invitation = $this->usableInvitation($token);

        if ($invitation === null) {
            return view('client.auth.invitation-expired');
        }

        // An existing account was only added to the organization: it logs in instead.
        if ($invitation->user->isActivated()) {
            return redirect()->route('client.login');
        }

        return view('client.auth.register', [
            'token' => $token,
            'user' => $invitation->user,
            'organization' => $invitation->organization,
            'inviter' => $invitation->creator,
            'terms' => $this->legalDocuments->current(LegalDocumentType::Terms),
            'privacy' => $this->legalDocuments->current(LegalDocumentType::PrivacyPolicy),
        ]);
    }

    public function store(Request $request, string $token, CustomerAuthenticator $authenticator, LandingResolver $landing): View|RedirectResponse
    {
        $invitation = $this->usableInvitation($token);

        if ($invitation === null) {
            return view('client.auth.invitation-expired');
        }

        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'confirmed', PasswordRules::rule()],
            'accept' => ['accepted'],
            Turnstile::FIELD => [new Turnstile],
        ], [
            'accept.accepted' => __('auth_ui.registration.accept_required'),
        ]);

        $user = $invitation->user;

        DB::transaction(function () use ($invitation, $user, $validated): void {
            $this->tokens->consume($invitation);

            $user->fill([
                'last_name' => $validated['last_name'],
                'first_name' => $validated['first_name'],
            ]);

            if (filled($validated['password'] ?? null)) {
                $user->password = $validated['password'];
            }

            $user->activated_at = now();
            $user->last_organization_id = $invitation->organization_id;
            $user->save();

            Membership::query()
                ->where('organization_id', $invitation->organization_id)
                ->where('user_id', $user->id)
                ->update(['status' => MembershipStatus::Active, 'activated_at' => now()]);

            $this->legalDocuments->recordAcceptance($user, $this->legalDocuments->currentAcceptable());
        });

        $authenticator->login($user);

        return redirect()->intended($landing->urlFor($user->refresh()));
    }

    /**
     * Invalid when expired or used, or when the user or the organization was
     * deactivated in the meantime (14 · 14.1).
     */
    private function usableInvitation(string $token): ?UserToken
    {
        $invitation = $this->tokens->find($token, TokenPurpose::ClientInvitation);

        if ($invitation === null || ! $invitation->isPending()) {
            return null;
        }

        $invitation->loadMissing(['user', 'organization', 'creator']);

        $membership = Membership::query()
            ->where('organization_id', $invitation->organization_id)
            ->where('user_id', $invitation->user_id)
            ->first();

        $isUsable = $invitation->user->isActive()
            && $invitation->organization?->isActive()
            && $membership !== null
            && $membership->status !== MembershipStatus::Inactive;

        return $isUsable ? $invitation : null;
    }
}
