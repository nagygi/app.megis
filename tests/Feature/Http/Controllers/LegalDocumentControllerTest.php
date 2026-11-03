<?php

use App\Enums\LegalDocumentType;
use App\Models\LegalDocumentVersion;
use App\Models\Organization;
use Illuminate\Support\Facades\Storage;

it('serves the version in force at the permanent address', function () {
    Storage::fake('materials');
    Storage::disk('materials')->put('legal/aszf/old.pdf', 'old version');
    Storage::disk('materials')->put('legal/aszf/new.pdf', 'new version');
    LegalDocumentVersion::factory()->ofType(LegalDocumentType::Terms)->create(['file_path' => 'legal/aszf/old.pdf', 'effective_from' => today()->subYear()]);
    LegalDocumentVersion::factory()->ofType(LegalDocumentType::Terms)->create(['file_path' => 'legal/aszf/new.pdf', 'effective_from' => today()]);
    LegalDocumentVersion::factory()->ofType(LegalDocumentType::Terms)->create(['file_path' => 'legal/aszf/future.pdf', 'effective_from' => today()->addMonth()]);

    $response = $this->get('/aszf');

    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($response->streamedContent())->toBe('new version');
});

it('returns 404 while a document is not uploaded', function () {
    $response = $this->get('/cookie-tajekoztato');

    $response->assertNotFound();
});

it('links only the uploaded documents in the public footer', function () {
    LegalDocumentVersion::factory()->ofType(LegalDocumentType::PrivacyPolicy)->create();

    $response = $this->get(route('public.home'));

    $response->assertSee('/adatkezelesi-tajekoztato', escape: false)->assertDontSee('/cookie-tajekoztato', escape: false);
});

it('shows the legal footer on the public Értéktükör recording too', function () {
    LegalDocumentVersion::factory()->ofType(LegalDocumentType::CookiePolicy)->create();

    $response = $this->get(route('mirror.record'));

    $response->assertOk()
        ->assertSee('aria-label="Jogi dokumentumok"', escape: false)
        ->assertSee('/cookie-tajekoztato', escape: false);
});

it('has no footer while no document is uploaded', function () {
    $response = $this->get(route('public.home'));

    $response->assertOk()->assertDontSee('<footer', escape: false);
});

it('links the uploaded documents in the customer footer, in a new tab', function () {
    LegalDocumentVersion::factory()->ofType(LegalDocumentType::Terms)->create();
    LegalDocumentVersion::factory()->ofType(LegalDocumentType::PrivacyPolicy)->create();
    $organization = Organization::factory()->create();
    actingAsCustomer(customerOf($organization));

    $response = $this->get(route('client.home', $organization));

    $response->assertOk()
        ->assertSee('powered by')
        ->assertSee('aria-label="Jogi dokumentumok"', escape: false)
        ->assertSee('href="'.route('legal.show', 'aszf').'" target="_blank"', escape: false)
        ->assertSee('href="'.route('legal.show', 'adatkezelesi-tajekoztato').'" target="_blank"', escape: false)
        ->assertDontSee('/cookie-tajekoztato', escape: false);
});

it('keeps the powered by line in the customer footer while no document is uploaded', function () {
    $response = $this->get(route('client.login'));

    $response->assertOk()
        ->assertSee('<footer', escape: false)
        ->assertSee('powered by')
        ->assertDontSee('aria-label="Jogi dokumentumok"', escape: false);
});
