<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Session\Session;

/**
 * Logs users in and out of the two session guards.
 *
 * The admin and the customer side share one session but use separate guards,
 * so an admin can work in a customer's name in parallel (14 · 14.6).
 */
class GuardSession
{
    public const ADMIN = 'admin';

    public const WEB = 'web';

    public const ADMIN_LAST_ACTIVITY_KEY = 'admin_last_activity';

    public function __construct(
        private AuthFactory $auth,
        private Session $session,
    ) {}

    public function login(string $guard, User $user): void
    {
        $this->auth->guard($guard)->login($user);
        $this->session->regenerate();
        $this->rememberEpoch($guard, $user);

        if ($guard === self::ADMIN) {
            $this->touchAdminActivity();
        }
    }

    public function logout(string $guard): void
    {
        $this->auth->guard($guard)->logout();
        $this->session->forget($this->epochKey($guard));

        if ($guard === self::ADMIN) {
            $this->session->forget(self::ADMIN_LAST_ACTIVITY_KEY);
        }

        if (! $this->auth->guard($this->otherGuard($guard))->check()) {
            $this->session->invalidate();
        }

        $this->session->regenerateToken();
    }

    /**
     * Keeps the current session alive after the user's own password change,
     * while every other session ends.
     */
    public function rememberEpoch(string $guard, User $user): void
    {
        $this->session->put($this->epochKey($guard), (int) $user->auth_epoch);
    }

    public function epochMatches(string $guard, User $user): bool
    {
        $sessionEpoch = $this->session->get($this->epochKey($guard));

        return $sessionEpoch !== null && (int) $sessionEpoch === (int) $user->auth_epoch;
    }

    public function touchAdminActivity(): void
    {
        $this->session->put(self::ADMIN_LAST_ACTIVITY_KEY, now()->getTimestamp());
    }

    public function adminLastActivity(): ?int
    {
        return $this->session->get(self::ADMIN_LAST_ACTIVITY_KEY);
    }

    private function epochKey(string $guard): string
    {
        return 'auth_epoch.'.$guard;
    }

    private function otherGuard(string $guard): string
    {
        return $guard === self::ADMIN ? self::WEB : self::ADMIN;
    }
}
