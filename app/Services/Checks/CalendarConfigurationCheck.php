<?php

namespace App\Services\Checks;

/**
 * The Google and Microsoft calendar apps (09 · 9.2). Without keys the option
 * is not offered; the iCal link, the ICS file and the conversation still work.
 */
class CalendarConfigurationCheck implements Check
{
    public function run(): CheckResult
    {
        $label = __('checks.calendar.label');
        $missing = collect(['google' => 'GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET', 'microsoft' => 'MICROSOFT_CLIENT_ID / MICROSOFT_CLIENT_SECRET'])
            ->filter(fn (string $variables, string $provider) => blank(config('services.'.$provider.'.client_id')) || blank(config('services.'.$provider.'.client_secret')));

        if ($missing->isNotEmpty()) {
            return CheckResult::warning($label, __('checks.calendar.missing', ['variables' => $missing->implode(', ')]));
        }

        return CheckResult::ok($label, __('checks.calendar.ok'));
    }
}
