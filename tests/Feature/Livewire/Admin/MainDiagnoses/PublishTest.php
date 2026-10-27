<?php

use App\Enums\DiagnosisStatus;
use App\Enums\MainDiagnosisStage;
use App\Livewire\Admin\MainDiagnoses\Publish;
use App\Mail\DiagnosisNoticeMail;
use App\Models\ChapterUpdate;
use App\Models\DiagnosisClaim;
use App\Models\ReportSnapshot;
use App\Models\User;
use App\Services\Report\PublishService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    Queue::fake();
    Mail::fake();
});

it('keeps "Publikálom" grey until the checklist is complete (21 · 21.2)', function () {
    [$setup, $main] = approvedReading();
    DiagnosisClaim::query()->update(['status' => 'draft', 'approved_at' => null]);
    actingAsAdmin($setup['admin']);

    Livewire::test(Publish::class, ['diagnosis' => $main->diagnosis])
        ->assertSee('Minden állítás és szöveg jóváhagyva')
        ->assertSeeHtml('aria-disabled="true"')
        ->call('publish');

    expect($main->refresh()->stage)->toBe(MainDiagnosisStage::Reading);
});

it('links the threshold row to the Futás tab only for whoever may open it (21 · 21.2)', function () {
    [$setup, $main] = approvedReading();
    $main->update(['threshold_overridden_at' => null, 'threshold_overridden_by' => null]);
    $runUrl = route('admin.main-diagnoses.run', $main->diagnosis);

    actingAsAdmin($setup['admin']);
    Livewire::test(Publish::class, ['diagnosis' => $main->diagnosis])->assertSeeHtml('<a href="'.$runUrl.'" class="inline-flex');

    actingAsAdmin(User::factory()->admin()->create());
    Livewire::test(Publish::class, ['diagnosis' => $main->diagnosis])
        ->assertDontSeeHtml('href="'.$runUrl.'"')
        ->assertSee('a Futás fülön a kapcsolattartó ('.$setup['admin']->name.') vagy a szuperadmin írja felül');
});

it('publishes, freezes the report and mails the leader and the peers (21 · 21.3)', function () {
    [$setup, $main] = approvedReading();
    actingAsAdmin($setup['admin']);

    Livewire::test(Publish::class, ['diagnosis' => $main->diagnosis])
        ->call('publish')
        ->assertSee('Publikálva.');

    $main->refresh();
    $snapshot = ReportSnapshot::query()->sole();

    expect($main->stage)->toBe(MainDiagnosisStage::Published)
        ->and($main->diagnosis->status)->toBe(DiagnosisStatus::Published)
        ->and($snapshot->reason)->toBe(ReportSnapshot::PUBLISHED)
        ->and(array_keys($snapshot->data['takeaways']))->toBe([$setup['peers'][0]->id]);

    Mail::assertQueued(DiagnosisNoticeMail::class, fn (DiagnosisNoticeMail $mail) => $mail->hasTo($setup['leader']->email) && str_contains($mail->body, 'Elkészült'));
    Mail::assertQueued(DiagnosisNoticeMail::class, fn (DiagnosisNoticeMail $mail) => $mail->hasTo($setup['peers'][0]->email) && str_contains($mail->body, 'Útravalód'));
    Mail::assertQueued(DiagnosisNoticeMail::class, 2);

    actingAsCustomer($setup['peers'][0]);
    $this->get(route('client.diagnosis.takeaway', [$setup['organization'], $main->diagnosis]))->assertOk()->assertSee('Útravaló · a te értékeid');
    actingAsCustomer($setup['peers'][1]);
    $this->get(route('client.diagnosis.takeaway', [$setup['organization'], $main->diagnosis]))->assertNotFound();
});

it('publishes a correction with the "frissítve" mark on the changed chapter (21 · 21.4)', function () {
    [$setup, $main] = publishedReport();
    DiagnosisClaim::query()->update(['text' => 'Javított mondat a csúszásról.']);
    actingAsAdmin($setup['admin']);

    Livewire::test(Publish::class, ['diagnosis' => $main->diagnosis])
        ->set('correctionNote', 'Elírás')
        ->call('publishCorrection');

    expect(ReportSnapshot::query()->count())->toBe(2)
        ->and(ChapterUpdate::query()->pluck('chapter')->all())->toBe(['values']);

    actingAsCustomer($setup['leader']);
    $this->get(route('client.report.chapter', [$setup['organization'], $main->diagnosis, 'ertekek']))
        ->assertSee('Javított mondat a csúszásról.')
        ->assertSee('frissítve · '.today()->format('m.d.'));
});

it('withdraws with a reason and republishes without e-mails (21 · 21.5)', function () {
    [$setup, $main] = publishedReport();
    Mail::fake();
    actingAsAdmin($setup['admin']);

    Livewire::test(Publish::class, ['diagnosis' => $main->diagnosis])
        ->call('withdraw')
        ->assertHasErrors(['withdrawReason'])
        ->set('withdrawReason', 'Hibás folyamat-pont.')
        ->call('withdraw')
        ->assertHasNoErrors()
        ->assertSeeHtml('Újrapublikálás: e-mail nem megy ki');

    $main->refresh();
    expect($main->stage)->toBe(MainDiagnosisStage::Reading)
        ->and($main->diagnosis->status)->toBe(DiagnosisStatus::Draft)
        ->and($main->diagnosis->transitions()->latest('id')->first()->reason)->toBe('Hibás folyamat-pont.');

    actingAsCustomer($setup['leader']);
    $this->get(route('client.report.show', [$setup['organization'], $main->diagnosis]))->assertForbidden();
    $this->get(route('client.diagnosis.show', [$setup['organization'], $main->diagnosis]))->assertSee('A mégis javítja a diagnózisotokat.');

    app(PublishService::class)->publish($main->refresh(), $setup['admin']);

    expect(ReportSnapshot::query()->latest('sequence')->first()->reason)->toBe(ReportSnapshot::REPUBLISHED);
    Mail::assertNothingQueued();
});

it('previews the leader\'s report and a peer\'s Útravaló from the live data (21 · 21.1)', function () {
    [$setup, $main] = approvedReading();
    actingAsAdmin($setup['admin']);
    $value = $main->diagnosis->rows()->where('kind', 'value')->orderBy('position')->first();

    $this->get(route('admin.main-diagnoses.preview', $main->diagnosis))->assertOk()->assertSee('Mennyire lesz nálatok az értékekből tett?')
        ->assertSee(route('admin.main-diagnoses.preview', [$main->diagnosis, 'ertekek']), false);
    $this->get(route('admin.main-diagnoses.preview', [$main->diagnosis, 'ertekek']))->assertOk()->assertSee('Nálatok szólnak a csúszásról.')->assertDontSee('Magatokra ismertek?');
    $this->get(route('admin.main-diagnoses.preview', [$main->diagnosis, 'ertekek', $value->id]))->assertOk()->assertSee('A ti definíciótok');
    $this->get(route('admin.main-diagnoses.preview', [$main->diagnosis, 'osszefoglalo']))->assertOk();
    $this->get(route('admin.main-diagnoses.preview-takeaway', [$main->diagnosis, $setup['peers'][0]]))->assertOk()->assertSee('Útravaló · a te értékeid');
    $this->get(route('admin.main-diagnoses.preview-takeaway', [$main->diagnosis, $setup['peers'][1]]))->assertNotFound();
});
