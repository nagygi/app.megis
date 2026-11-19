<?php

use App\Enums\MainDiagnosisStage;
use App\Models\MainDiagnosis;
use App\Services\MainDiagnoses\StageMachine;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

/**
 * The links lit as the current page (aria-current), the band's tab among them.
 *
 * @return list<string>
 */
function currentLinks(TestResponse $response): array
{
    preg_match_all('/<a\s+href="([^"]*)"[^>]*aria-current="page"/s', $response->getContent(), $matches);

    return array_map('html_entity_decode', $matches[1]);
}

it('lights the tab of the page in the band of a main diagnosis, and only that one', function (string $routeName) {
    actingAsAdmin();
    $diagnosis = MainDiagnosis::factory()->create()->diagnosis;
    $tabs = ['admin.diagnoses.show', 'admin.main-diagnoses.edit', 'admin.main-diagnoses.subdiagnoses', 'admin.diagnoses.capabilities'];

    $lit = currentLinks($this->get(route($routeName, $diagnosis))->assertOk());

    expect($lit)->toContain(route($routeName, $diagnosis))
        ->and(array_intersect($lit, array_map(fn (string $tab) => route($tab, $diagnosis), array_diff($tabs, [$routeName]))))->toBe([]);
})->with([
    'Áttekintés' => 'admin.diagnoses.show',
    'Beállítás' => 'admin.main-diagnoses.edit',
    'Részdiagnózisok' => 'admin.main-diagnoses.subdiagnoses',
    'Képességek' => 'admin.diagnoses.capabilities',
]);

it('keeps the coming tabs in their place, locked, and opens Publikálás with the reading (19 · 19.1, 20 · 20.5)', function () {
    Queue::fake();
    [$setup, $main] = runningDiagnosis();
    actingAsAdmin($setup['admin']);
    $readingUrl = route('admin.main-diagnoses.reading', $main->diagnosis);
    $publishUrl = route('admin.main-diagnoses.publish', $main->diagnosis);

    $this->get(route('admin.diagnoses.show', $main->diagnosis))
        ->assertSee('Az olvasat indításával nyílik meg: a Futás fülön indítod.')
        ->assertSee('Az olvasat szakaszban nyílik meg.')
        ->assertDontSee('href="'.$readingUrl.'"', false)
        ->assertDontSee('href="'.$publishUrl.'"', false);

    app(StageMachine::class)->transition($main, MainDiagnosisStage::Reading, $setup['admin']);

    // Not everything is approved yet: Publikálás opens anyway and tells what is missing.
    $this->get(route('admin.diagnoses.show', $main->diagnosis))
        ->assertSee('href="'.$publishUrl.'"', false)
        ->assertDontSee('Az olvasat szakaszban nyílik meg.');
});
