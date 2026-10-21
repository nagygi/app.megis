<?php

use App\Enums\LegalDocumentType;
use App\Models\Capability;
use App\Models\Diagnosis;
use App\Models\DiagnosisRow;
use App\Models\DiagnosisType;
use App\Models\LegalDocumentVersion;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Pattern;
use App\Models\Process;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use App\Models\Value;
use App\Models\ValueCategory;

it('renders every admin page for a superadmin with a page heading', function (Closure $route) {
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

    // Every page names itself with one h1 (NFR · akadálymentesség).
    $response->assertOk()->assertSee('<h1', false);
})->with([
    'organizations' => fn () => route('admin.organizations.index'),
    'new organization' => fn () => route('admin.organizations.create'),
    'organization data' => fn (array $models) => route('admin.organizations.edit', $models['organization']),
    'teams' => fn (array $models) => route('admin.organizations.teams', $models['organization']),
    'members' => fn (array $models) => route('admin.organizations.members', $models['organization']),
    'organization values' => fn (array $models) => route('admin.organizations.values', $models['organization']),
    'organization processes' => fn (array $models) => route('admin.organizations.processes', $models['organization']),
    'process occasions' => fn (array $models) => route('admin.organizations.process-occasions', [$models['organization'], Process::factory()->for($models['organization'])->create()]),
    'organization diagnoses' => fn (array $models) => route('admin.organizations.diagnoses', $models['organization']),
    'start diagnosis' => fn (array $models) => route('admin.main-diagnoses.create', ['szervezet' => $models['organization']->id]),
    'main diagnosis' => fn () => route('admin.diagnoses.show', MainDiagnosis::factory()->create()->diagnosis),
    'main diagnosis settings' => fn () => route('admin.main-diagnoses.edit', MainDiagnosis::factory()->create()->diagnosis),
    'subdiagnoses' => fn () => route('admin.main-diagnoses.subdiagnoses', MainDiagnosis::factory()->create()->diagnosis),
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
    'measurements' => fn () => route('admin.measurements.index'),
    'diagnoses' => fn () => route('admin.diagnoses.index'),
    'new diagnosis' => fn () => route('admin.diagnoses.create'),
    'diagnosis' => fn (array $models) => route('admin.diagnoses.show', $models['diagnosis']),
    'diagnosis data' => fn (array $models) => route('admin.diagnoses.edit', $models['diagnosis']),
    'diagnosis capabilities' => fn (array $models) => route('admin.diagnoses.capabilities', $models['diagnosis']),
    'main diagnosis capabilities' => fn () => route('admin.diagnoses.capabilities', MainDiagnosis::factory()->create()->diagnosis),
    'upload' => fn () => route('admin.diagnoses.upload'),
    'material' => fn (array $models) => route('admin.materials.show', Material::factory()->transcribed([['speaker' => 'Beszélő 1', 'text' => 'Szöveg.']])->create(['organization_id' => $models['organization']->id, 'context' => 'admin_upload', 'status' => 'awaiting_speakers'])),
    'support questions' => fn () => route('admin.support-questions.index'),
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
    'capabilities' => fn () => route('admin.capabilities.index'),
    'new capability' => fn () => route('admin.capabilities.create'),
    'edit capability' => fn () => route('admin.capabilities.edit', Capability::factory()->create()),
    'capability patterns' => fn () => route('admin.capabilities.patterns', Capability::factory()->create()),
    'capability versions' => fn () => route('admin.capabilities.versions', Capability::factory()->create()),
    'capability import' => fn () => route('admin.capabilities.import'),
    'capability groups' => fn () => route('admin.capability-groups.index'),
    'patterns' => fn () => route('admin.patterns.index'),
    'pattern' => fn () => route('admin.patterns.show', Pattern::factory()->create()),
    'pattern versions' => fn () => route('admin.patterns.versions', Pattern::factory()->create()),
    'capability rules' => fn () => route('admin.capability-rules.index'),
    'legal documents' => fn () => route('admin.settings.legal'),
    'areas' => fn () => route('admin.settings.areas'),
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
