<?php

use App\Enums\MembershipStatus;
use App\Enums\TokenPurpose;
use App\Livewire\Admin\Organizations\Members;
use App\Mail\ClientInvitationMail;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Services\Auth\TokenService;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('shows the headcount and the invite button while there is a free seat', function () {
    actingAsAdmin();
    $organization = Organization::factory()->withHeadcountLimit(10)->create();
    Membership::factory()->count(8)->for($organization)->create();

    $component = Livewire::test(Members::class, ['organization' => $organization]);

    $component->assertSee('8 / 10 felhasználó')->assertSee('Felhasználót hívok meg');
});

it('greys out the invite button with the reason at the limit', function () {
    actingAsAdmin();
    $organization = Organization::factory()->withHeadcountLimit(2)->create();
    Membership::factory()->count(2)->for($organization)->create();

    $component = Livewire::test(Members::class, ['organization' => $organization]);

    $component->assertSee('Elértétek a létszám limitet (2 / 2).');
});

it('sends the invitation of a recorded user from the list', function () {
    Mail::fake();
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $membership = Membership::factory()->added()->for($organization)->create(['user_id' => User::factory()->invited()]);

    Livewire::test(Members::class, ['organization' => $organization])
        ->assertSee('Elküldöm a meghívót')
        ->call('sendInvitation', $membership->id);

    expect($membership->fresh()->status)->toBe(MembershipStatus::Invited);
    Mail::assertQueued(ClientInvitationMail::class);
});

it('shows "Meghívó lejárt" once the invitation link expired', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $membership = Membership::factory()->invited()->for($organization)->create(['user_id' => User::factory()->invited()]);
    app(TokenService::class)->issue($membership->user, TokenPurpose::ClientInvitation, $organization);
    $this->travel(15)->days();

    $component = Livewire::test(Members::class, ['organization' => $organization]);

    $component->assertSee('Meghívó lejárt');
});

it('deactivates a member and voids the open invitation', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $membership = Membership::factory()->invited()->for($organization)->create(['user_id' => User::factory()->invited()]);
    $issued = app(TokenService::class)->issue($membership->user, TokenPurpose::ClientInvitation, $organization);

    Livewire::test(Members::class, ['organization' => $organization])->call('deactivate', $membership->id);

    expect($membership->fresh()->status)->toBe(MembershipStatus::Inactive)
        ->and($issued->token->fresh()->invalidated_at)->not->toBeNull();
});

it('reactivates a member only while there is a free seat', function () {
    actingAsAdmin();
    $organization = Organization::factory()->withHeadcountLimit(1)->create();
    Membership::factory()->for($organization)->create();
    $inactive = Membership::factory()->inactive()->for($organization)->create();

    Livewire::test(Members::class, ['organization' => $organization])->call('activate', $inactive->id);

    expect($inactive->fresh()->status)->toBe(MembershipStatus::Inactive);
});

it('offers "Belépek a nevében" for an active member', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $membership = Membership::factory()->for($organization)->create();

    $component = Livewire::test(Members::class, ['organization' => $organization]);

    $component->assertSee(route('admin.impersonate', $membership->user));
});

it('greys out "Belépek a nevében" with the reason', function (Closure $membershipFactory, string $reason) {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $membership = $membershipFactory()->for($organization)->create();

    $component = Livewire::test(Members::class, ['organization' => $organization]);

    $component->assertSee('Belépek a nevében')
        ->assertSee($reason)
        ->assertDontSee(route('admin.impersonate', $membership->user));
})->with([
    'deactivated account' => [fn () => Membership::factory()->state(['user_id' => User::factory()->deactivated()]), 'A felhasználó fiókja inaktív.'],
    'inactive in the organization' => [fn () => Membership::factory()->inactive(), 'A felhasználó ebben a szervezetben inaktív.'],
    'not registered yet' => [fn () => Membership::factory()->invited()->state(['user_id' => User::factory()->invited()]), 'A felhasználó még nem regisztrált.'],
    'registered, invitation still open' => [fn () => Membership::factory()->invited(), 'A felhasználó tagsága ebben a szervezetben még nem aktív. A meghívó elküldésével azonnal aktív lesz.'],
]);

it('shows no "Belépek a nevében" for an admin account', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    Membership::factory()->for($organization)->create(['user_id' => User::factory()->admin()]);

    $component = Livewire::test(Members::class, ['organization' => $organization]);

    $component->assertDontSee('Belépek a nevében');
});

it('filters members by tag', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    Membership::factory()->organizationLeader()->for($organization)->create(['user_id' => User::factory()->state(['last_name' => 'Vezeto'])]);
    Membership::factory()->partner()->for($organization)->create(['user_id' => User::factory()->state(['last_name' => 'Kulsos'])]);

    $component = Livewire::test(Members::class, ['organization' => $organization])->set('filters.tag', 'partner');

    $component->assertSee('Kulsos')->assertDontSee('Vezeto');
});
