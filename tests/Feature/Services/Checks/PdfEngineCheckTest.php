<?php

use App\Enums\CheckStatus;
use App\Services\Checks\PdfEngineCheck;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

it('passes when the WeasyPrint binary runs', function () {
    config(['laravel-pdf.driver' => 'weasyprint', 'laravel-pdf.weasyprint.binary' => '/usr/local/bin/weasyprint']);
    Process::fake(['*' => Process::result(output: "WeasyPrint version 70.0\n")]);

    $result = app(PdfEngineCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Ok)
        ->and($result->message)->toContain('/usr/local/bin/weasyprint')->toContain('70.0');
    Process::assertRan(fn (PendingProcess $process) => $process->command === ['/usr/local/bin/weasyprint', '--version']);
});

it('reports an error when the WeasyPrint binary is missing', function () {
    config(['laravel-pdf.driver' => 'weasyprint', 'laravel-pdf.weasyprint.binary' => '/usr/local/bin/weasyprint']);
    Process::fake(['*' => Process::result(errorOutput: 'not found', exitCode: 127)]);

    $result = app(PdfEngineCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Error)
        ->and($result->message)->toContain('/usr/local/bin/weasyprint')->toContain('LARAVEL_PDF_WEASYPRINT_BINARY');
});

it('reports an error when Gotenberg does not answer its health endpoint', function () {
    config(['laravel-pdf.driver' => 'gotenberg', 'laravel-pdf.gotenberg.url' => 'http://gotenberg:3000']);
    Http::fake(['gotenberg:3000/health' => Http::response(status: 503)]);

    expect(app(PdfEngineCheck::class)->run()->status)->toBe(CheckStatus::Error);
});

it('only names another driver', function () {
    config(['laravel-pdf.driver' => 'dompdf']);
    Process::fake();
    Http::fake();

    $result = app(PdfEngineCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Warning)
        ->and($result->message)->toContain('dompdf');
    Process::assertNothingRan();
    Http::assertNothingSent();
});
