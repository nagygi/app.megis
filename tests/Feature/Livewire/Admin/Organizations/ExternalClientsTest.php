<?php

use App\Enums\ExternalClientItemKind;
use App\Livewire\Admin\Organizations\ExternalClientForm;
use App\Livewire\Admin\Organizations\ExternalClients;
use App\Livewire\Admin\Organizations\Form;
use App\Models\ExternalClient;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Process;
use App\Models\User;
use Livewire\Livewire;

it('has no Ügyfelek tab while Ügyfélmérés is switched off', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();

    $this->get(route('admin.organizations.external-clients', $organization))->assertNotFound();
    $this->get(route('admin.organizations.external-clients.create', $organization))->assertNotFound();
    $this->get(route('admin.organizations.edit', $organization))->assertDontSee(route('admin.organizations.external-clients', $organization));
});

it('shows the Ügyfelek tab once Ügyfélmérés is switched on', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();

    Livewire::test(Form::class, ['organization' => $organization])
        ->set('external_clients_enabled', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.organizations.edit', $organization));

    expect($organization->refresh()->usesExternalClients())->toBeTrue();
    $this->get(route('admin.organizations.edit', $organization))->assertSee(route('admin.organizations.external-clients', $organization));
    $this->get(route('admin.organizations.external-clients', $organization))->assertOk();
});

it('creates a client with users who are not members, one question for its values, and its processes with their own questions', function () {
    actingAsAdmin();
    $organization = Organization::factory()->withExternalClients()->withHeadcountLimit(1)->create();
    Membership::factory()->for($organization)->create();
    [$first, $second, $third] = organizationValues($organization)->items()->get()->all();
    $process = Process::factory()->accepted()->for($organization)->create(['name' => 'Ajánlatadás']);
    $existing = User::factory()->create(['email' => 'kati@ugyfel.test']);

    $component = Livewire::test(ExternalClientForm::class, ['organization' => $organization])
        ->assertSet("values.{$first->key}", true)
        ->assertSet('valueQuestion', __('external_clients.default_questions.values'))
        ->assertSet("processes.{$process->id}.selected", false)
        ->set('name', 'Acme Kft.')
        ->set('newLastName', 'Új')
        ->set('newFirstName', 'Ügyfél')
        ->set('newEmail', 'Uj@Ugyfel.test')
        ->call('addUser')
        ->set('newEmail', 'kati@ugyfel.test')
        ->call('addUser')
        ->set("values.{$second->key}", false)
        ->set("values.{$third->key}", false)
        ->set('valueQuestion', 'Mikor éreztél ránk számítani?')
        ->set("processes.{$process->id}.selected", true)
        ->call('save')
        ->assertHasNoErrors();

    $client = $organization->externalClients()->sole();
    $component->assertRedirect(route('admin.organizations.external-clients.edit', [$organization, $client]));

    $newUser = User::query()->where('email', 'uj@ugyfel.test')->sole();
    expect($client->name)->toBe('Acme Kft.')
        ->and($client->value_question)->toBe('Mikor éreztél ránk számítani?')
        ->and($client->users()->pluck('users.id')->sort()->values()->all())->toBe(collect([$newUser->id, $existing->id])->sort()->values()->all())
        ->and($newUser->name)->toBe('Új Ügyfél')
        ->and($newUser->memberships()->exists())->toBeFalse()
        // The users of a client do not take a seat of the organization.
        ->and($organization->refresh()->headcount())->toBe(1);

    $items = $client->items()->get();
    expect($items->map(fn ($item) => [$item->kind, $item->value_key, $item->process_id, $item->question])->all())->toBe([
        [ExternalClientItemKind::Value, $first->key, null, null],
        [ExternalClientItemKind::Process, null, $process->id, __('external_clients.default_questions.process', ['process' => 'Ajánlatadás'])],
    ]);
});

