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

it('creates a client with users who are not members, and its values and processes with their own questions', function () {
    actingAsAdmin();
    $organization = Organization::factory()->withExternalClients()->withHeadcountLimit(1)->create();
    Membership::factory()->for($organization)->create();
    [$first, $second, $third] = organizationValues($organization)->items()->get()->all();
    $process = Process::factory()->accepted()->for($organization)->create(['name' => 'Ajánlatadás']);
    $existing = User::factory()->create(['email' => 'kati@ugyfel.test']);

    $component = Livewire::test(ExternalClientForm::class, ['organization' => $organization])
        ->assertSet("values.{$first->key}.selected", true)
        ->assertSet("processes.{$process->id}.selected", false)
        ->set('name', 'Acme Kft.')
        ->set('newLastName', 'Új')
        ->set('newFirstName', 'Ügyfél')
        ->set('newEmail', 'Uj@Ugyfel.test')
        ->call('addUser')
        ->set('newEmail', 'kati@ugyfel.test')
        ->call('addUser')
        ->set("values.{$second->key}.selected", false)
        ->set("values.{$third->key}.selected", false)
        ->set("values.{$first->key}.question", 'Mikor éreztél ránk számítani?')
        ->set("processes.{$process->id}.selected", true)
        ->call('save')
        ->assertHasNoErrors();

    $client = $organization->externalClients()->sole();
    $component->assertRedirect(route('admin.organizations.external-clients.edit', [$organization, $client]));

    $newUser = User::query()->where('email', 'uj@ugyfel.test')->sole();
    expect($client->name)->toBe('Acme Kft.')
        ->and($client->users()->pluck('users.id')->sort()->values()->all())->toBe(collect([$newUser->id, $existing->id])->sort()->values()->all())
        ->and($newUser->name)->toBe('Új Ügyfél')
        ->and($newUser->memberships()->exists())->toBeFalse()
        // The users of a client do not take a seat of the organization.
        ->and($organization->refresh()->headcount())->toBe(1);

    $items = $client->items()->get();
    expect($items->map(fn ($item) => [$item->kind, $item->value_key, $item->process_id, $item->question])->all())->toBe([
        [ExternalClientItemKind::Value, $first->key, null, 'Mikor éreztél ránk számítani?'],
        [ExternalClientItemKind::Process, null, $process->id, __('external_clients.default_questions.process', ['process' => 'Ajánlatadás'])],
    ]);
});

it('keeps an item that stays, and drops one taken out', function () {
    actingAsAdmin();
    $organization = Organization::factory()->withExternalClients()->create();
    [$first, $second] = organizationValues($organization)->items()->get()->all();
    $client = ExternalClient::factory()->for($organization)->create();
    $kept = $client->items()->create(['kind' => ExternalClientItemKind::Value, 'value_key' => $first->key, 'question' => 'Régi kérdés?']);
    $client->items()->create(['kind' => ExternalClientItemKind::Value, 'value_key' => $second->key, 'question' => 'Másik?']);

    Livewire::test(ExternalClientForm::class, ['organization' => $organization, 'externalClient' => $client])
        ->assertSet("values.{$first->key}.question", 'Régi kérdés?')
        ->set("values.{$second->key}.selected", false)
        ->set("values.{$first->key}.question", 'Új kérdés?')
        ->call('save')
        ->assertHasNoErrors();

    expect($client->items()->sole())
        ->id->toBe($kept->id)
        ->question->toBe('Új kérdés?');
});

it('asks for the question of a checked item', function () {
    actingAsAdmin();
    $organization = Organization::factory()->withExternalClients()->create();
    $first = organizationValues($organization)->items()->first();

    Livewire::test(ExternalClientForm::class, ['organization' => $organization])
        ->set('name', 'Acme Kft.')
        ->set("values.{$first->key}.question", '')
        ->call('save')
        ->assertHasErrors(["values.{$first->key}.question"]);

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
