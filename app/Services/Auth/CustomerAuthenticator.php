<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Customer password login with the 15-minute pause (14 · 14.2).
 * Logging in with a code keeps working during the pause.
 */
class CustomerAuthenticator
{
    public const MAX_FAILED_PASSWORDS = 10;

    public const PAUSE_MINUTES = 15;

    public function __construct(
        private Hasher $hasher,
        private GuardSession $guardSession,
        private Cache $cache,
    ) {}

    /**
     * Logs the customer in after a correct password, code, link or registration.
     */
    public function login(User $user): void
    {
        $this->guardSession->login(GuardSession::WEB, $user);
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        Log::channel('auth')->info('Customer logged in.', ['user_id' => $user->id]);
    }

    /**
     * An activated, active account; it may still have no password.
     */
    public function findLoginable(string $email): ?User
    {
        $user = User::query()->where('email', Str::lower(trim($email)))->first();

        return $user !== null && $user->isActive() && $user->isActivated() ? $user : null;
    }

    /**
     * @return array{0: LoginOutcome, 1: ?User}
     */
    public function attemptPassword(string $email, string $password): array
    {
        $user = $this->findLoginable($email);

        if ($user === null || $user->password === null) {
            // Hashed all the same, so the time taken does not tell either.
            $this->hasher->make($password);

            return [$this->recordFailureWithoutAccount($email), null];
        }

        if ($user->isPasswordPaused()) {
            return [LoginOutcome::PasswordPaused, $user];
        }

        if (! $this->hasher->check($password, $user->password)) {
            return [$this->recordFailure($user), $user];
        }

        $user->forceFill(['failed_password_attempts' => 0, 'password_paused_until' => null])->save();

        return [LoginOutcome::Success, $user];
    }

    private function recordFailure(User $user): LoginOutcome
    {
        $user->failed_password_attempts++;

        if ($user->failed_password_attempts >= self::MAX_FAILED_PASSWORDS) {
            $user->failed_password_attempts = 0;
            $user->password_paused_until = now()->addMinutes(self::PAUSE_MINUTES);
        }

        $user->save();

        Log::channel('auth')->warning('Failed customer password login.', ['user_id' => $user->id]);

        return $user->isPasswordPaused() ? LoginOutcome::PasswordPaused : LoginOutcome::Invalid;
    }

    /**
     * The same count and pause on an address with no password account, kept
     * in the cache: the pause message comes for any address, so it does not
     * tell who has an account (14 · 14.2).
     */
    private function recordFailureWithoutAccount(string $email): LoginOutcome
    {
        $key = 'customer-password:'.sha1(Str::lower(trim($email)));

        if ($this->cache->has($key.':paused')) {
            return LoginOutcome::PasswordPaused;
        }

        $failures = (int) $this->cache->get($key) + 1;

        if ($failures < self::MAX_FAILED_PASSWORDS) {
            $this->cache->put($key, $failures, now()->addDay());

            return LoginOutcome::Invalid;
        }

        $this->cache->forget($key);
        $this->cache->put($key.':paused', true, now()->addMinutes(self::PAUSE_MINUTES));

        return LoginOutcome::PasswordPaused;
    }
}
