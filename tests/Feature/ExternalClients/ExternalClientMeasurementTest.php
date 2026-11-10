<?php

use App\Enums\ConsentKind;
use App\Enums\ExternalClientItemKind;
use App\Enums\LegalDocumentType;
use App\Enums\MainDiagnosisStage;
use App\Enums\MaterialContext;
use App\Enums\StepMark;
use App\Enums\TokenPurpose;
use App\Jobs\AnalyzeMaterial;
use App\Livewire\Admin\MainDiagnoses\Form as MainDiagnosisForm;
use App\Livewire\ExternalClient\Measurement;
use App\Mail\ExternalClientInvitationMail;
use App\Models\Consent;
use App\Models\ExternalClient;
use App\Models\ExternalClientAnswer;
use App\Models\LegalAcceptance;
use App\Models\LegalDocumentVersion;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\Process;
use App\Models\User;
use App\Services\Accounts\AccountDeletionService;
use App\Services\Auth\LandingResolver;
use App\Services\Auth\TokenService;
use App\Services\Diagnoses\DiagnosisCalculator;
use App\Services\ExternalClients\ExternalClientMeasurementService;
use App\Services\ExternalClients\ExternalClientService;
use App\Services\MainDiagnoses\ExperienceService;
use App\Services\MainDiagnoses\MainDiagnosisService;
use App\Services\MainDiagnoses\StageMachine;
use App\Services\Organizations\OrganizationValueSetService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

/**
 * A team diagnosis with one client (a user, a value and a process asked),
 * started by the leader: it runs, and the client's user got the link.
 *
 * @return array{0: MainDiagnosis, 1: ExternalClient, 2: User}
 */
function diagnosisWithClient(bool $start = true): array
{
    $setup = diagnosisTeam(peers: 0);
    $organization = $setup['organization'];
    $organization->update(['external_clients_enabled' => true]);
    $process = Process::factory()->accepted()->for($organization)->create(['name' => 'Ajánlatadás']);
    $client = ExternalClient::factory()->for($organization)->create(['name' => 'Acme']);
    $user = User::factory()->invited()->create(['email' => 'kati@acme.test']);
    $client->users()->attach($user);
    $client->items()->create(['kind' => ExternalClientItemKind::Value, 'value_key' => $organization->valueSet->items()->first()->key, 'question' => 'Mikor számíthattál ránk?', 'position' => 0]);
    $client->items()->create(['kind' => ExternalClientItemKind::Process, 'process_id' => $process->id, 'question' => 'Milyen volt az ajánlatadás?', 'position' => 1]);

    $main = startMainDiagnosis($setup);
    app(ExternalClientMeasurementService::class)->syncClients($main, [$client->id]);

    if ($start) {
        confirmOwnValues($setup['leader']);
        $version = app(OrganizationValueSetService::class)->forOrganization($organization)->latestVersion;
        $rows = app(ExperienceService::class)->rows($version->snapshot);
        app(ExperienceService::class)->submit($main, $setup['leader'], $version, collect($rows)->mapWithKeys(fn ($row) => [$row['key'] => '4'])->all());
        $main->stepMarks()->create(['user_id' => $setup['leader']->id, 'step' => 'processes', 'mark' => StepMark::Skipped]);
        $main->stepMarks()->create(['user_id' => $setup['leader']->id, 'step' => 'capabilities', 'mark' => StepMark::Skipped]);
        app(MainDiagnosisService::class)->start($main->refresh(), $setup['leader']);
    }

    return [$main->refresh(), $client, $user];
}

/**
 * The client user, signed in on the customer side with consent given.
 */
function answeringClient(MainDiagnosis $main, User $user): User
{
    $user->forceFill(['activated_at' => now()])->save();
    actingAsCustomer($user);
    app(ExternalClientMeasurementService::class)->giveConsent($main, $user);

    return $user;
}

it('invites the users of the diagnosis\' clients as it starts running, once', function () {
    [$main, $client, $user] = diagnosisWithClient();

    Mail::assertQueued(ExternalClientInvitationMail::class, fn ($mail) => $mail->hasTo('kati@acme.test') && $mail->main->is($main));
    expect($main->externalClients()->sole()->pivot->invited_at)->not->toBeNull();

    app(ExternalClientMeasurementService::class)->inviteClients($main);
    Mail::assertQueued(ExternalClientInvitationMail::class, 1);
});

it('sends no invitation before the diagnosis runs', function () {
    diagnosisWithClient(start: false);

    Mail::assertNotQueued(ExternalClientInvitationMail::class);
});

