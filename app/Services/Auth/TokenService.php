<?php

namespace App\Services\Auth;

use App\Enums\TokenPurpose;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Support\Str;

/**
 * Issues and checks one-time links and codes.
 *
 * Values are stored as an HMAC keyed with the application key, so a leaked
 * database does not reveal a 6-digit code by brute force.
 */
class TokenService
{
    /**
     * Five wrong codes invalidate the code (14 · 14.2).
     */
    public const MAX_CODE_ATTEMPTS = 5;

    /**
     * A new token always invalidates the previous ones of the same purpose
     * (and organization, for invitations).
     */
    public function issue(User $user, TokenPurpose $purpose, ?Organization $organization = null, ?User $creator = null): IssuedToken
    {
        $this->invalidatePending($user, $purpose, $organization);

        $plainToken = Str::random(64);
        $plainCode = $purpose->hasCode()
            ? str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT)
            : null;

        $token = UserToken::create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'organization_id' => $organization?->id,
            'token_hash' => $this->hash($plainToken),
            'code_hash' => $plainCode === null ? null : $this->hash($plainCode),
            'expires_at' => now()->add($purpose->lifetime()),
            'created_by' => $creator?->id,
        ]);

        return new IssuedToken($token, $plainToken, $plainCode);
    }

    /**
     * @param  TokenPurpose|list<TokenPurpose>  $purposes
     */
    public function invalidatePending(User $user, TokenPurpose|array $purposes, ?Organization $organization = null): void
    {
        $user->tokens()
            ->pending()
            ->whereIn('purpose', is_array($purposes) ? $purposes : [$purposes])
            ->when($organization, fn ($query) => $query->where('organization_id', $organization->id))
            ->update(['invalidated_at' => now()]);
    }

    /**
     * Finds a token by its link value regardless of its state; callers check isPending().
     */
    public function find(string $plainToken, TokenPurpose $purpose): ?UserToken
    {
        return UserToken::query()
            ->where('token_hash', $this->hash($plainToken))
            ->where('purpose', $purpose)
            ->first();
    }

    /**
     * The latest still pending token of a user for a purpose.
     */
    public function pendingFor(User $user, TokenPurpose $purpose): ?UserToken
    {
        return $user->tokens()->pending()->where('purpose', $purpose)->latest('id')->first();
    }

    public function consume(UserToken $token): void
    {
        $token->forceFill(['consumed_at' => now()])->save();
    }

    /**
     * Compares a typed code. A wrong code counts as an attempt; the last allowed
     * wrong attempt invalidates the token.
     */
    public function checkCode(UserToken $token, string $code): bool
    {
        if (! $token->isPending() || $token->code_hash === null) {
            return false;
        }

        if (hash_equals($token->code_hash, $this->hash(trim($code)))) {
            return true;
        }

        $token->attempts++;

        if ($token->attempts >= self::MAX_CODE_ATTEMPTS) {
            $token->invalidated_at = now();
        }

        $token->save();

        return false;
    }

    public function hash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
