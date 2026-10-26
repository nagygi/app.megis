<?php

use App\Enums\DiagnosisStatus;
use App\Models\Diagnosis;
use App\Models\DiagnosisRow;
use App\Models\DiagnosisText;
use App\Models\Organization;
use App\Models\User;

it('writes the recommendation with a subheading and previews the user\'s report before publishing (04 · 4.6)', function () {
    $organization = Organization::factory()->create();
    $diagnosis = Diagnosis::factory()->forUser(customerOf($organization), $organization)->create(['status' => DiagnosisStatus::PendingApproval]);
    DiagnosisRow::factory()->for($diagnosis)->scored(5.0)->create();

    $page = adminLoginInBrowser(User::factory()->admin()->create());
    $page->navigate(route('admin.diagnoses.show', $diagnosis, false));
    $page->typeSlowly('.CodeMirror textarea', 'Hol kezdjük', 20);
    $page->click('.megis-md-heading');
    $page->press('Mentem')->assertSee('A javaslat mentve.')->assertNoJavaScriptErrors();

    expect(DiagnosisText::query()->where('diagnosis_id', $diagnosis->id)->where('key', 'recommendation')->value('text'))->toBe('### Hol kezdjük');

    $page->navigate(route('admin.diagnoses.preview', $diagnosis, false))
        ->assertSee('Így látja:')
        ->withinFrame('iframe', fn ($frame) => $frame->assertSee('A te diagnózisod'))
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();
});

it('walks the user through the published report of their diagnosis (04 · 4.6)', function () {
    $organization = Organization::factory()->create();
    $user = customerOf($organization);
    $diagnosis = Diagnosis::factory()->forUser($user, $organization)->published()->create(['name' => 'Értéktükör · 2026.10.26.']);
    $row = DiagnosisRow::factory()->for($diagnosis)->scored(5.0, 3.0)->create(['quotes' => [['detection_id' => null, 'speaker' => 'Beszélő 1', 'quote' => 'Nekem az számít, hogy tükörbe tudjak nézni.']]]);
    DiagnosisText::query()->create(['diagnosis_id' => $diagnosis->id, 'key' => 'recommendation', 'text' => "### Hol kezdjük\n\nA **nyíltsággal**.", 'status' => 'approved', 'approved_at' => now()]);
    $params = [$organization, $diagnosis];

    $page = loginInBrowser($user);
    $page->assertSee('Értéktükör · 2026.10.26.');

    foreach ([
        route('client.report.show', $params, false),
        route('client.report.chapter', [...$params, 'ertekek'], false),
        route('client.report.item', [...$params, 'ertekek', $row->id], false),
        route('client.report.closing', $params, false),
        route('client.report.appendix', $params, false),
    ] as $url) {
        $page->navigate($url)
            ->assertDontSee('Nincs jogosultságod')
            ->assertNoJavaScriptErrors()
            ->assertNoAccessibilityIssues();
    }
});
