<?php

use App\Enums\InboxItemType;
use App\Livewire\Admin\Inbox\Index as Inbox;
use App\Livewire\Admin\MainDiagnoses\Reading;
use App\Livewire\Client\Report\Feedback;
use App\Mail\FeedbackReplyMail;
use App\Models\ChapterUpdate;
use App\Models\DiagnosisClaim;
use App\Models\InboxItem;
use App\Models\ReportFeedback;
use App\Services\Report\PublishService;
use App\Services\Report\ReportPdfStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\LaravelPdf\Facades\Pdf;

beforeEach(function () {
    Queue::fake();
    Mail::fake();
});

it('takes "Igen" at once and keeps it out of the Beérkező (28 · 28.1)', function () {
    [$setup, $main] = publishedReport();
    actingAsCustomer($setup['leader']);

    Livewire::test(Feedback::class, ['main' => $main, 'chapter' => 'values'])
        ->call('choose', 'yes')
        ->assertSee('Köszönjük');

    expect(ReportFeedback::query()->sole()->answer)->toBe('yes')
        ->and(InboxItem::query()->where('type', InboxItemType::Feedback)->exists())->toBeFalse();
});

it('asks "Miben nem?" and sends Részben to the Beérkező', function () {
    [$setup, $main] = publishedReport();
    actingAsCustomer($setup['leader']);

    Livewire::test(Feedback::class, ['main' => $main, 'chapter' => 'values'])
        ->call('choose', 'partial')
        ->assertSee('Miben nem?')
        ->call('send')
        ->assertHasErrors(['text'])
        ->set('text', 'A nyíltság nálunk inkább a vezetői körben hiányzik.')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSee('Elküldve a mégisnek');

    $item = InboxItem::query()->where('type', InboxItemType::Feedback)->sole();
    expect($item->summary)->toContain('Részben')->toContain('A nyíltság');
});

it('keeps the feedback to those who see the report', function () {
    [$setup, $main] = publishedReport();
    actingAsCustomer($setup['peers'][0]);

    Livewire::test(Feedback::class, ['main' => $main, 'chapter' => 'values'])
        ->call('choose', 'yes')
        ->assertForbidden();
});

it('builds the feedback in and marks the chapter on the correction (22 · 22.2, 21 · 21.4)', function () {
    [$setup, $main] = publishedReport();
    actingAsCustomer($setup['leader']);
    Livewire::test(Feedback::class, ['main' => $main, 'chapter' => 'values'])
        ->call('choose', 'no')
        ->set('text', 'Ez nem így van nálunk.')
        ->call('send');
    $feedback = ReportFeedback::query()->sole();
    $item = InboxItem::query()->where('type', InboxItemType::Feedback)->sole();

    actingAsAdmin($setup['admin']);
    Livewire::test(Inbox::class)->assertSeeHtml(route('admin.main-diagnoses.reading', [$main->diagnosis, 'visszajelzes' => $feedback->id]));

    Livewire::withQueryParams(['visszajelzes' => $feedback->id])->test(Reading::class, ['diagnosis' => $main->diagnosis])
        ->assertSee('Ez nem így van nálunk.')
        ->call('incorporateFeedback');

    expect($feedback->refresh()->incorporated_at)->not->toBeNull()
        ->and($item->refresh()->closed_at)->not->toBeNull();

    DiagnosisClaim::query()->update(['text' => 'A visszajelzés után átírt mondat.']);
    app(PublishService::class)->publishCorrection($main, $setup['admin']);

    expect(ChapterUpdate::query()->sole()->reason)->toBe(ChapterUpdate::FEEDBACK);

    actingAsCustomer($setup['leader']);
    $this->get(route('client.report.chapter', [$setup['organization'], $main->diagnosis, 'ertekek']))->assertSee('a ti visszajelzésetek alapján');
});

it('answers a feedback by e-mail by name (22, question 1)', function () {
    [$setup, $main] = publishedReport();
    actingAsCustomer($setup['leader']);
    Livewire::test(Feedback::class, ['main' => $main, 'chapter' => 'brand'])
        ->call('choose', 'partial')
        ->set('text', 'A korona nálunk más.')
        ->call('send');
    $item = InboxItem::query()->where('type', InboxItemType::Feedback)->sole();
    actingAsAdmin($setup['admin']);

    Livewire::test(Inbox::class)
        ->set('replyingTo', $item->id)
        ->call('reply', $item->id)
        ->assertHasErrors(["replies.{$item->id}"])
        ->set("replies.{$item->id}", 'Köszönjük, átnézzük a korona olvasatát.')
        ->call('reply', $item->id)
        ->assertHasNoErrors();

    Mail::assertQueued(FeedbackReplyMail::class, fn (FeedbackReplyMail $mail) => $mail->hasTo($setup['leader']->email) && $mail->reply === 'Köszönjük, átnézzük a korona olvasatát.');
    expect($item->refresh()->closed_at)->not->toBeNull()
        ->and(ReportFeedback::query()->sole()->answered_at)->not->toBeNull();
});

