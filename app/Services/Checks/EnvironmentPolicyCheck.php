<?php

namespace App\Services\Checks;

/**
 * Verifies that indexing, debugging, URL and mail settings fit the environment
 * (docs/uzemeltetes.md · 2 and 7).
 */
class EnvironmentPolicyCheck implements Check
{
    public function run(): CheckResult
    {
        $label = __('checks.environment_policy.label');
        $errors = [];
        $warnings = [];

        if (app()->isProduction()) {
            if (config('app.debug')) {
                $errors[] = __('checks.environment_policy.debug_on');
            }

            if (! str_starts_with((string) config('app.url'), 'https://')) {
                $errors[] = __('checks.environment_policy.url_not_https');
            }
        }

        if (app()->environment('staging')) {
            $hasBasicAuth = filled(config('megis.staging.basic_auth_user')) && filled(config('megis.staging.basic_auth_password'));

            if (! $hasBasicAuth && blank(config('megis.staging.allowed_ips'))) {
                $warnings[] = __('checks.environment_policy.staging_unprotected');
            }
        }

        if ($errors !== []) {
            return CheckResult::error($label, implode(' ', $errors));
        }

        if ($warnings !== []) {
            return CheckResult::warning($label, implode(' ', $warnings));
        }

        return CheckResult::ok($label, __('checks.environment_policy.ok', ['environment' => app()->environment()]));
    }
}
