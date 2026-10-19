<?php

use App\Enums\LegalDocumentType;
use App\Livewire\Admin\Settings\LegalDocuments;
use App\Models\LegalDocumentVersion;
use App\Services\Legal\LegalDocuments as LegalDocumentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('uploads a new version that comes into force today', function () {
    Storage::fake('materials');
    $admin = actingAsAdmin();

    Livewire::test(LegalDocuments::class)
        ->call('openUpload', LegalDocumentType::Terms->value)
        ->set('file', UploadedFile::fake()->create('aszf-2026.pdf', 200, 'application/pdf'))
        ->set('requiresReacceptance', true)
        ->call('storeVersion')
        ->assertHasNoErrors();

    $version = LegalDocumentVersion::query()->sole();
    expect($version)
        ->type->toBe(LegalDocumentType::Terms)
        ->original_name->toBe('aszf-2026.pdf')
        ->requires_reacceptance->toBeTrue()
        ->created_by->toBe($admin->id)
        ->and($version->effective_from->isToday())->toBeTrue();
    Storage::disk('materials')->assertExists($version->file_path);
});

it('keeps the old version in force until a future date', function () {
    Storage::fake('materials');
    actingAsAdmin();
    $current = LegalDocumentVersion::factory()->ofType(LegalDocumentType::PrivacyPolicy)->create(['effective_from' => today()->subYear()]);

    Livewire::test(LegalDocuments::class)
        ->call('openUpload', LegalDocumentType::PrivacyPolicy->value)
        ->set('file', UploadedFile::fake()->create('adatkezeles.pdf', 100, 'application/pdf'))
        ->set('effectiveFrom', today()->addWeek()->toDateString())
        ->call('storeVersion')
        ->assertHasNoErrors();

    expect(app(LegalDocumentService::class)->current(LegalDocumentType::PrivacyPolicy)->is($current))->toBeTrue();
});

it('accepts only PDF files up to 10 MB', function (UploadedFile $file) {
    Storage::fake('materials');
    actingAsAdmin();

    Livewire::test(LegalDocuments::class)
        ->call('openUpload', LegalDocumentType::Terms->value)
        ->set('file', $file)
        ->call('storeVersion')
        ->assertHasErrors('file');

    expect(LegalDocumentVersion::query()->count())->toBe(0);
})->with([
    'word document' => fn () => UploadedFile::fake()->create('aszf.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
    'too large' => fn () => UploadedFile::fake()->create('aszf.pdf', 10241, 'application/pdf'),
]);

it('never asks for the cookie policy to be accepted again', function () {
    Storage::fake('materials');
    actingAsAdmin();

    Livewire::test(LegalDocuments::class)
        ->call('openUpload', LegalDocumentType::CookiePolicy->value)
        ->set('file', UploadedFile::fake()->create('cookie.pdf', 100, 'application/pdf'))
        ->set('requiresReacceptance', true)
        ->call('storeVersion');

    expect(LegalDocumentVersion::query()->sole()->requires_reacceptance)->toBeFalse();
});

it('warns when a document has not been uploaded', function () {
    actingAsAdmin();

    $component = Livewire::test(LegalDocuments::class);

    $component->assertSee('Még nincs feltöltve.');
});