it('invites at once a client added to a running diagnosis, and a user added to its client', function () {
    [$main, $client] = diagnosisWithClient();
    $other = ExternalClient::factory()->for($client->organization)->create();
    $other->users()->attach(User::factory()->create(['email' => 'bela@other.test']));

    app(ExternalClientMeasurementService::class)->syncClients($main, [$client->id, $other->id]);
    app(ExternalClientService::class)->save($client->organization, $client, ['name' => 'Acme', 'description' => null, 'active' => true], [
        ['email' => 'kati@acme.test'],
        ['email' => 'uj@acme.test', 'first_name' => 'Új', 'last_name' => 'Ügyfél'],
    ], [['kind' => ExternalClientItemKind::Process, 'process_id' => $client->items()->firstWhere('kind', ExternalClientItemKind::Process)->process_id, 'question' => 'Milyen volt?']]);

    Mail::assertQueued(ExternalClientInvitationMail::class, fn ($mail) => $mail->hasTo('bela@other.test'));
    Mail::assertQueued(ExternalClientInvitationMail::class, fn ($mail) => $mail->hasTo('uj@acme.test'));
});

it('asks the active clients by default on A1 and saves the chosen ones', function () {
    [$main, $client] = diagnosisWithClient(start: false);
    $organization = $client->organization;
    ExternalClient::factory()->for($organization)->inactive()->create();
    $second = ExternalClient::factory()->for($organization)->create();
    actingAsAdmin();

    Livewire::test(MainDiagnosisForm::class)
        ->set('organization_id', $organization->id)
        ->assertSet('externalClientIds', [$client->id, $second->id]);

    Livewire::test(MainDiagnosisForm::class, ['diagnosis' => $main->diagnosis])
        ->assertSet('externalClientIds', [$client->id])
        ->set('externalClientIds', [(string) $second->id])
        ->call('saveDraft')
        ->assertHasNoErrors();

    expect($main->externalClients()->pluck('external_clients.id')->all())->toBe([$second->id]);
});

it('opens with the personal link once: activates, accepts the legal documents, logs in and opens the questions', function () {
    [$main, $client, $user] = diagnosisWithClient();
    $terms = LegalDocumentVersion::factory()->ofType(LegalDocumentType::Terms)->create();
    $token = app(TokenService::class)->issue($user, TokenPurpose::ExternalClientInvitation, $client->organization)->plainToken;

    $this->get(route('external.invitation.show', $token))->assertOk()->assertSee($client->organization->name)->assertSee('name="accept"', false);
    $this->post(route('external.invitation.store', $token))->assertSessionHasErrors('accept');

    $this->post(route('external.invitation.store', $token), ['accept' => '1'])
        ->assertRedirect(route('external.measurement', $main->diagnosis));

    $this->assertAuthenticatedAs($user, 'web');
    expect($user->refresh()->isActivated())->toBeTrue()
        ->and(LegalAcceptance::query()->where('user_id', $user->id)->where('legal_document_version_id', $terms->id)->exists())->toBeTrue()
        ->and($user->memberships()->exists())->toBeFalse();

    $this->get(route('external.invitation.show', $token))->assertOk()->assertSee(__('external_clients.invitation.expired'));
});

it('lands a client user without an organization on the open measurement, then on the paused page when it closed', function () {
    [$main, , $user] = diagnosisWithClient();
    $user->forceFill(['activated_at' => now()])->save();

    expect(app(LandingResolver::class)->urlFor($user))->toBe(route('external.measurement', $main->diagnosis));

    app(StageMachine::class)->transition($main, MainDiagnosisStage::Reading);

    expect(app(LandingResolver::class)->urlFor($user))->toBe(route('client.paused'));
});

it('does not open the measurement for a user of no client of the diagnosis', function () {
    [$main] = diagnosisWithClient();
    actingAsCustomer(customerOf($main->diagnosis->organization));

    $this->get(route('external.measurement', $main->diagnosis))->assertForbidden();
});

it('asks for the consent first, then saves the answers as drafts', function () {
    [$main, $client, $user] = diagnosisWithClient();
    $user->forceFill(['activated_at' => now()])->save();
    actingAsCustomer($user);
    [$valueItem, $processItem] = $client->items()->get()->all();

    $this->get(route('external.measurement', $main->diagnosis))->assertOk()->assertSee($main->diagnosis->organization->name)->assertSee(__('external_clients.measurement.start'));

    Livewire::test(Measurement::class, ['diagnosis' => $main->diagnosis])
        ->assertSet('screen', 'intro')
        ->call('start')
        ->assertHasErrors('consent')
        ->set('consent', true)
        ->call('start')
        ->assertSet('screen', 'question')
        ->assertSee('Mikor számíthattál ránk?')
        ->set("answers.{$valueItem->id}", 'Amikor határidő előtt szóltak.')
        ->call('next')
        ->assertSee('Milyen volt az ajánlatadás?');

    expect(Consent::query()->where('user_id', $user->id)->where('main_diagnosis_id', $main->id)->sole()->kind)->toBe(ConsentKind::Answers)
        ->and(ExternalClientAnswer::query()->where('external_client_item_id', $valueItem->id)->sole())
        ->answer->toBe('Amikor határidő előtt szóltak.')
        ->submitted_at->toBeNull()
        ->question->toBe('Mikor számíthattál ránk?');
});

