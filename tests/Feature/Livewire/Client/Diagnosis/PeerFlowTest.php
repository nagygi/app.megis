<?php

use App\Enums\AuditEventType;
use App\Enums\ConsentKind;
use App\Enums\MainDiagnosisStage;
use App\Enums\ParticipantRole;
use App\Livewire\Client\Diagnosis\Experience;
use App\Livewire\Client\Diagnosis\OrganizationValues;
use App\Livewire\Client\Diagnosis\Overview;
use App\Livewire\Client\Diagnosis\OwnValues;
use App\Livewire\Client\Profile;
use App\Livewire\Mirror\Record;
use App\Models\AuditEvent;
use App\Models\Consent;
use App\Models\Diagnosis;
use App\Models\Material;
use App\Models\Team;
use App\Services\Auth\LandingResolver;
use App\Services\MainDiagnoses\ExperienceService;
use App\Services\MainDiagnoses\StageMachine;
use App\Services\Organizations\OrganizationValueSetService;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

it('asks the peer for the consents before anything else (T1)', function () {
    [$setup, $main] = runningDiagnosis();
    $peer = $setup['peers'][0];
    actingAsCustomer($peer);

    Livewire::test(Overview::class, peerParams($setup, $main))
        ->assertSee($setup['leader']->name.' meghívott')
        ->assertSee('Az idézeteim szereppel és dátummal')
        ->call('start')
        ->assertHasErrors(['answersConsent'])
        ->set('answersConsent', true)
        ->call('start')
        ->assertHasNoErrors()
        ->assertRedirect(route('client.diagnosis.show', peerParams($setup, $main)));

    // T2 has its own header, so the page loads again.
    $this->get(route('client.diagnosis.show', peerParams($setup, $main)))
        ->assertSee('Első belépés')
        ->assertSee('Két lépés most, egy, amíg fut.');

    $consent = Consent::query()->where('user_id', $peer->id)->sole();
    expect($consent->kind)->toBe(ConsentKind::Answers)
        ->and($consent->text)->toContain($setup['leader']->name)
        ->and($consent->text_version)->toBe('2026-10-14')
        ->and($main->participantFor($peer, ParticipantRole::Peer)->started_at)->not->toBeNull()
        ->and(AuditEvent::query()->where('subject_user_id', $peer->id)->where('type', AuditEventType::ConsentGiven)->exists())->toBeTrue();
});

it('records the optional quote consent only when the admin turned quotes on', function () {
    [$setup, $main] = runningDiagnosis(showQuotes: false);
    actingAsCustomer($setup['peers'][0]);

    Livewire::test(Overview::class, peerParams($setup, $main))
        ->assertDontSee('Az idézeteim szereppel és dátummal')
        ->set('answersConsent', true)
        ->set('quotesConsent', true)
        ->call('start');

    expect(Consent::query()->where('user_id', $setup['peers'][0]->id)->pluck('kind')->all())->toBe([ConsentKind::Answers]);
});

it('never gives a consent in the user\'s name', function () {
    [$setup, $main] = runningDiagnosis();
    $peer = $setup['peers'][0];
    actingAsAdmin($setup['admin']);
    $this->post(route('admin.impersonate', $peer));

    Livewire::test(Overview::class, peerParams($setup, $main))
        ->set('answersConsent', true)
        ->call('start')
        ->assertForbidden();

    // Opening it in the user's name is not "megnyitotta" either (plan 3.2).
    expect($main->participantFor($peer, ParticipantRole::Peer)->opened_at)->toBeNull();
});

it('lets a peer fill in the own values and the experience, answering the recorded version (T3, T4)', function () {
    [$setup, $main] = runningDiagnosis();
    $peer = $setup['peers'][0];
    $main->participantFor($peer, ParticipantRole::Peer)->update(['started_at' => now()]);
    Consent::create(['user_id' => $peer->id, 'main_diagnosis_id' => $main->id, 'kind' => ConsentKind::Answers, 'text_version' => 'v', 'text' => 't', 'given_at' => now()]);
    actingAsCustomer($peer);

    Livewire::test(OwnValues::class, peerParams($setup, $main))
        ->call('show', 'add')
        ->set('draft.name', 'Türelem')
        ->set('draft.meaning', 'Kivárom.')
        ->call('addDraft')
        ->call('next')
        ->assertRedirect(route('client.diagnosis.megeles', peerParams($setup, $main)));

    // The organization values change after the start; the peer still answers the recorded version.
    $set = app(OrganizationValueSetService::class)->forOrganization($setup['organization']);
    $set->update(['impact' => 'Egészen más']);
    $set->recordVersion();

    $component = Livewire::test(Experience::class, peerParams($setup, $main))->assertSee('Mi marad');
    foreach (app(ExperienceService::class)->rows($main->valueSetVersion->snapshot) as $row) {
        $component->set('answers.'.$row['key'], '5');
    }
    $component->call('submit')->assertRedirect(route('client.diagnosis.show', peerParams($setup, $main)));

    $experience = Diagnosis::query()->where('parent_id', $main->diagnosis_id)->where('user_id', $peer->id)->sole();
    expect($experience->value_set_version_id)->toBe($main->value_set_version_id);

    Livewire::test(Overview::class, peerParams($setup, $main))
        ->assertSee('Amit eddig behoztál')
        ->assertSee('Türelem');
});

