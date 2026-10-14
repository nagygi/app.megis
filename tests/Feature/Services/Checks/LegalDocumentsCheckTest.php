<?php

use App\Enums\CheckStatus;
use App\Enums\LegalDocumentType;
use App\Models\LegalDocumentVersion;
use App\Services\Checks\LegalDocumentsCheck;

it('passes when all three documents are uploaded', function () {
    foreach (LegalDocumentType::cases() as $type) {
        LegalDocumentVersion::factory()->ofType($type)->create();
    }

    $result = app(LegalDocumentsCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Ok);
});

it('names the missing documents with a warning outside production', function () {
    LegalDocumentVersion::factory()->ofType(LegalDocumentType::Terms)->create();

    $result = app(LegalDocumentsCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Warning)
        ->and($result->message)->toBe('Nincs feltöltve: Adatkezelési tájékoztató, Cookie tájékoztató');
});

it('reports missing documents as an error in production', function () {
    $this->app['env'] = 'production';

    $result = app(LegalDocumentsCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Error);
});
