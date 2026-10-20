<?php

use App\Enums\DiagnosisRowKind;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    Mail::fake();
});

/**
 * Opens each page at 390px and fails on the first one wider than the screen.
 *
 * @param  array<string, string>  $paths
 */
function assertNoSidewaysScroll(mixed $page, array $paths): void
{
    $page->resize(390, 844);

    foreach ($paths as $path) {
        $page->navigate($path);
        $width = $page->script('() => document.documentElement.scrollWidth');

        expect($width)->toBeLessThanOrEqual(390, "{$path} is {$width}px wide on a 390px phone");
    }
}

it('fits the customer pages on a phone', function () {
    [$setup, $main] = publishedReport();
    $params = [$setup['organization'], $main->diagnosis];
    $row = $main->diagnosis->rows()->where('kind', DiagnosisRowKind::Value)->orderBy('position')->firstOrFail();

    $page = loginInBrowser($setup['leader']);

    assertNoSidewaysScroll($page, [
        route('client.home', $setup['organization'], false),
        route('client.diagnosis.show', $params, false),
        route('client.diagnosis.sajat-ertekek', $params, false),
        route('client.diagnosis.megeles', $params, false),
        route('client.report.show', $params, false),
        route('client.report.chapter', [...$params, 'ertekek'], false),
        route('client.report.item', [...$params, 'ertekek', $row->id], false),
        route('client.report.compare', $params, false),
        route('client.report.appendix', $params, false),
        route('client.values', $setup['organization'], false),
        route('client.profile', $setup['organization'], false),
        route('client.wisdom.index', $setup['organization'], false),
    ]);
    $page->assertNoJavaScriptErrors();
});

it('fits the admin lists on a phone: wide tables scroll inside their box', function () {
    Organization::factory()->count(3)->create();
    $page = adminLoginInBrowser(User::factory()->admin()->create());

    assertNoSidewaysScroll($page, [
        route('admin.organizations.index', absolute: false),
        route('admin.clients.index', absolute: false),
        route('admin.values.index', absolute: false),
        route('admin.capabilities.index', absolute: false),
        route('admin.inbox.index', absolute: false),
    ]);
    $page->assertNoJavaScriptErrors();
});
