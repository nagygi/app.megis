<?php

namespace App\Services\Checks;

class RequiredEnvironmentCheck implements Check
{
    public function run(): CheckResult
    {
        $label = __('checks.required_environment.label');

        $missing = collect(config('megis.required_config'))
            ->filter(fn (string $environmentName, string $configKey): bool => blank(config($configKey)))
            ->values();

        if ($missing->isNotEmpty()) {
            return CheckResult::error($label, __('checks.required_environment.error', ['names' => $missing->implode(', ')]));
        }

        return CheckResult::ok($label, __('checks.required_environment.ok'));
    }
}
