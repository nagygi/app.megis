<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Login limits shown as a form error instead of a 429 page. The key combines
 * the address and the IP, so one person cannot lock out a whole office.
 */
trait ThrottlesAttempts
{
    /**
     * @throws ValidationException
     */
    protected function throttle(string $limiter, string $email, string $ip, int $maxAttempts, int $decaySeconds, string $field = 'email'): string
    {
        $key = $limiter.':'.Str::transliterate(Str::lower(trim($email))).'|'.$ip;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw ValidationException::withMessages([
                $field => __('auth_ui.throttled', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        RateLimiter::hit($key, $decaySeconds);

        return $key;
    }
}
