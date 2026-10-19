<?php

namespace App\Services\Checks;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * The PDF engine of the report and the Útravaló (28 · 28.3): Gotenberg
 * answers its health endpoint, or the WeasyPrint binary runs (hosts without
 * Docker, deploy.md); another driver is only named.
 */
class PdfEngineCheck implements Check
{
    public function run(): CheckResult
    {
        $label = __('checks.pdf.label');
        $driver = (string) config('laravel-pdf.driver');

        return match ($driver) {
            'gotenberg' => $this->gotenberg($label),
            'weasyprint' => $this->weasyPrint($label),
            default => CheckResult::warning($label, __('checks.pdf.other', ['driver' => $driver])),
        };
    }

    private function gotenberg(string $label): CheckResult
    {
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

    private function weasyPrint(string $label): CheckResult
    {
        $binary = (string) config('laravel-pdf.weasyprint.binary');

        try {
            $result = Process::timeout(10)->run([$binary, '--version']);
        } catch (Throwable) {
            $result = null;
        }

        return $result?->successful()
            ? CheckResult::ok($label, __('checks.pdf.weasyprint_ok', ['binary' => $binary, 'version' => trim($result->output())]))
            : CheckResult::error($label, __('checks.pdf.weasyprint_missing', ['binary' => $binary]));
    }
}
