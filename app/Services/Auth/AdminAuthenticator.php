<?php

namespace App\Services\Auth;

use App\Enums\UserRole;
use App\Mail\AdminLockedMail;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Admin password check with lockout (01 · 1.2).
 */
class AdminAuthenticator
{
    public const MAX_FAILED_ATTEMPTS = 10;

    public function __construct(private Hasher $hasher) {}

    /**
     * @return array{0: LoginOutcome, 1: ?User}
     */
    public function attempt(string $email, string $password): array
    {
        $user = User::query()->admins()->where('email', Str::lower(trim($email)))->first();

        if ($user === null || ! $user->isActive() || ! $user->isActivated()) {
            return [LoginOutcome::Invalid, null];
        }

        if ($user->locked_at !== null) {
            return [LoginOutcome::Locked, $user];
        }

        if ($user->password === null || ! $this->hasher->check($password, $user->password)) {
            $this->recordFailure($user);

            return [$user->locked_at !== null ? LoginOutcome::Locked : LoginOutcome::Invalid, $user];
        }

        $user->forceFill([
            'failed_login_attempts' => 0,
            'last_login_at' => now(),
        ])->save();

        Log::channel('auth')->info('Admin logged in.', ['user_id' => $user->id]);

        return [LoginOutcome::Success, $user];
    }

    public function unlock(User $user): void
    {
        $user->forceFill([
            'locked_at' => null,
            'failed_login_attempts' => 0,
        ])->save();

        Log::channel('auth')->info('Admin account unlocked.', ['user_id' => $user->id]);
    }

    private function recordFailure(User $user): void
    {
        $user->failed_login_attempts++;

        if ($user->failed_login_attempts >= self::MAX_FAILED_ATTEMPTS) {
            $user->locked_at = now();
        }

        $user->save();

        Log::channel('auth')->warning('Failed admin login.', [
            'user_id' => $user->id,
            'attempts' => $user->failed_login_attempts,
        ]);

        if ($user->locked_at !== null) {
            Log::channel('auth')->warning('Admin account locked.', ['user_id' => $user->id]);
            $this->alertSuperadmins($user);
        }
    }

    /**
     * Anyone who knows an admin's address could lock them out, so the
     * superadmins hear about it right away.
     */
    private function alertSuperadmins(User $lockedUser): void
    {
        $superadmins = User::query()
            ->where('role', UserRole::Superadmin)
            ->whereNull('deactivated_at')
            ->whereKeyNot($lockedUser->id)
            ->pluck('email');

        if ($superadmins->isNotEmpty()) {
            Mail::to($superadmins->all())->queue(new AdminLockedMail($lockedUser));
        }
    }
}
