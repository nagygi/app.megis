<?php

namespace App\Http\Controllers\ExternalClient;

use App\Enums\LegalDocumentType;
use App\Enums\TokenPurpose;
use App\Http\Controllers\Controller;
use App\Models\MainDiagnosis;
use App\Models\UserToken;
use App\Services\Auth\CustomerAuthenticator;
use App\Services\Auth\TokenService;
use App\Services\ExternalClients\ExternalClientMeasurementService;
use App\Services\Legal\LegalDocuments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * A client user's personal link to the Ügyfélmérés (35 · 35.5). Corporate
 * mail scanners open links in advance, so opening only shows a button
 * (14 · 14.2); pressing it activates the account at the first time, with the
 * legal documents accepted, logs in and opens the questions. No password.
 */
class InvitationController extends Controller
{
    public function __construct(
        private TokenService $tokens,
        private LegalDocuments $legalDocuments,
        private ExternalClientMeasurementService $measurements,
    ) {}

    public function show(string $token): View
    {
        $invitation = $this->usableInvitation($token);

        if ($invitation === null) {
            return view('external-client.invitation-expired');
        }

        return view('external-client.invitation', [
            'token' => $token,
            'organization' => $invitation->organization,
            'needsAcceptance' => ! $invitation->user->isActivated(),
            'terms' => $this->legalDocuments->current(LegalDocumentType::Terms),
            'privacy' => $this->legalDocuments->current(LegalDocumentType::PrivacyPolicy),
        ]);
    }

    public function store(Request $request, string $token, CustomerAuthenticator $authenticator): View|RedirectResponse
    {
        $invitation = $this->usableInvitation($token);

        if ($invitation === null) {
            return view('external-client.invitation-expired');
        }

        $user = $invitation->user;

        if (! $user->isActivated()) {
            $request->validate(['accept' => ['accepted']], ['accept.accepted' => __('auth_ui.registration.accept_required')]);
        }

        DB::transaction(function () use ($invitation, $user): void {
            $this->tokens->consume($invitation);

            if (! $user->isActivated()) {
                $user->activated_at = now();
                $user->save();

                $this->legalDocuments->recordAcceptance($user, $this->legalDocuments->currentAcceptable());
            }
        });

        $authenticator->login($user);

        $main = $this->measurements->openMeasurementsFor($user)
            ->first(fn (MainDiagnosis $main) => $main->diagnosis->organization_id === $invitation->organization_id);

        return $main === null
            ? redirect()->route('client.landing')
            : redirect()->route('external.measurement', $main->diagnosis);
    }

    /**
     * Invalid when expired or used, or when the user or the organization was
     * deactivated in the meantime. An admin account answers nowhere as a client.
     */
    private function usableInvitation(string $token): ?UserToken
    {
        $invitation = $this->tokens->find($token, TokenPurpose::ExternalClientInvitation);

        if ($invitation === null || ! $invitation->isPending()) {
            return null;
        }

        $invitation->loadMissing(['user', 'organization']);

        $isUsable = $invitation->user->isActive()
            && ! $invitation->user->isAdmin()
            && $invitation->organization?->isActive();

        return $isUsable ? $invitation : null;
    }
}
