<?php

use App\Models\Diagnosis;
use App\Models\MainDiagnosis;
use App\Models\Organization;
use App\Models\Process;
use Illuminate\Testing\TestResponse;

/**
 * The admin layout's header.
 */
function adminHeader(TestResponse $response): string
{
    preg_match('/<header.*?<\/header>/s', $response->assertOk()->getContent(), $header);

    return $header[0] ?? '';
}

/**
 * The tabs lit in the header, URL => aria-current.
 *
 * @return array<string, string>
 */
function litHeaderTabs(TestResponse $response): array
{
    preg_match_all('/<a\s+href="([^"]*)"[^>]*aria-current="([a-z]+)"/s', adminHeader($response), $links, PREG_SET_ORDER);

    return collect($links)->mapWithKeys(fn (array $link) => [html_entity_decode($link[1]) => $link[2]])->all();
}

beforeEach(fn () => actingAsAdmin());

it('keeps the organization\'s name and tabs on the pages under a tab, lighting the tab they sit under', function (Closure $page) {
    $organization = Organization::factory()->create(['name' => 'Alma Kft']);
    [$url, $tab] = $page($organization);

    $response = $this->get($url);

    expect(adminHeader($response))->toContain('Alma Kft')
        ->and(litHeaderTabs($response))->toBe([route($tab, $organization) => 'true']);
})->with([
    'an invitation' => fn (Organization $organization) => [route('admin.organizations.members.create', $organization), 'admin.organizations.members'],
    'the occasions of a process' => fn (Organization $organization) => [route('admin.organizations.process-occasions', [$organization, Process::factory()->for($organization)->create()]), 'admin.organizations.processes'],
    'A1' => fn (Organization $organization) => [route('admin.main-diagnoses.create', ['szervezet' => $organization->id]), 'admin.organizations.diagnoses'],
    'a main diagnosis' => fn (Organization $organization) => [
        route('admin.diagnoses.show', MainDiagnosis::factory()->create(['diagnosis_id' => Diagnosis::factory()->ofType('main')->for($organization)])->diagnosis),
        'admin.organizations.diagnoses',
    ],
]);

it('runs the breadcrumbs above the title from the organization\'s tab down to the page itself', function () {
    $organization = Organization::factory()->create();

    $content = $this->get(route('admin.main-diagnoses.create', ['szervezet' => $organization->id]))->assertOk()->getContent();
    preg_match('/<nav aria-label="Útvonal".*?<\/nav>/s', $content, $nav);

    expect(strip_tags($nav[0] ?? ''))->toMatch('/^\s*Diagnózisok\s+Diagnózis indítása\s*$/u')
        ->and($nav[0] ?? '')->toContain('<span aria-current="page"');
});

it('lights the organization\'s tab as the page on the tab itself', function () {
    $organization = Organization::factory()->create();

    expect(litHeaderTabs($this->get(route('admin.organizations.teams', $organization))))
        ->toBe([route('admin.organizations.teams', $organization) => 'page']);
});

it('names the menu group in the header outside an organization', function () {
    $header = adminHeader($this->get(route('admin.values.index')));

    expect($header)->toContain(__('admin.nav.value_library'))
        ->and($header)->not->toContain('aria-current');
});
