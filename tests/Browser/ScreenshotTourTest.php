<?php

use App\Enums\DiagnosisRowKind;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

/*
 * Screenshots of the main pages on a phone and on a desktop, to compare the UI
 * before and after a change. Not part of the suite (phpunit.xml excludes the
 * group): `php artisan test --group=screenshots`, with UI_TOUR naming the set
 * (default "before"). Pest empties tests/Browser/Screenshots on every run, so
 * copy the images out afterwards.
 */

beforeEach(function () {
    Queue::fake();
    Mail::fake();
});

/**
 * Screenshots the current page at 390 and 1280px; with UI_A11Y=1 it checks
 * every page for accessibility issues too.
 */
function tourShot(mixed $page, string $name): void
{
    $set = getenv('UI_TOUR') ?: 'before';

    foreach ([390 => 844, 1280 => 800] as $width => $height) {
        $page->resize($width, $height);
        $page->screenshot(true, "{$set}-{$name}-{$width}");

        if (getenv('UI_A11Y')) {
            $page->assertNoAccessibilityIssues();
        }
    }
}

/**
 * Opens each page and screenshots it.
 *
 * @param  array<string, string>  $pages  name => path
 */
function tourPages(mixed $page, array $pages): void
{
    foreach ($pages as $name => $path) {
        $page->navigate($path);
        tourShot($page, $name);
    }
}

it('tours the guest pages', function () {
    fakeTurnstile();

    $page = visit('/');
    tourShot($page, 'guest-01-home');

    tourPages($page, [
        'guest-02-login' => route('client.login', absolute: false),
        'guest-03-mirror' => '/ertektukor',
    ]);
})->group('screenshots');

it('tours the leader\'s steps before the diagnosis starts', function () {
    $setup = diagnosisTeam(peers: 1);
    $main = startMainDiagnosis($setup);
    $params = [$setup['organization'], $main->diagnosis];

    $page = loginInBrowser($setup['leader']);
    tourShot($page, 'leader-01-home');

    tourPages($page, [
        'leader-02-own-values' => route('client.diagnosis.sajat-ertekek', $params, false),
        'leader-03-organization-values' => route('client.diagnosis.szervezet-ertekei', $params, false),
        'leader-04-brand' => route('client.diagnosis.markad-alapja', $params, false),
        'leader-05-experience' => route('client.diagnosis.megeles', $params, false),
        'leader-06-processes' => route('client.diagnosis.folyamatok', $params, false),
        'leader-07-visibility' => route('client.diagnosis.ki-lat-mit', $params, false),
        'leader-08-profile' => route('client.profile', $setup['organization'], false),
    ]);
})->group('screenshots');

it('tours a running diagnosis as the leader and as a peer', function () {
    [$setup, $main] = runningDiagnosis();
    $params = [$setup['organization'], $main->diagnosis];

    $page = loginInBrowser($setup['leader']);
    tourShot($page, 'running-01-leader-home');
    tourPages($page, [
        'running-02-contribute' => route('client.diagnosis.contribute', $params, false),
        'running-03-situations' => route('client.diagnosis.situations', $params, false),
        'running-04-mirror' => route('client.mirror.record', $setup['organization'], false),
    ]);
})->group('screenshots');

it('tours a running diagnosis as a peer', function () {
    [$setup] = runningDiagnosis();

    $page = loginInBrowser($setup['peers'][0]);
    tourShot($page, 'running-05-peer-home');
})->group('screenshots');

it('tours the published report and the libraries', function () {
    [$setup, $main] = publishedReport();
    $params = [$setup['organization'], $main->diagnosis];
    $row = $main->diagnosis->rows()->where('kind', DiagnosisRowKind::Value)->orderBy('position')->firstOrFail();

    $page = loginInBrowser($setup['leader']);
    tourShot($page, 'report-01-home');

    tourPages($page, [
        'report-02-answer' => route('client.report.show', $params, false),
        'report-03-summary' => route('client.report.summary', $params, false),
        'report-04-chapter' => route('client.report.chapter', [...$params, 'ertekek'], false),
        'report-05-item' => route('client.report.item', [...$params, 'ertekek', $row->id], false),
        'report-06-closing' => route('client.report.closing', $params, false),
        'report-07-appendix' => route('client.report.appendix', $params, false),
        'report-08-takeaway' => route('client.diagnosis.takeaway', $params, false),
        'library-01-values' => route('client.values', $setup['organization'], false),
        'library-02-processes' => route('client.processes', $setup['organization'], false),
        'library-03-wisdom' => route('client.wisdom.index', $setup['organization'], false),
    ]);
})->group('screenshots');

it('tours the confirmation and the toast', function () {
    Organization::factory()->create(['name' => 'Acme Kft.']);
    $page = adminLoginInBrowser(User::factory()->admin()->create());

    $page->click('[aria-label="Műveletek: Acme Kft."]')->click('Inaktiválom');
    tourShot($page, 'feedback-01-confirm');

    $page->press('Igen');
    $page->assertVisible('[aria-live="polite"] [x-text="toast.message"]');
    tourShot($page, 'feedback-02-toast');
})->group('screenshots');

it('tours the pager of an admin list', function () {
    Organization::factory()->count(30)->create();
    $page = adminLoginInBrowser(User::factory()->admin()->create());
    tourShot($page, 'pager-01-organizations');
})->group('screenshots');

it('tours the admin', function () {
    [$setup, $main] = approvedReading();
    $diagnosis = $main->diagnosis;
    $organization = $setup['organization'];

    $page = adminLoginInBrowser($setup['admin']);
    tourShot($page, 'admin-01-organizations');

    tourPages($page, [
        'admin-02-organization' => route('admin.organizations.edit', $organization, false),
        'admin-03-members' => route('admin.organizations.members', $organization, false),
        'admin-04-organization-values' => route('admin.organizations.values', $organization, false),
        'admin-05-diagnoses' => route('admin.diagnoses.index', absolute: false),
        'admin-06-main-show' => route('admin.diagnoses.show', $diagnosis, false),
        'admin-07-main-settings' => route('admin.main-diagnoses.edit', $diagnosis, false),
        'admin-08-main-subdiagnoses' => route('admin.main-diagnoses.subdiagnoses', $diagnosis, false),
        'admin-09-main-run' => route('admin.main-diagnoses.run', $diagnosis, false),
        'admin-10-main-reading' => route('admin.main-diagnoses.reading', $diagnosis, false),
        'admin-11-main-publish' => route('admin.main-diagnoses.publish', $diagnosis, false),
        'admin-12-values' => route('admin.values.index', absolute: false),
        'admin-13-capabilities' => route('admin.capabilities.index', absolute: false),
        'admin-14-inbox' => route('admin.inbox.index', absolute: false),
        'admin-15-clients' => route('admin.clients.index', absolute: false),
    ]);
})->group('screenshots');
