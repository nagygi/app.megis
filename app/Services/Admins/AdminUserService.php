<?php

namespace App\Services\Admins;

use App\Enums\TokenPurpose;
use App\Enums\UserRole;
use App\Mail\AdminInvitationMail;
use App\Mail\AdminPasswordResetMail;
use App\Mail\PasswordChangedMail;
use App\Models\User;
use App\Models\UserToken;
use App\Services\Auth\TokenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Admin account lifecycle (01 · 1.3): invitation, password reset by another
 * admin, deactivation and setting the password from the e-mailed link.
 */
class AdminUserService
{
    private const ADMIN_LINK_PURPOSES = [TokenPurpose::AdminInvitation, TokenPurpose::AdminPasswordReset];

    public function __construct(private TokenService $tokens) {}

    /**
     * An existing account (e.g. a customer) gets the role instead of a second account.
     *
     * @param  array{first_name: string, last_name: string, email: string}  $data
     */
    public function invite(array $data, UserRole $role, ?User $actor): User
    {
        $user = DB::transaction(function () use ($data, $role): User {
            $user = User::query()->firstWhere('email', Str::lower(trim($data['email']))) ?? new User;
            $user->fill([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
            ]);
            $user->role = $role;
            $user->save();

            return $user;
        });

        $this->sendInvitation($user, $actor);

        return $user;
    }

    public function sendInvitation(User $user, ?User $actor): void
    {
        $issued = $this->tokens->issue($user, TokenPurpose::AdminInvitation, creator: $actor);

        Mail::to($user->email)->queue(new AdminInvitationMail($user, $actor, $issued->plainToken));
    }

    /**
     * The old password stops working at once and every session ends; the
     * starting admin never sees a password.
     */
    public function resetPassword(User $user, User $actor): void
    {
        $user->forceFill(['password' => null])->save();
        $user->invalidateSessions();

        $issued = $this->tokens->issue($user, TokenPurpose::AdminPasswordReset, creator: $actor);
        $this->tokens->invalidatePending($user, TokenPurpose::AdminInvitation);

        Mail::to($user->email)->queue(new AdminPasswordResetMail($user, $actor, $issued->plainToken));

        Log::channel('auth')->info('Admin password reset started.', ['user_id' => $user->id, 'actor_id' => $actor->id]);
    }

    /**
     * The user is logged out at once; an unused invitation link stops working.
     */
    public function deactivate(User $user): void
    {
        $user->forceFill(['deactivated_at' => now()])->save();
        $user->invalidateSessions();
        $this->tokens->invalidatePending($user, self::ADMIN_LINK_PURPOSES);

        Log::channel('auth')->info('Admin deactivated.', ['user_id' => $user->id]);
    }

    /**
     * After reactivation the old password works again.
     */
    public function activate(User $user): void
    {
        $user->forceFill(['deactivated_at' => null])->save();
    }

    public function findUsableLink(string $plainToken): ?UserToken
    {
        $token = $this->tokens->find($plainToken, TokenPurpose::AdminInvitation)
            ?? $this->tokens->find($plainToken, TokenPurpose::AdminPasswordReset);

        if ($token === null || ! $token->isPending()) {
            return null;
        }

        $user = $token->user;

        return $user->isAdmin() && $user->isActive() ? $token : null;
    }

    public function completePasswordSetup(UserToken $token, string $password): User
    {
        $user = $token->user;

        DB::transaction(function () use ($token, $user, $password): void {
            $this->tokens->consume($token);

            $user->password = $password;
            $user->activated_at ??= now();
            $user->save();
            $user->invalidateSessions();
        });

        if ($token->purpose === TokenPurpose::AdminPasswordReset) {
            Mail::to($user->email)->queue(new PasswordChangedMail($user, now()->toImmutable()));
        }

        return $user->refresh();
    }
}
