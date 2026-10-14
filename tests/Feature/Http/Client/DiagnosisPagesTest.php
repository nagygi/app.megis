<?php

use App\Enums\ConsentKind;
use App\Enums\ParticipantRole;
use App\Enums\StepMark;
use App\Models\Consent;
use App\Models\Process;
use App\Services\MainDiagnoses\ExperienceService;
use App\Services\MainDiagnoses\MainDiagnosisService;
use App\Services\Organizations\OrganizationValueSetService;

it('renders every page of the leader with the frame', function (string $route, ?string $see) {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    $process = Process::factory()->for($setup['organization'])->create(['name' => 'Ops heti']);
    actingAsCustomer($setup['leader']);

    $parameters = ['organization' => $setup['organization'], 'diagnosis' => $main->diagnosis];

    $response = $this->get(route($route, $route === 'client.diagnosis.process' ? [...$parameters, 'process' => $process] : $parameters))->assertOk();

    if ($see !== null) {
        $response->assertSee($see);
    }
})->with([
    'V1' => ['client.diagnosis.show', 'Mi vár rád'],
    'V2' => ['client.diagnosis.sajat-ertekek', '1 / 6'],
    'V3' => ['client.diagnosis.szervezet-ertekei', 'A szervezet értékei'],
    'V4' => ['client.diagnosis.markad-alapja', 'Mit mond rólatok a fa?'],
    'V5' => ['client.diagnosis.megeles', 'Hogyan látjátok ma a márkát?'],
    'V6' => ['client.diagnosis.folyamatok', 'Milyen folyamataid vannak?'],
    'V6·b' => ['client.diagnosis.process-calendar', 'Mit olvasunk, és mit soha.'],
    'V6·c' => ['client.diagnosis.process-conversation', 'Négy kérdés, a saját szavaidból.'],
    'V6·d' => ['client.diagnosis.process-list', 'Detektált folyamatok'],
    'V6·e' => ['client.diagnosis.process', 'Ops heti'],
    'V6·f' => ['client.diagnosis.process-summary', 'A folyamataid'],
    'V9' => ['client.diagnosis.ki-lat-mit', 'Egy lap, a diagnózis végéig érvényes.'],
]);

it('renders the peer\'s pages from the invitation to the home', function () {
    $setup = diagnosisTeam(peers: 1);
    $main = startMainDiagnosis($setup);
    confirmOwnValues($setup['leader']);
    $version = app(OrganizationValueSetService::class)->forOrganization($setup['organization'])->latestVersion;
    $answers = collect(app(ExperienceService::class)->rows($version->snapshot))->mapWithKeys(fn ($row) => [$row['key'] => '4'])->all();
    app(ExperienceService::class)->submit($main, $setup['leader'], $version, $answers);
    $main->stepMarks()->create(['user_id' => $setup['leader']->id, 'step' => 'processes', 'mark' => StepMark::Skipped]);
    app(MainDiagnosisService::class)->start($main->refresh(), $setup['leader']);
    $peer = $setup['peers'][0];
    $parameters = ['organization' => $setup['organization'], 'diagnosis' => $main->diagnosis];
    actingAsCustomer($peer);

    $this->get(route('client.diagnosis.show', $parameters))->assertOk()->assertSee('Hogyan látod te?');

    Consent::create(['user_id' => $peer->id, 'main_diagnosis_id' => $main->id, 'kind' => ConsentKind::Answers, 'text_version' => 'v', 'text' => 't', 'given_at' => now()]);
    $main->participantFor($peer, ParticipantRole::Peer)->update(['started_at' => now()]);

    $this->get(route('client.diagnosis.show', $parameters))->assertOk()->assertSee('Két lépés most, egy, amíg fut.');
    $this->get(route('client.diagnosis.sajat-ertekek', $parameters))->assertOk()->assertSee('1 / 2');
    $this->get(route('client.diagnosis.megeles', $parameters))->assertOk()->assertSee('2 / 2');
    $this->get(route('client.diagnosis.ki-lat-mit', $parameters))->assertForbidden();
    $this->get(route('client.home', $setup['organization']))->assertOk()->assertSee($main->subjectName());
});
