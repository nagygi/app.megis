<?php

namespace App\Rules;

use App\Services\Turnstile\TurnstileVerifier;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates the "cf-turnstile-response" field of a form against Cloudflare.
 */
class Turnstile implements ValidationRule
{
    public const FIELD = 'cf-turnstile-response';

    /**
     * Runs even when the field is missing or empty, so a form that forgets
     * "required" cannot skip the captcha.
     */
    public bool $implicit = true;

    public function __construct(private ?string $ipAddress = null) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $ipAddress = $this->ipAddress ?? request()->ip();

        if (! is_string($value) || ! app(TurnstileVerifier::class)->verify($value, $ipAddress)) {
            $fail('validation.turnstile')->translate();
        }
    }
}
