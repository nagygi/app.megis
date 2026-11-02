<?php

use App\Models\MainDiagnosis;
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