it('keeps the leader-only steps from peers and peers out before "Kezdem"', function () {
    [$setup, $main] = runningDiagnosis();
    actingAsCustomer($setup['peers'][0]);

    Livewire::test(OrganizationValues::class, peerParams($setup, $main))->assertNotFound();
    Livewire::test(OwnValues::class, peerParams($setup, $main))->assertRedirect(route('client.diagnosis.show', peerParams($setup, $main)));
});

it('lands a peer on the running diagnosis after login', function () {
    [$setup, $main] = runningDiagnosis();

    expect(app(LandingResolver::class)->urlFor($setup['peers'][0]))->toBe(route('client.diagnosis.show', peerParams($setup, $main)));
});

it('withdraws the answers consent from the profile until publishing and takes the answers out', function () {
    [$setup, $main] = runningDiagnosis();
    $peer = $setup['peers'][0];
    $main->participantFor($peer, ParticipantRole::Peer)->update(['started_at' => now()]);
    $consent = Consent::create(['user_id' => $peer->id, 'main_diagnosis_id' => $main->id, 'kind' => ConsentKind::Answers, 'text_version' => 'v', 'text' => 't', 'given_at' => now()]);
    $version = $main->valueSetVersion;
    $experience = app(ExperienceService::class)->submit($main, $peer, $version, collect(app(ExperienceService::class)->rows($version->snapshot))->mapWithKeys(fn ($row) => [$row['key'] => '3'])->all());
    actingAsCustomer($peer);

    Livewire::test(Profile::class, ['organization' => $setup['organization']])
        ->assertSee('Hozzájárulásaim')
        ->assertSee('A válaszaim névvel csak a mégisnél')
        ->call('revokeConsent', $consent->id)
        ->assertHasNoErrors();

    expect($consent->refresh()->revoked_at)->not->toBeNull()
        ->and($experience->refresh()->excluded_at)->not->toBeNull()
        ->and($main->participantFor($peer, ParticipantRole::Peer)->hasWithdrawn())->toBeTrue()
        ->and(AuditEvent::query()->where('subject_user_id', $peer->id)->where('type', AuditEventType::ConsentRevoked)->exists())->toBeTrue();

    Livewire::test(Overview::class, peerParams($setup, $main))->assertSee('Visszavontad a hozzájárulásodat');
});

it('does not withdraw a consent after publishing', function () {
    [$setup, $main] = runningDiagnosis();
    $peer = $setup['peers'][0];
    $consent = Consent::create(['user_id' => $peer->id, 'main_diagnosis_id' => $main->id, 'kind' => ConsentKind::Quotes, 'text_version' => 'v', 'text' => 't', 'given_at' => now()]);
    app(StageMachine::class)->transition($main, MainDiagnosisStage::Reading);
    app(StageMachine::class)->transition($main->refresh(), MainDiagnosisStage::Published);
    actingAsCustomer($peer);

    Livewire::test(Profile::class, ['organization' => $setup['organization']])
        ->assertDontSee('Visszavonom')
        ->call('revokeConsent', $consent->id);

    expect($consent->refresh()->revoked_at)->toBeNull();
});

it('asks which diagnosis a mirror from the own menu belongs to when there are more', function () {
    Queue::fake();
    [$setup, $main] = runningDiagnosis();
    $leader = $setup['leader'];
    $otherTeam = Team::factory()->for($setup['organization'])->create();
    $otherTeam->users()->attach($leader->id, ['is_leader' => true]);
    $second = startMainDiagnosis(['organization' => $setup['organization'], 'team' => $otherTeam, 'leader' => $leader, 'admin' => $setup['admin']]);
    actingAsCustomer($leader);

    Livewire::test(Record::class, ['organization' => $setup['organization']])
        ->assertSee('Melyik diagnózishoz tartozzon?')
        ->call('checkBeforeUpload')
        ->assertHasErrors(['mainDiagnosisId'])
        ->set('mode', 'text')
        ->set('text', str_repeat('Kimondom, amit gondolok, akkor is, ha kényelmetlen. ', 3))
        ->call('submitText')
        ->assertHasErrors(['mainDiagnosisId'])
        ->set('mainDiagnosisId', $second->diagnosis_id)
        ->call('submitText')
        ->assertHasNoErrors();

    expect(Material::query()->latest('id')->first()->parent_diagnosis_id)->toBe($second->diagnosis_id);
});

it('ties a mirror from the own menu to the only running diagnosis without asking', function () {
    Queue::fake();
    [$setup, $main] = runningDiagnosis();
    actingAsCustomer($setup['leader']);

    Livewire::test(Record::class, ['organization' => $setup['organization']])
        ->assertDontSee('Melyik diagnózishoz tartozzon?')
        ->set('mode', 'text')
        ->set('mainDiagnosisId', 999)
        ->set('text', str_repeat('Kimondom, amit gondolok, akkor is, ha kényelmetlen. ', 3))
        ->call('submitText')
        ->assertHasNoErrors();

    expect(Material::query()->latest('id')->first()->parent_diagnosis_id)->toBe($main->diagnosis_id);
});
