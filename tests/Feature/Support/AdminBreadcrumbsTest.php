<?php

use App\Models\Diagnosis;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\Organization;
use App\Models\Process;
use App\Models\Team;
use App\Models\Value;
use App\Support\AdminBreadcrumbs;
use App\Support\AdminLocation;

/**
 * Where an admin page sits (AdminBreadcrumbs): its crumbs as label => URL.
 *
 * @param  array<string, mixed>  $parameters
 * @param  array<string, mixed>  $query
 */
function locate(string $routeName, array $parameters = [], array $query = []): AdminLocation
{
    return app(AdminBreadcrumbs::class)->resolve($routeName, $parameters, $query);
}

/**
 * @return array<string, string>
 */
function crumbs(AdminLocation $location): array
{
    return collect($location->trail)->pluck('url', 'label')->all();
}

/**
 * The crumbs down to an organization's Diagnózisok tab.
 *
 * @return array<string, string>
 */
function diagnosesCrumbs(Organization $organization): array
{
    return [
        'Szervezetek' => route('admin.organizations.index'),
        $organization->name => route('admin.organizations.edit', $organization),
        'Diagnózisok' => route('admin.organizations.diagnoses', $organization),
    ];
}

beforeEach(fn () => actingAsAdmin());

it('puts the tabs of an organization under the organization, sharing it as their home', function () {
    $organization = Organization::factory()->create();

    $location = locate('admin.organizations.diagnoses', ['organization' => $organization]);

    expect(crumbs($location))->toBe([
        'Szervezetek' => route('admin.organizations.index'),
        $organization->name => route('admin.organizations.edit', $organization),
    ])
        ->and($location->organization->is($organization))->toBeTrue()
        ->and($location->tab)->toBe('admin.organizations.diagnoses')
        ->and($location->home)->toBe(route('admin.organizations.edit', $organization))
        ->and($location->section)->toBe('admin.organizations.index');
});

it('puts the occasions of a process under the organization\'s Folyamatok tab, lighting it', function () {
    $organization = Organization::factory()->create();
    $process = Process::factory()->for($organization)->create();

    $location = locate('admin.organizations.process-occasions', ['organization' => $organization, 'process' => $process]);

    expect(crumbs($location))->toBe([
        'Szervezetek' => route('admin.organizations.index'),
        $organization->name => route('admin.organizations.edit', $organization),
        'Folyamatok' => route('admin.organizations.processes', $organization),
    ])->and($location->tab)->toBe('admin.organizations.processes')
        ->and($location->home)->toBeNull();
});

it('puts a new process under the organization\'s Folyamatok tab, lighting it', function () {
    $organization = Organization::factory()->create();

    $location = locate('admin.organizations.processes.create', ['organization' => $organization]);

    expect(crumbs($location))->toBe([
        'Szervezetek' => route('admin.organizations.index'),
        $organization->name => route('admin.organizations.edit', $organization),
        'Folyamatok' => route('admin.organizations.processes', $organization),
    ])->and($location->tab)->toBe('admin.organizations.processes');
});

it('puts an invitation under the organization\'s Felhasználók tab, lighting it', function () {
    $organization = Organization::factory()->create();

    $location = locate('admin.organizations.members.create', ['organization' => $organization]);

    expect(crumbs($location))->toBe([
        'Szervezetek' => route('admin.organizations.index'),
        $organization->name => route('admin.organizations.edit', $organization),
        'Felhasználók' => route('admin.organizations.members', $organization),
    ])->and($location->organization->is($organization))->toBeTrue()
        ->and($location->tab)->toBe('admin.organizations.members');
});

it('puts a team\'s form under the organization\'s Csapatok tab, lighting it', function (bool $editing) {
    $organization = Organization::factory()->create();
    $parameters = ['organization' => $organization];

    if ($editing) {
        $parameters['team'] = Team::factory()->for($organization)->create();
    }

    $location = locate($editing ? 'admin.organizations.teams.edit' : 'admin.organizations.teams.create', $parameters);

    expect(crumbs($location))->toBe([
        'Szervezetek' => route('admin.organizations.index'),
        $organization->name => route('admin.organizations.edit', $organization),
        'Csapatok' => route('admin.organizations.teams', $organization),
    ])->and($location->tab)->toBe('admin.organizations.teams');
})->with(['new team' => false, 'editing' => true]);

it('puts every tab of a main diagnosis under its organization\'s diagnoses, lighting Szervezetek and the Diagnózisok tab', function (string $routeName) {
    $main = MainDiagnosis::factory()->create();
    $diagnosis = $main->diagnosis;

    $location = locate($routeName, ['diagnosis' => $diagnosis]);

    expect(crumbs($location))->toBe(diagnosesCrumbs($diagnosis->organization))
        ->and($location->organization->is($diagnosis->organization))->toBeTrue()
        ->and($location->tab)->toBe('admin.organizations.diagnoses')
        ->and($location->home)->toBe(route('admin.diagnoses.show', $diagnosis))
        ->and($location->section)->toBe('admin.organizations.index');
})->with([
    'Áttekintés' => 'admin.diagnoses.show',
    'Futás' => 'admin.main-diagnoses.run',
    'Részdiagnózisok' => 'admin.main-diagnoses.subdiagnoses',
    'Képességek' => 'admin.diagnoses.capabilities',
]);

