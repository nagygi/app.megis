<?php

use App\Enums\LegalDocumentType;
use App\Models\Diagnosis;
use App\Models\DiagnosisRow;
use App\Models\DiagnosisType;
use App\Models\LegalDocumentVersion;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use App\Models\Value;
use App\Models\ValueCategory;

it('renders every admin page for a superadmin', function (Closure $route) {
    $superadmin = actingAsAdmin(User::factory()->superadmin()->create());
    $organization = Organization::factory()->withHeadcountLimit(10)->create(['contact_admin_id' => $superadmin->id]);
    $team = Team::factory()->for($organization)->create();
    $customer = User::factory()->create();
    $membership = Membership::factory()->for($organization)->for($customer)->create(['position' => 'Ügyvezető']);
    $customer->teams()->attach($team, ['is_leader' => true]);
    LegalDocumentVersion::factory()->ofType(LegalDocumentType::Terms)->create(['created_by' => $superadmin->id]);
    $otherAdmin = User::factory()->admin()->create();
    $category = ValueCategory::factory()->create();
    $value = Value::factory()->for($category, 'category')->create();
    $value->recordVersion();
    $diagnosis = Diagnosis::factory()->forTeam($team)->create();
    DiagnosisRow::factory()->for($diagnosis)->for($value)->scored(4, 5)->create();
    $diagnosisType = DiagnosisType::factory()->create();

    $response = $this->get($route(compact('organization', 'team', 'customer', 'membership', 'otherAdmin', 'category', 'value', 'diagnosis', 'diagnosisType')));

    $response->assertOk();
})->with([
    'organizations' => fn () => route('admin.organizations.index'),
    'new organization' => fn () => route('admin.organizations.create'),
    'organization data' => fn (array $models) => route('admin.organizations.edit', $models['organization']),
    'teams' => fn (array $models) => route('admin.organizations.teams', $models['organization']),
    'members' => fn (array $models) => route('admin.organizations.members', $models['organization']),
    'organization values' => fn (array $models) => route('admin.organizations.values', $models['organization']),
    'invite member' => fn (array $models) => route('admin.organizations.members.create', $models['organization']),
    'edit member' => fn (array $models) => route('admin.organizations.members.edit', [$models['organization'], $models['membership']]),
    'products' => fn () => route('admin.products.index'),
    'new product' => fn () => route('admin.products.create'),
    'edit product' => fn (array $models) => route('admin.products.edit', $models['organization']->product),
    'admins' => fn () => route('admin.admins.index'),
    'invite admin' => fn () => route('admin.admins.create'),
    'edit admin' => fn (array $models) => route('admin.admins.edit', $models['otherAdmin']),
    'clients' => fn () => route('admin.clients.index'),
    'client datasheet' => fn (array $models) => route('admin.clients.show', $models['customer']),
    'inbox' => fn () => route('admin.inbox.index'),
    'diagnoses' => fn () => route('admin.diagnoses.index'),
    'new diagnosis' => fn () => route('admin.diagnoses.create'),
    'diagnosis' => fn (array $models) => route('admin.diagnoses.show', $models['diagnosis']),
    'diagnosis data' => fn (array $models) => route('admin.diagnoses.edit', $models['diagnosis']),
    'diagnosis types' => fn () => route('admin.diagnosis-types.index'),
    'new diagnosis type' => fn () => route('admin.diagnosis-types.create'),
    'edit diagnosis type' => fn (array $models) => route('admin.diagnosis-types.edit', $models['diagnosisType']),
    'values' => fn () => route('admin.values.index'),
    'new value' => fn () => route('admin.values.create'),
    'edit value' => fn (array $models) => route('admin.values.edit', $models['value']),
    'value versions' => fn (array $models) => route('admin.values.versions', $models['value']),
    'value categories' => fn () => route('admin.value-categories.index'),
    'new value category' => fn () => route('admin.value-categories.create'),
    'edit value category' => fn (array $models) => route('admin.value-categories.edit', $models['category']),
    'legal documents' => fn () => route('admin.settings.legal'),
    'profile' => fn () => route('admin.profile'),
    'system' => fn () => route('admin.system.index'),
    'health' => fn () => route('admin.system.health'),
    'ai usage' => fn () => route('admin.system.ai.usage'),
    'ai configuration' => fn () => route('admin.system.ai.configuration'),
    'ai prompts' => fn () => route('admin.system.ai.prompts'),
]);

it('shows the app version at the bottom of the admin menu', function () {
    actingAsAdmin();

    $response = $this->get(route('admin.organizations.index'));

    $response->assertOk()->assertSee('Verzió '.config('megis.version'));
});

it('shows the role badge in the user menu', function () {
    actingAsAdmin(User::factory()->superadmin()->create());

    $response = $this->get(route('admin.organizations.index'));

    $response->assertSee('Szuperadmin');
});

it('hides the Rendszer menu from admins', function () {
    actingAsAdmin();

    $response = $this->get(route('admin.organizations.index'));

    $response->assertDontSee(route('admin.system.health'));
});

it('redirects the admin root to the organization list', function () {
    actingAsAdmin();
    Product::factory()->create();

    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect('/admin/szervezetek');
});
