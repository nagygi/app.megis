<?php

namespace App\Services\Auth;

use App\Models\UserToken;

/**
 * A freshly issued token with the plain values that go into the e-mail.
 * The plain values are never stored.
 */
final readonly class IssuedToken
{
    public function __construct(
        public UserToken $token,
        public string $plainToken,
        public ?string $plainCode = null,
    ) {}
}
