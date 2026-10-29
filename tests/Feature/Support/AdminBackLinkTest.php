<?php

use App\Models\Diagnosis;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\MirrorSession;
use App\Models\User;
use Illuminate\Testing\TestResponse;

/**
 * The Vissza above the title (x-admin.back-link): where it leads and what it says.
 *
 * @return array{href: string, label: string}|null
 */
function backLink(TestResponse $response): ?array
{
    $found = preg_match('/<a href="([^"]*)" class="-ml-1 inline-flex[^"]*">\s*<span\s+class="megis-mask megis-icon-chevron-left[^>]*><\/span>\s*(.*?)\s*<\/a>/s', $response->getContent(), $match);

    return $found ? ['href' => html_entity_decode($match[1]), 'label' => $match[2]] : null;
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
    expect(backLink($response))->toBe(['href' => $list, 'label' => 'Vissza']);
});

it('keeps where a main diagnosis was opened from while switching its tabs', function () {
    $main = MainDiagnosis::factory()->create();
    $client = route('admin.clients.show', User::factory()->create());
    $this->get(route('admin.diagnoses.show', $main->diagnosis), ['referer' => $client]);

    $response = $this->get(route('admin.main-diagnoses.subdiagnoses', $main->diagnosis), ['referer' => route('admin.diagnoses.show', $main->diagnosis)]);

    expect(backLink($response)['href'])->toBe($client);
});

it('keeps where a page was opened from when coming back from a page opened there', function () {
    $main = MainDiagnosis::factory()->create();
    $subdiagnosis = subdiagnosisOf($main);
    $subdiagnoses = route('admin.main-diagnoses.subdiagnoses', $main->diagnosis);
    $this->get($subdiagnoses, ['referer' => route('admin.diagnoses.index')]);
    $toSubdiagnosis = $this->get(route('admin.diagnoses.show', $subdiagnosis), ['referer' => $subdiagnoses]);

    $response = $this->get($subdiagnoses, ['referer' => route('admin.diagnoses.show', $subdiagnosis)]);

    expect(backLink($toSubdiagnosis)['href'])->toBe($subdiagnoses)
        ->and(backLink($response)['href'])->toBe(route('admin.diagnoses.index'));
});

it('hands where a create form was opened from on to the page it redirects to', function () {
    $material = Material::factory()->transcribed([['speaker' => 'Beszélő 1', 'text' => 'Szöveg.']])->create(['context' => 'admin_upload', 'status' => 'awaiting_speakers']);
    $list = route('admin.diagnoses.index', ['kereses' => 'Alma']);
    $this->get(route('admin.diagnoses.upload'), ['referer' => $list]);

    $response = $this->get(route('admin.materials.show', $material), ['referer' => route('admin.diagnoses.upload')]);

    expect(backLink($response)['href'])->toBe($list);
});

it('falls back to the subdiagnoses of the main diagnosis when not opened from an admin page', function (?string $referer) {
    $main = MainDiagnosis::factory()->create();
    $subdiagnosis = subdiagnosisOf($main);
    $this->get(route('admin.diagnoses.show', $subdiagnosis), ['referer' => route('admin.diagnoses.index')]);

    $response = $this->get(route('admin.diagnoses.show', $subdiagnosis), array_filter(['referer' => $referer]));

    expect(backLink($response)['href'])->toBe(route('admin.main-diagnoses.subdiagnoses', $main->diagnosis));
})->with([
    'no referer' => null,
    'another site' => 'https://example.com/admin/diagnozisok',
    'the login page' => fn () => route('admin.login'),
]);

it('falls back to the diagnoses of the organization for a standalone diagnosis', function () {
    $diagnosis = Diagnosis::factory()->create();

    $response = $this->get(route('admin.diagnoses.show', $diagnosis));

    expect(backLink($response)['href'])->toBe(route('admin.organizations.diagnoses', $diagnosis->organization));
});

it('names the list only when Vissza leads to it', function (Closure $referer, string $label) {
    $mirror = MirrorSession::factory()->create();

    $response = $this->get(route('admin.mirrors.show', $mirror), ['referer' => $referer()]);

    expect(backLink($response)['label'])->toBe($label);
})->with([
    'from the list' => [fn () => route('admin.mirrors.index'), 'Értéktükrök'],
    'from elsewhere' => [fn () => route('admin.inbox.index'), 'Vissza'],
]);
