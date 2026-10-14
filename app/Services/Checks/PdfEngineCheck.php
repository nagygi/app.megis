<?php

namespace App\Services\Checks;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * The PDF engine of the report and the Útravaló (28 · 28.3): Gotenberg
 * answers its health endpoint; another driver is only named.
 */
class PdfEngineCheck implements Check
{
    public function run(): CheckResult
    {
        $label = __('checks.pdf.label');
        $driver = (string) config('laravel-pdf.driver');

        if ($driver !== 'gotenberg') {
            return CheckResult::warning($label, __('checks.pdf.other', ['driver' => $driver]));
        }

        $url = rtrim((string) config('laravel-pdf.gotenberg.url'), '/');

        try {
            $healthy = Http::timeout(5)->get($url.'/health')->successful();
        } catch (Throwable) {
            $healthy = false;
        }

        return $healthy
            ? CheckResult::ok($label, __('checks.pdf.ok', ['url' => $url]))
            : CheckResult::error($label, __('checks.pdf.unreachable', ['url' => $url]));
    }
}
