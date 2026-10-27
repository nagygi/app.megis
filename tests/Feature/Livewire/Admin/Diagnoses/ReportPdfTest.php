<?php

use App\Livewire\Admin\Diagnoses\ReportPdf;
use App\Models\Diagnosis;
use App\Models\Organization;
use App\Services\Report\ReportPdfStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Queue::fake();
    Mail::fake();
    Storage::fake('materials');
});

it('stores the uploaded report PDF and replaces the previous one (28 · 28.3)', function () {
    [$setup, $main] = publishedReport();
    actingAsAdmin($setup['admin']);
    $diagnosis = $main->diagnosis;

    Livewire::test(ReportPdf::class, ['diagnosis' => $diagnosis])
        ->set('file', UploadedFile::fake()->create('riport-v1.pdf', 200, 'application/pdf'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Az egyedi PDF feltöltve: a letöltés ezt adja.')
        ->assertSee('riport-v1.pdf · feltöltve');

    $first = $diagnosis->refresh()->report_pdf_path;
    expect($diagnosis->report_pdf_name)->toBe('riport-v1.pdf')
        ->and($diagnosis->report_pdf_uploaded_at)->not->toBeNull();
    Storage::disk('materials')->assertExists($first);

    Livewire::test(ReportPdf::class, ['diagnosis' => $diagnosis])
        ->set('file', UploadedFile::fake()->create('riport-v2.pdf', 200, 'application/pdf'))
        ->call('save');

    expect($diagnosis->refresh()->report_pdf_name)->toBe('riport-v2.pdf');
    Storage::disk('materials')->assertMissing($first)->assertExists($diagnosis->report_pdf_path);
});

it('removes the uploaded PDF with "Törlöm", so the generated one comes back', function () {
    [$setup, $main] = publishedReport();
    actingAsAdmin($setup['admin']);
    $diagnosis = $main->diagnosis;
    app(ReportPdfStore::class)->store($diagnosis, UploadedFile::fake()->create('riport.pdf', 200, 'application/pdf'));
    $path = $diagnosis->report_pdf_path;

    Livewire::test(ReportPdf::class, ['diagnosis' => $diagnosis])
        ->call('remove')
        ->assertSee('Az egyedi PDF törölve: a letöltés újra a generált riportot adja.')
        ->assertSee('Nincs egyedi PDF');

    expect($diagnosis->refresh()->only(['report_pdf_path', 'report_pdf_name', 'report_pdf_uploaded_at']))
        ->toBe(['report_pdf_path' => null, 'report_pdf_name' => null, 'report_pdf_uploaded_at' => null]);
    Storage::disk('materials')->assertMissing($path);
});

it('accepts only a PDF of at most 20 MB', function (UploadedFile $file) {
    [$setup, $main] = publishedReport();
    actingAsAdmin($setup['admin']);

    Livewire::test(ReportPdf::class, ['diagnosis' => $main->diagnosis])
        ->set('file', $file)
        ->call('save')
        ->assertHasErrors('file');

    expect($main->diagnosis->refresh()->hasReportPdf())->toBeFalse();
})->with([
    'word document' => fn () => UploadedFile::fake()->create('riport.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
    'too large' => fn () => UploadedFile::fake()->create('riport.pdf', 20481, 'application/pdf'),
]);

it('shows the card on the Publikálás tab and on a standalone diagnosis', function () {
    [$setup, $main] = publishedReport();
    actingAsAdmin($setup['admin']);
    $organization = Organization::factory()->create();
    $standalone = Diagnosis::factory()->forUser(customerOf($organization), $organization)->create();

    $this->get(route('admin.main-diagnoses.publish', $main->diagnosis))->assertOk()->assertSee('Egyedi riport-PDF');
    $this->get(route('admin.diagnoses.show', $standalone))->assertOk()->assertSee('Egyedi riport-PDF');
});

it('opens the uploaded PDF for the admin, and only for the admin', function () {
    [$setup, $main] = publishedReport();
    $diagnosis = $main->diagnosis;

    actingAsAdmin($setup['admin']);
    $this->get(route('admin.diagnoses.report-pdf', $diagnosis))->assertNotFound();

    app(ReportPdfStore::class)->store($diagnosis, UploadedFile::fake()->createWithContent('riport.pdf', 'egyedi riport'));
    $response = $this->get(route('admin.diagnoses.report-pdf', $diagnosis))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))->toStartWith('inline')
        ->and($response->streamedContent())->toBe('egyedi riport');
});

it('refuses the admin\'s copy of the uploaded PDF to a customer', function () {
    [$setup, $main] = publishedReport();
    app(ReportPdfStore::class)->store($main->diagnosis, UploadedFile::fake()->create('riport.pdf', 200, 'application/pdf'));

    actingAsCustomer($setup['leader']);
    $this->get(route('admin.diagnoses.report-pdf', $main->diagnosis))->assertForbidden();
});

it('deletes the uploaded PDF with its diagnosis', function () {
    $organization = Organization::factory()->create();
    $diagnosis = Diagnosis::factory()->forUser(customerOf($organization), $organization)->create();
    app(ReportPdfStore::class)->store($diagnosis, UploadedFile::fake()->create('riport.pdf', 200, 'application/pdf'));
    $path = $diagnosis->report_pdf_path;

    $diagnosis->delete();

    Storage::disk('materials')->assertMissing($path);
});
