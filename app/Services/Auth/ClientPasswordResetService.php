<?php

namespace App\Services\Auth;

use App\Enums\AuditEventType;
use App\Enums\TokenPurpose;
use App\Mail\ClientPasswordResetMail;
use App\Mail\PasswordChangedMail;
use App\Models\User;
use App\Models\UserToken;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * "Elfelejtettem a jelszavam" of a customer (14 · 14.3): a link valid for 60
 * minutes and usable once, asked for on B1 or sent by an admin (33 · 33.2).
 * The old password keeps working until the new one is saved. Admin accounts
 * never get one: they ask another admin (01 · 1.2).
 */
class ClientPasswordResetService
{
    public function __construct(
        private TokenService $tokens,
        private CustomerAuthenticator $authenticator,
        private AuditLogger $audit,
    ) {}

    /**
     * Sends the link when the address belongs to a customer who may log in.
     * Says nothing either way, so the caller can answer the same to everyone.
     */
    public function request(string $email): void
    {
        $user = $this->authenticator->findLoginable($email);

        if ($user === null || ! $this->mayReset($user)) {
            return;
        }

        $this->send($user);

        Log::channel('auth')->info('Customer password link sent.', ['user_id' => $user->id]);
    }

    public function sendByAdmin(User $user, User $admin): void
    {
        $this->send($user, $admin);

        $this->audit->record(AuditEventType::PasswordResetLinkSent, $user, $admin);
        Log::channel('auth')->info('Customer password link sent by an admin.', ['user_id' => $user->id, 'actor_id' => $admin->id]);
    }

    /**
     * The token behind a link while it can still set a password.
     */
    public function findUsable(string $plainToken): ?UserToken
    {
        $token = $this->tokens->find($plainToken, TokenPurpose::ClientPasswordReset);

        if ($token === null || ! $token->isPending()) {
            return null;
        }

        return $this->mayReset($token->user) ? $token : null;
    }

    /**
     * Every other session ends, and the user is told about the change.
     */
    public function complete(UserToken $token, string $password): User
    {
        $user = $token->user;

        DB::transaction(function () use ($token, $user, $password): void {
            $this->tokens->consume($token);

            $user->password = $password;
            $user->save();
            $user->invalidateSessions();
        });

        Mail::to($user->email)->queue(new PasswordChangedMail($user, now()->toImmutable()));
        Log::channel('auth')->info('Customer password reset.', ['user_id' => $user->id]);

        return $user->refresh();
    }

    public function mayReset(User $user): bool
    {
        return ! $user->isAdmin()
            && $user->isActive()
            && $user->isActivated()
            && ! $user->isDeletionRequested();
    }

    private function send(User $user, ?User $admin = null): void
    {
        $issued = $this->tokens->issue($user, TokenPurpose::ClientPasswordReset, creator: $admin);

        Mail::to($user->email)->queue(new ClientPasswordResetMail($user, $issued->plainToken, byAdmin: $admin !== null));
    }
}
