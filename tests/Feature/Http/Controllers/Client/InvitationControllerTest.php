<?php

use App\Enums\LegalDocumentType;
use App\Enums\MembershipStatus;
use App\Enums\TokenPurpose;
use App\Models\LegalAcceptance;
use App\Models\LegalDocumentVersion;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Services\Auth\TokenService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;

/**
 * @return array{0: Membership, 1: string}
 */
function invitedMembership(?Organization $organization = null): array
{
    $organization ??= Organization::factory()->create(['name' => 'Acme Kft.']);
    $inviter = User::factory()->admin()->create(['last_name' => 'Nagy', 'first_name' => 'Luca']);
    $membership = Membership::factory()->invited()->for($organization)->create([
        'user_id' => User::factory()->invited()->state(['first_name' => 'Anna', 'last_name' => null]),
    ]);
    $token = app(TokenService::class)->issue($membership->user, TokenPurpose::ClientInvitation, $organization, $inviter)->plainToken;

    return [$membership, $token];
}

function postRegistration(string $token, array $overrides = []): TestResponse
{
    return test()->post(route('client.invitation.store', $token), [
        'last_name' => 'Kiss',
        'first_name' => 'Anna',
        'password' => '',
        'password_confirmation' => '',
        'accept' => '1',
        'cf-turnstile-response' => 'turnstile-token',
        ...$overrides,
    ]);
}

it('shows the registration form with who invited whom, without using up the link', function () {
    [$membership, $token] = invitedMembership();

    $response = $this->get(route('client.invitation.show', $token));

    $response->assertOk()
        ->assertSee('Nagy Luca meghívott a(z) Acme Kft. felhasználójaként.')
        ->assertSee('value="Anna"', escape: false);
    expect($membership->user->tokens()->sole()->consumed_at)->toBeNull();
});

it('registers the customer, records both legal acceptances and logs in', function () {
    fakeTurnstile();
    $terms = LegalDocumentVersion::factory()->ofType(LegalDocumentType::Terms)->create();
    $privacy = LegalDocumentVersion::factory()->ofType(LegalDocumentType::PrivacyPolicy)->create();
    LegalDocumentVersion::factory()->ofType(LegalDocumentType::CookiePolicy)->create();
    [$membership, $token] = invitedMembership();

    $response = postRegistration($token);

    $response->assertRedirect(route('client.home', $membership->organization));
    $user = $membership->user->fresh();
    $this->assertAuthenticatedAs($user, 'web');
    expect($user)
        ->last_name->toBe('Kiss')
        ->activated_at->not->toBeNull()
        ->password->toBeNull()
        ->and($membership->fresh()->status)->toBe(MembershipStatus::Active)
        ->and($user->tokens()->sole()->consumed_at)->not->toBeNull();
    $this->assertDatabaseHas('legal_acceptances', ['user_id' => $user->id, 'legal_document_version_id' => $terms->id]);
    $this->assertDatabaseHas('legal_acceptances', ['user_id' => $user->id, 'legal_document_version_id' => $privacy->id]);
    expect($user->id)->toBeInt()->and(LegalAcceptance::query()->count())->toBe(2);
});

it('stores an optional password with the customer rule', function () {
    fakeTurnstile();
    [$membership, $token] = invitedMembership();

    postRegistration($token, ['password' => 'jelszo-12', 'password_confirmation' => 'jelszo-12']);

    expect(Hash::check('jelszo-12', $membership->user->fresh()->password))->toBeTrue();
});

it('requires the acceptance of the terms', function () {
    fakeTurnstile();
    [$membership, $token] = invitedMembership();

    $response = postRegistration($token, ['accept' => null]);

    $response->assertSessionHasErrors(['accept' => 'A regisztrációhoz el kell fogadnod az ÁSZF-et és az adatkezelési tájékoztatót.']);
    expect($membership->user->fresh()->activated_at)->toBeNull();
});

it('requires both names', function () {
    fakeTurnstile();
    [, $token] = invitedMembership();

    $response = postRegistration($token, ['last_name' => '', 'first_name' => '']);

    $response->assertSessionHasErrors(['last_name', 'first_name']);
});

it('shows the expired page after 14 days', function () {
    [, $token] = invitedMembership();
    $this->travel(15)->days();

    $response = $this->get(route('client.invitation.show', $token));

    $response->assertOk()->assertSee('Ez a meghívó már lejárt. Kérj újat attól, aki meghívott.');
});

it('voids the invitation when the organization is deactivated', function () {
    [$membership, $token] = invitedMembership();
    $membership->organization->forceFill(['deactivated_at' => now()])->save();

    $response = $this->get(route('client.invitation.show', $token));

    $response->assertSee('Ez a meghívó már lejárt.');
});

it('voids the previous link when a new invitation is sent', function () {
    [$membership, $oldToken] = invitedMembership();
    app(TokenService::class)->issue($membership->user, TokenPurpose::ClientInvitation, $membership->organization);

    $response = $this->get(route('client.invitation.show', $oldToken));

    $response->assertSee('Ez a meghívó már lejárt.');
});
