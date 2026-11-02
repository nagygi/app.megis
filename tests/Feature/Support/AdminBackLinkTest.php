<?php

use App\Models\Diagnosis;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\MirrorSession;
use App\Models\User;
use Illuminate\Testing\TestResponse;

/**
 * The Vissza before the breadcrumbs (x-admin.breadcrumbs): where it leads;
 * null when the page was opened from a page of its breadcrumbs.
 */
function backLink(TestResponse $response): ?string
{
    $found = preg_match('/<a id="admin-back" href="([^"]*)"/', $response->getContent(), $match);

    return $found ? html_entity_decode($match[1]) : null;
}

/**
 * The breadcrumbs above the page's title, label => URL.
 *
 * @return array<string, string>
 */
function pageCrumbs(TestResponse $response): array
{
    preg_match('/<nav aria-label="Útvonal".*?<\/nav>/s', $response->getContent(), $nav);
    preg_match_all('/<a href="([^"]*)"[^>]*>\s*<span class="truncate">(.*?)<\/span>/s', $nav[0] ?? '', $links, PREG_SET_ORDER);

    return collect($links)->mapWithKeys(fn (array $link) => [html_entity_decode($link[2]) => html_entity_decode($link[1])])->all();
}

function subdiagnosisOf(MainDiagnosis $main): Diagnosis
{
    return Diagnosis::factory()->create(['organization_id' => $main->diagnosis->organization_id, 'parent_id' => $main->diagnosis_id]);
}

beforeEach(fn () => actingAsAdmin());

it('leads back to the admin page the diagnosis was opened from, with its filters', function () {
    $diagnosis = subdiagnosisOf(MainDiagnosis::factory()->create());
    $list = route('admin.diagnoses.index', ['kereses' => 'Alma', 'page' => 2]);

    $response = $this->get(route('admin.diagnoses.show', $diagnosis), ['referer' => $list]);

    $response->assertOk();
    expect(backLink($response))->toBe($list);
});

it('keeps where a main diagnosis was opened from while switching its tabs', function () {
    $main = MainDiagnosis::factory()->create();
    $client = route('admin.clients.show', User::factory()->create());
    $this->get(route('admin.diagnoses.show', $main->diagnosis), ['referer' => $client]);

    $response = $this->get(route('admin.main-diagnoses.subdiagnoses', $main->diagnosis), ['referer' => route('admin.diagnoses.show', $main->diagnosis)]);

    expect(backLink($response))->toBe($client);
});

it('keeps where a page was opened from when coming back from a page opened there', function () {
    $main = MainDiagnosis::factory()->create();
    $subdiagnosis = subdiagnosisOf($main);
    $subdiagnoses = route('admin.main-diagnoses.subdiagnoses', $main->diagnosis);
    $this->get($subdiagnoses, ['referer' => route('admin.diagnoses.index')]);
    $toSubdiagnosis = $this->get(route('admin.diagnoses.show', $subdiagnosis), ['referer' => $subdiagnoses]);

    $response = $this->get($subdiagnoses, ['referer' => route('admin.diagnoses.show', $subdiagnosis)]);

    expect(backLink($toSubdiagnosis))->toBeNull()
        ->and(pageCrumbs($toSubdiagnosis)['Részdiagnózisok'])->toBe($subdiagnoses)
        ->and(backLink($response))->toBe(route('admin.diagnoses.index'));
});

it('hands where a create form was opened from on to the page it redirects to', function () {
    $material = Material::factory()->transcribed([['speaker' => 'Beszélő 1', 'text' => 'Szöveg.']])->create(['context' => 'admin_upload', 'status' => 'awaiting_speakers']);
    $list = route('admin.diagnoses.index', ['kereses' => 'Alma']);
    $this->get(route('admin.diagnoses.upload'), ['referer' => $list]);

    $response = $this->get(route('admin.materials.show', $material), ['referer' => route('admin.diagnoses.upload')]);

    expect(backLink($response))->toBe($list);
});

it('leads up the breadcrumbs without a Vissza when a subdiagnosis is not opened from an admin page', function (?string $referer) {
    $main = MainDiagnosis::factory()->create();
    $subdiagnosis = subdiagnosisOf($main);
    $this->get(route('admin.diagnoses.show', $subdiagnosis), ['referer' => route('admin.diagnoses.index')]);

    $response = $this->get(route('admin.diagnoses.show', $subdiagnosis), array_filter(['referer' => $referer]));

    expect(backLink($response))->toBeNull()
        ->and(pageCrumbs($response)['Részdiagnózisok'])->toBe(route('admin.main-diagnoses.subdiagnoses', $main->diagnosis));
})->with([
    'no referer' => null,
    'another site' => 'https://example.com/admin/diagnozisok',
    'the login page' => fn () => route('admin.login'),
]);

it('leads from a standalone diagnosis up to the diagnoses of its organization', function () {
    $diagnosis = Diagnosis::factory()->create();

    $response = $this->get(route('admin.diagnoses.show', $diagnosis));

    expect(backLink($response))->toBeNull()
        ->and(pageCrumbs($response))->toBe([
            'Szervezetek' => route('admin.organizations.index'),
            $diagnosis->organization->name => route('admin.organizations.edit', $diagnosis->organization),
            'Diagnózisok' => route('admin.organizations.diagnoses', $diagnosis->organization),
        ]);
});

it('leads back to a list of the breadcrumbs through its crumb, with the filters, and to anywhere else through Vissza', function () {
    $mirror = MirrorSession::factory()->create();
    $list = route('admin.mirrors.index', ['kereses' => 'Anna']);

    $fromList = $this->get(route('admin.mirrors.show', $mirror), ['referer' => $list]);
    $fromInbox = $this->get(route('admin.mirrors.show', $mirror), ['referer' => route('admin.inbox.index')]);

    expect(backLink($fromList))->toBeNull()
        ->and(pageCrumbs($fromList))->toBe(['Értéktükrök' => $list])
        ->and(backLink($fromInbox))->toBe(route('admin.inbox.index'))
        ->and(pageCrumbs($fromInbox))->toBe(['Értéktükrök' => route('admin.mirrors.index')]);
});

it('gives the menu\'s own lists neither breadcrumbs nor Vissza', function () {
    $response = $this->get(route('admin.organizations.index'), ['referer' => route('admin.inbox.index')]);

    expect(backLink($response))->toBeNull()
        ->and(pageCrumbs($response))->toBe([]);
});
