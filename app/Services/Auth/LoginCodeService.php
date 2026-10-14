<?php

namespace App\Services\Auth;

use App\Enums\TokenPurpose;
use App\Mail\LoginCodeMail;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * E-mailed login code and link, both valid for 15 minutes and usable once (14 · 14.2).
 */
class LoginCodeService
{
    public const RESEND_AFTER_SECONDS = 60;

    public function __construct(
        private TokenService $tokens,
        private CustomerAuthenticator $authenticator,
    ) {}

    /**
     * Sends a code when the address belongs to an account that may log in.
     * Says nothing either way, so the caller can answer the same to everyone.
     */
    public function send(string $email): void
    {
        $user = $this->authenticator->findLoginable($email);

        if ($user === null) {
            return;
        }

        $issued = $this->tokens->issue($user, TokenPurpose::LoginCode);

        Mail::to($user->email)->queue(new LoginCodeMail($user, $issued->plainToken, (string) $issued->plainCode));

        Log::channel('auth')->info('Login code sent.', ['user_id' => $user->id]);
    }

    /**
     * Seconds until another code may be requested; 0 when it may be requested now.
     */
    public function secondsUntilResend(string $email): int
    {
        $user = $this->authenticator->findLoginable($email);
        $latest = $user === null ? null : $user->tokens()->where('purpose', TokenPurpose::LoginCode)->latest('id')->first();

        if ($latest === null) {
            return 0;
        }

        return max(0, self::RESEND_AFTER_SECONDS - (int) $latest->created_at->diffInSeconds(now()));
    }

    /**
     * Checks a typed code against the user's pending code.
     */
    public function verifyCode(string $email, string $code): ?User
    {
        $user = $this->authenticator->findLoginable($email);
        $token = $user === null ? null : $this->tokens->pendingFor($user, TokenPurpose::LoginCode);

        if ($token === null || ! $this->tokens->checkCode($token, $code)) {
            return null;
        }

        $this->tokens->consume($token);

        return $user;
    }

    /**
     * The token behind a login link, in any state.
     */
    public function findLinkToken(string $plainToken): ?UserToken
    {
        return $this->tokens->find($plainToken, TokenPurpose::LoginCode);
    }

    /**
     * Uses up a login link. Opening the link does not do this; only the button on the page does,
     * because corporate mail scanners open links in advance (14 · 14.1).
     */
    public function useLink(string $plainToken): ?User
    {
        $token = $this->findLinkToken($plainToken);

        if ($token === null || ! $token->isPending()) {
            return null;
        }

        $user = $token->user;

        if (! $user->isActive() || ! $user->isActivated()) {
            return null;
        }

        $this->tokens->consume($token);

        return $user;
    }
}