it('keeps names, quotes and answers as text in the e-mail', function () {
    [$setup, $main] = publishedReport();
    $setup['leader']->forceFill(['first_name' => '[Kattints](https://example.com/nev)'])->save();
    actingAsCustomer($setup['leader']);
    Livewire::test(Feedback::class, ['main' => $main, 'chapter' => 'brand'])
        ->call('choose', 'partial')
        ->set('text', 'Nézd meg [itt](https://example.com/idezet).')
        ->call('send');

    $mail = new FeedbackReplyMail(ReportFeedback::query()->sole(), 'Válasz [itt](https://example.com/valasz).');

    $mail->assertDontSeeInHtml('https://example.com/nev"', false)
        ->assertDontSeeInHtml('https://example.com/idezet"', false)
        ->assertDontSeeInHtml('https://example.com/valasz"', false)
        ->assertSeeInHtml('Kattints', false);
});

it('shows the Melléklet with what the mégis wrote over (28 · 28.2)', function () {
    [$setup, $main] = publishedReport();
    actingAsCustomer($setup['leader']);

    $this->get(route('client.report.appendix', [$setup['organization'], $main->diagnosis]))
        ->assertOk()
        ->assertSee('Honnan tudjuk?')
        ->assertSee('mégis · publikálva')
        ->assertSee('Szóltam a csúszásról')
        ->assertSee('Amit a mégis átírt');
});

it('downloads the report and the Útravaló as PDF from the snapshot (28 · 28.3)', function () {
    Pdf::fake();
    [$setup, $main] = publishedReport();

    actingAsCustomer($setup['leader']);
    $this->get(route('client.report.pdf', [$setup['organization'], $main->diagnosis]))->assertOk();
    Pdf::assertRespondedWithPdf(fn ($pdf) => str_starts_with($pdf->downloadName, 'megis-diagnozis-')
        && str_ends_with($pdf->downloadName, today()->format('Y-m-d').'.pdf')
        && $pdf->viewName === 'pdf.report');

    // The fake keeps one builder; a fresh one per request as in production.
    Pdf::fake();
    actingAsCustomer($setup['peers'][0]);
    $this->get(route('client.report.pdf', [$setup['organization'], $main->diagnosis]))->assertForbidden();
    $this->get(route('client.diagnosis.takeaway.pdf', [$setup['organization'], $main->diagnosis]))->assertOk();
    Pdf::assertRespondedWithPdf(fn ($pdf) => $pdf->downloadName === 'megis-utravalo-'.today()->format('Y-m-d').'.pdf' && $pdf->viewName === 'pdf.takeaway');
});

it('gives the leader the PDF the admin uploaded instead of the generated one (28 · 28.3)', function () {
    Storage::fake('materials');
    [$setup, $main] = publishedReport();
    $parameters = [$setup['organization'], $main->diagnosis];
    app(ReportPdfStore::class)->store($main->diagnosis, UploadedFile::fake()->createWithContent('sajat.pdf', 'egyedi riport'));

    actingAsCustomer($setup['leader']);
    $response = $this->get(route('client.report.pdf', $parameters))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($response->streamedContent())->toBe('egyedi riport')
        ->and($response->headers->get('Content-Disposition'))->toStartWith('attachment; filename=megis-diagnozis-')
        ->toEndWith(today()->format('Y-m-d').'.pdf');

    actingAsCustomer($setup['peers'][0]);
    $this->get(route('client.report.pdf', $parameters))->assertForbidden();
});

it('shows the PDF buttons only when MEGIS_PDF_DOWNLOADS is on (28 · 28.3)', function (bool $enabled) {
    config(['megis.pdf_downloads' => $enabled]);
    [$setup, $main] = publishedReport();
    $parameters = [$setup['organization'], $main->diagnosis];
    $assertLink = fn ($response, string $href) => $enabled ? $response->assertSeeHtml($href) : $response->assertDontSeeHtml($href);

    actingAsCustomer($setup['leader']);
    foreach (['client.diagnosis.show', 'client.report.closing', 'client.report.appendix'] as $page) {
        $assertLink($this->get(route($page, $parameters))->assertOk(), route('client.report.pdf', $parameters));
    }

    actingAsCustomer($setup['peers'][0]);
    $assertLink($this->get(route('client.diagnosis.takeaway', $parameters))->assertOk(), route('client.diagnosis.takeaway.pdf', $parameters));
})->with(['off' => false, 'on' => true]);

it('shows the leader\'s home after publishing (26 · 26.3)', function () {
    [$setup, $main] = publishedReport();
    actingAsCustomer($setup['leader']);

    $this->get(route('client.diagnosis.show', [$setup['organization'], $main->diagnosis]))
        ->assertOk()
        ->assertSee('publikálva')
        ->assertSee('Belső működés · értékek · folyamatok · területek')
        ->assertSee('Értékmérés · a diagnózis után kapcsolható be');

    actingAsCustomer($setup['peers'][0]);
    $this->get(route('client.diagnosis.show', [$setup['organization'], $main->diagnosis]))
        ->assertOk()
        ->assertSeeHtml(route('client.diagnosis.takeaway', [$setup['organization'], $main->diagnosis]));
});