it('sends every answer as a material of its own, outside the team\'s materials', function () {
    Queue::fake();
    [$main, $client, $user] = diagnosisWithClient();
    answeringClient($main, $user);
    [$valueItem, $processItem] = $client->items()->get()->all();

    Livewire::test(Measurement::class, ['diagnosis' => $main->diagnosis])
        ->set("answers.{$valueItem->id}", 'Mindig számíthattunk rájuk.')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('screen', 'sent');

    $answer = ExternalClientAnswer::query()->where('external_client_item_id', $valueItem->id)->sole();
    expect($answer->submitted_at)->not->toBeNull()
        ->and($answer->material)
        ->context->toBe(MaterialContext::ExternalClient)
        ->parent_diagnosis_id->toBeNull()
        ->user_id->toBe($user->id)
        ->organization_id->toBe($client->organization_id)
        ->text->toContain('Mikor számíthattál ránk?')
        ->text->toContain('Mindig számíthattunk rájuk.')
        ->and(ExternalClientAnswer::query()->where('external_client_item_id', $processItem->id)->first()?->material_id)->toBeNull()
        // The team's numbers do not read it (35 · 35.7).
        ->and(app(DiagnosisCalculator::class)->materials($main)->pluck('id'))->not->toContain($answer->material_id);
    Queue::assertPushed(AnalyzeMaterial::class, fn ($job) => $job->material->is($answer->material));
});

it('replaces the material of a changed answer and takes back an emptied one', function () {
    Queue::fake();
    [$main, $client, $user] = diagnosisWithClient();
    answeringClient($main, $user);
    [$valueItem, $processItem] = $client->items()->get()->all();

    $component = Livewire::test(Measurement::class, ['diagnosis' => $main->diagnosis])
        ->set("answers.{$valueItem->id}", 'Első válasz.')
        ->set("answers.{$processItem->id}", 'Gyors volt.')
        ->call('submit');
    $first = ExternalClientAnswer::query()->where('external_client_item_id', $valueItem->id)->sole()->material_id;
    $processMaterial = ExternalClientAnswer::query()->where('external_client_item_id', $processItem->id)->sole()->material_id;

    $component->call('summary')
        ->set("answers.{$valueItem->id}", 'Átírt válasz.')
        ->set("answers.{$processItem->id}", '')
        ->call('submit')
        ->assertHasNoErrors();

    $answer = ExternalClientAnswer::query()->where('external_client_item_id', $valueItem->id)->sole();
    expect($answer->material_id)->not->toBe($first)
        ->and($answer->material->text)->toContain('Átírt válasz.')
        ->and(Material::query()->whereKey([$first, $processMaterial])->exists())->toBeFalse()
        ->and(ExternalClientAnswer::query()->where('external_client_item_id', $processItem->id)->sole()->submitted_at)->toBeNull();
});

it('needs at least one answer to send', function () {
    [$main, , $user] = diagnosisWithClient();
    answeringClient($main, $user);

    Livewire::test(Measurement::class, ['diagnosis' => $main->diagnosis])
        ->call('submit')
        ->assertHasErrors(['answers' => __('external_clients.measurement.nothing_to_submit')]);
});

it('closes the measurement once the reading starts', function () {
    [$main, $client, $user] = diagnosisWithClient();
    answeringClient($main, $user);
    app(StageMachine::class)->transition($main, MainDiagnosisStage::Reading);
    $item = $client->items()->first();

    Livewire::test(Measurement::class, ['diagnosis' => $main->diagnosis])
        ->assertSee(__('external_clients.measurement.closed'))
        ->set("answers.{$item->id}", 'Késő.')
        ->call('submit')
        ->assertForbidden();

    expect(ExternalClientAnswer::query()->exists())->toBeFalse();
});

it('takes a client user\'s answers and client links when the account is deleted', function () {
    Queue::fake();
    [$main, $client, $user] = diagnosisWithClient();
    answeringClient($main, $user);
    $item = $client->items()->first();
    Livewire::test(Measurement::class, ['diagnosis' => $main->diagnosis])->set("answers.{$item->id}", 'Válasz.')->call('submit');

    app(AccountDeletionService::class)->purge($user);

    expect(ExternalClientAnswer::query()->where('user_id', $user->id)->exists())->toBeFalse()
        ->and(Material::query()->where('user_id', $user->id)->exists())->toBeFalse()
        ->and($client->users()->exists())->toBeFalse();
});
