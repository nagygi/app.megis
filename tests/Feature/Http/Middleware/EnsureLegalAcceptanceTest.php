<?php

use App\Enums\LegalDocumentType;
use App\Models\LegalDocumentVersion;
use App\Models\Organization;

it('holds the customer on the acceptance page after a version that must be accepted again', function () {
    $organization = Organization::factory()->create();
    actingAsCustomer(customerOf($organization));
    LegalDocumentVersion::factory()->ofType(LegalDocumentType::Terms)->requiringReacceptance()->create();

    $this->get(route('client.home', $organization))->assertRedirect(route('client.legal.show'));
    $this->get(route('client.legal.show'))->assertOk()->assertSee('Változott a(z) ÁSZF.')->assertSee('Kijelentkezem');
});

it('lets the customer continue after accepting', function () {
    $organization = Organization::factory()->create();
    $customer = actingAsCustomer(customerOf($organization));
    $version = LegalDocumentVersion::factory()->ofType(LegalDocumentType::PrivacyPolicy)->requiringReacceptance()->create();

    $this->post(route('client.legal.store'))->assertRedirect();

    $this->assertDatabaseHas('legal_acceptances', ['user_id' => $customer->id, 'legal_document_version_id' => $version->id]);
    $this->get(route('client.home', $organization))->assertOk();
});

it('does not ask again for a version without the re-acceptance switch', function () {
    $organization = Organization::factory()->create();
    actingAsCustomer(customerOf($organization));
    LegalDocumentVersion::factory()->ofType(LegalDocumentType::Terms)->create();

    $response = $this->get(route('client.home', $organization));

    $response->assertOk();
});

it('waits for a future version to come into force', function () {
    $organization = Organization::factory()->create();
    actingAsCustomer(customerOf($organization));
    LegalDocumentVersion::factory()->ofType(LegalDocumentType::Terms)->requiringReacceptance()->create(['effective_from' => today()->addDay()]);

    $this->get(route('client.home', $organization))->assertOk();
    $this->travel(1)->day();
    $this->get(route('client.home', $organization))->assertRedirect(route('client.legal.show'));
});