it('keeps an item that stays, and drops one taken out', function () {
    actingAsAdmin();
    $organization = Organization::factory()->withExternalClients()->create();
    [$first, $second] = organizationValues($organization)->items()->get()->all();
    $client = ExternalClient::factory()->for($organization)->create(['value_question' => 'Régi kérdés?']);
    $kept = $client->items()->create(['kind' => ExternalClientItemKind::Value, 'value_key' => $first->key]);
    $client->items()->create(['kind' => ExternalClientItemKind::Value, 'value_key' => $second->key]);

    Livewire::test(ExternalClientForm::class, ['organization' => $organization, 'externalClient' => $client])
        ->assertSet('valueQuestion', 'Régi kérdés?')
        ->set("values.{$second->key}", false)
        ->set('valueQuestion', 'Új kérdés?')
        ->call('save')
        ->assertHasNoErrors();

    expect($client->items()->sole()->id)->toBe($kept->id)
        ->and($client->refresh()->value_question)->toBe('Új kérdés?');
});

it('asks for the values\' question while a value is measured, and for a checked process\' own', function () {
    actingAsAdmin();
    $organization = Organization::factory()->withExternalClients()->create();
    organizationValues($organization);
    $process = Process::factory()->accepted()->for($organization)->create();

    Livewire::test(ExternalClientForm::class, ['organization' => $organization])
        ->set('name', 'Acme Kft.')
        ->set('valueQuestion', '')
        ->set("processes.{$process->id}.selected", true)
        ->set("processes.{$process->id}.question", '')
        ->call('save')
        ->assertHasErrors(['valueQuestion', "processes.{$process->id}.question"]);

    expect($organization->externalClients()->exists())->toBeFalse();
});

it('does not take an admin account or the same e-mail twice as a client user', function () {
    actingAsAdmin();
    $organization = Organization::factory()->withExternalClients()->create();
    $admin = User::factory()->admin()->create();

    Livewire::test(ExternalClientForm::class, ['organization' => $organization])
        ->set('newEmail', $admin->email)
        ->call('addUser')
        ->assertHasErrors(['newEmail' => __('admin.external_clients.admin_email')])
        ->set('newEmail', 'kati@ugyfel.test')
        ->call('addUser')
        ->set('newEmail', 'KATI@ugyfel.test')
        ->call('addUser')
        ->assertHasErrors(['newEmail' => __('admin.external_clients.user_already_added')])
        ->assertCount('users', 1);
});

it('rejects a client name already used in the organization', function () {
    actingAsAdmin();
    $organization = Organization::factory()->withExternalClients()->create();
    ExternalClient::factory()->for($organization)->create(['name' => 'Acme']);

    Livewire::test(ExternalClientForm::class, ['organization' => $organization])
        ->set('name', 'Acme')
        ->call('save')
        ->assertHasErrors(['name' => __('admin.external_clients.name_taken')]);
});

it('does not open a client of another organization', function () {
    actingAsAdmin();
    $organization = Organization::factory()->withExternalClients()->create();
    $foreign = ExternalClient::factory()->create();

    $this->get(route('admin.organizations.external-clients.edit', [$organization, $foreign]))->assertNotFound();
});

it('deactivates, activates and deletes a client, its users keeping their accounts', function () {
    actingAsAdmin();
    $organization = Organization::factory()->withExternalClients()->create();
    $client = ExternalClient::factory()->for($organization)->create();
    $user = User::factory()->create();
    $client->users()->attach($user);

    $component = Livewire::test(ExternalClients::class, ['organization' => $organization])
        ->assertSee($client->name)
        ->call('deactivate', $client->id);
    expect($client->refresh()->isActive())->toBeFalse();

    $component->call('activate', $client->id);
    expect($client->refresh()->isActive())->toBeTrue();

    $component->call('delete', $client->id);
    $this->assertModelMissing($client);
    $this->assertModelExists($user);
});

it('measures any value when asked, and then wants the value question even without a chosen value', function () {
    actingAsAdmin();
    $organization = Organization::factory()->withExternalClients()->create();
    $values = organizationValues($organization)->items()->get();

    $component = Livewire::test(ExternalClientForm::class, ['organization' => $organization])
        ->set('name', 'Acme Kft.')
        ->set('valueQuestion', '')
        ->set('anyValue', true);

    foreach ($values as $value) {
        $component->set("values.{$value->key}", false);
    }

    $component->call('save')->assertHasErrors('valueQuestion');

    $component->set('valueQuestion', 'Milyen velünk dolgozni?')->call('save')->assertHasNoErrors();

    expect($organization->externalClients()->sole())
        ->measuresAnyValue()->toBeTrue()
        ->value_question->toBe('Milyen velünk dolgozni?')
        ->and($organization->externalClients()->sole()->items()->exists())->toBeFalse();
});
