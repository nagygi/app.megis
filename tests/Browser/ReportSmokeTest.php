<?php

use App\Enums\DiagnosisRowKind;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    // The reading's AI drafts and the e-mails stay out of the walk.
    Queue::fake();
    Mail::fake();
});

it('shows the Értéktár with the report scores without scrolling sideways on a phone', function () {
    [$setup] = publishedReport();
    // A long word next to the score and "Részletek" pushed the card wider than the screen.
    $setup['organization']->valueSet->items()->first()->update(['meaning' => 'Megbízhatóság-központúság a mindennapokban']);

    $page = loginInBrowser($setup['leader']);
    $page->resize(390, 844);
    $page->navigate(route('client.values', $setup['organization'], false));

    $page->assertSee('Részletek')->assertNoJavaScriptErrors();
    expect($page->script('() => document.documentElement.scrollWidth <= document.documentElement.clientWidth'))->toBeTrue();
});

it('walks the leader through the published report, R0 to the Melléklet (27, 28)', function () {
    [$setup, $main] = publishedReport();
    $params = [$setup['organization'], $main->diagnosis];
    $row = $main->diagnosis->rows()->where('kind', DiagnosisRowKind::Value)->orderBy('position')->firstOrFail();

    $page = loginInBrowser($setup['leader']);
    $page->assertSee('Riportok')->assertNoAccessibilityIssues();

    foreach ([
        route('client.report.show', $params, false),
        route('client.report.summary', $params, false),
        route('client.report.internal', $params, false),
        route('client.report.chapter', [...$params, 'ertekek'], false),
        route('client.report.item', [...$params, 'ertekek', $row->id], false),
        route('client.report.compare', $params, false),
        route('client.report.closing', $params, false),
        route('client.report.appendix', $params, false),
        route('client.wisdom.index', $setup['organization'], false),
    ] as $url) {
        $page->navigate($url)
            ->assertDontSee('Nincs jogosultságod')
            ->assertNoAccessibilityIssues();
    }
});

it('opens the reading, the publishing, the Képességtár and the lists of the admin (20, 21, 07)', function () {
    [$setup, $main] = approvedReading();

    $page = adminLoginInBrowser($setup['admin']);

    foreach ([
        route('admin.main-diagnoses.reading', $main->diagnosis, false),
        route('admin.main-diagnoses.publish', $main->diagnosis, false),
        route('admin.diagnoses.capabilities', $main->diagnosis, false),
        route('admin.capabilities.index', [], false),
        route('admin.capabilities.import', [], false),
        route('admin.capability-groups.index', [], false),
        route('admin.patterns.index', [], false),
        route('admin.capability-rules.index', [], false),
        route('admin.inbox.index', [], false),
        route('admin.measurements.index', [], false),
        route('admin.diagnoses.index', [], false),
        route('admin.clients.index', [], false),
        route('admin.clients.show', $setup['leader'], false),
        route('admin.organizations.edit', $setup['organization'], false),
        route('admin.organizations.members', $setup['organization'], false),
        route('admin.organizations.processes', $setup['organization'], false),
        route('admin.organizations.diagnoses', $setup['organization'], false),
        route('admin.values.index', [], false),
        route('admin.value-categories.index', [], false),
        route('admin.diagnosis-types.index', [], false),
        route('admin.support-questions.index', [], false),
        route('admin.products.index', [], false),
        route('admin.admins.index', [], false),
        route('admin.settings.legal', [], false),
        route('admin.settings.areas', [], false),
    ] as $url) {
        $page->navigate($url)
            ->assertDontSee('Nincs jogosultságod')
            ->assertNoAccessibilityIssues();
    }
});
