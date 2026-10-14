<?php

namespace App\Services\Checks;

class TurnstileConfigurationCheck implements Check
{
    /**
     * Cloudflare's documented test keys start with "1x", "2x" or "3x" followed by zeros.
     */
    private const TEST_KEY_PATTERN = '/^[123]x0{10,}/';

    public function run(): CheckResult
    {
        $label = __('checks.turnstile.label');
        $siteKey = (string) config('services.turnstile.site_key');
        $secretKey = (string) config('services.turnstile.secret_key');

        if ($siteKey === '' || $secretKey === '') {
            return CheckResult::error($label, __('checks.turnstile.missing'));
        }

        if (app()->isProduction() && (preg_match(self::TEST_KEY_PATTERN, $siteKey) || preg_match(self::TEST_KEY_PATTERN, $secretKey))) {
            return CheckResult::error($label, __('checks.turnstile.test_keys'));
        }

        return CheckResult::ok($label, __('checks.turnstile.ok'));
    }
}