it('puts a subdiagnosis under its main diagnosis\' Részdiagnózisok tab', function () {
    $main = MainDiagnosis::factory()->create();
    $subdiagnosis = Diagnosis::factory()->create(['organization_id' => $main->diagnosis->organization_id, 'parent_id' => $main->diagnosis_id]);

    $location = locate('admin.diagnoses.show', ['diagnosis' => $subdiagnosis]);

    expect(crumbs($location))->toBe([
        ...diagnosesCrumbs($main->diagnosis->organization),
        $main->diagnosis->name => route('admin.diagnoses.show', $main->diagnosis),
        'Részdiagnózisok' => route('admin.main-diagnoses.subdiagnoses', $main->diagnosis),
    ])->and($location->home)->toBeNull();
});

it('puts the other pages of a diagnosis under the diagnosis itself', function (string $routeName) {
    $diagnosis = Diagnosis::factory()->create();

    expect(crumbs(locate($routeName, ['diagnosis' => $diagnosis])))->toBe([
        ...diagnosesCrumbs($diagnosis->organization),
        $diagnosis->name => route('admin.diagnoses.show', $diagnosis),
    ]);
})->with(['admin.diagnoses.edit', 'admin.diagnoses.capabilities', 'admin.diagnoses.preview']);

it('puts A1 under the diagnoses of the organization it was opened for, or under the list without one', function () {
    $organization = Organization::factory()->create();

    $forOrganization = locate('admin.main-diagnoses.create', query: ['szervezet' => (string) $organization->id]);
    $withoutOrganization = locate('admin.main-diagnoses.create');

    expect(crumbs($forOrganization))->toBe(diagnosesCrumbs($organization))
        ->and($forOrganization->organization->is($organization))->toBeTrue()
        ->and($forOrganization->tab)->toBe('admin.organizations.diagnoses')
        ->and(crumbs($withoutOrganization))->toBe(['Diagnózisok listája' => route('admin.diagnoses.index')])
        ->and($withoutOrganization->organization)->toBeNull()
        ->and($withoutOrganization->section)->toBe('admin.diagnoses.index');
});

it('puts a library page under its menu item, its tabs sharing the first one as their home', function () {
    $value = Value::factory()->create();

    $location = locate('admin.values.versions', ['value' => $value]);

    expect(crumbs($location))->toBe(['Értékek' => route('admin.values.index')])
        ->and($location->organization)->toBeNull()
        ->and($location->tab)->toBeNull()
        ->and($location->home)->toBe(route('admin.values.edit', $value))
        ->and($location->section)->toBe('admin.values.index')
        ->and($location->area)->toBe(__('admin.nav.value_library'));
});

it('names the menu group of a page outside an organization, or the item without a group', function () {
    expect(locate('admin.diagnoses.index')->area)->toBe(__('admin.nav.diagnoses'))
        ->and(locate('admin.inbox.index')->area)->toBe(__('admin.nav.inbox'));
});

it('puts nothing above a menu item\'s own list, and nothing at all above a page outside the menu', function () {
    $list = locate('admin.diagnoses.index');
    $profile = locate('admin.profile');

    expect($list->trail)->toBe([])
        ->and($list->section)->toBe('admin.diagnoses.index')
        ->and($profile->trail)->toBe([])
        ->and($profile->section)->toBeNull();
});

it('puts a material under the diagnosis it was uploaded to, under the Folyamatok tab of its organization alone, or under the Beérkező without one', function () {
    $diagnosis = Diagnosis::factory()->create();
    $uploaded = Material::factory()->create(['parent_diagnosis_id' => $diagnosis->id, 'organization_id' => $diagnosis->organization_id]);
    $unclassified = Material::factory()->create(['organization_id' => $diagnosis->organization_id]);
    $loose = Material::factory()->create();

    $ofOrganization = locate('admin.materials.show', ['material' => $unclassified]);

    expect(crumbs(locate('admin.materials.show', ['material' => $uploaded])))->toBe([
        ...diagnosesCrumbs($diagnosis->organization),
        $diagnosis->name => route('admin.diagnoses.show', $diagnosis),
    ])
        ->and(crumbs($ofOrganization))->toBe([
            'Szervezetek' => route('admin.organizations.index'),
            $diagnosis->organization->name => route('admin.organizations.edit', $diagnosis->organization),
            'Folyamatok' => route('admin.organizations.processes', $diagnosis->organization),
        ])
        ->and($ofOrganization->tab)->toBe('admin.organizations.processes')
        ->and(crumbs(locate('admin.materials.show', ['material' => $loose])))->toBe(['Beérkező' => route('admin.inbox.index')]);
});
