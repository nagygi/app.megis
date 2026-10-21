<?php

use App\Enums\TokenPurpose;
use App\Livewire\Admin\AdminUsers\Index;
use App\Mail\AdminInvitationMail;
use App\Mail\AdminPasswordResetMail;
use App\Models\User;
use App\Services\Auth\TokenService;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('lists admins with their status and hides inactive ones by default', function () {
    actingAsAdmin(User::factory()->superadmin()->create(['last_name' => 'Aaa']));
    $invited = User::factory()->admin()->invited()->create(['last_name' => 'Meghivott']);
    app(TokenService::class)->issue($invited, TokenPurpose::AdminInvitation);
    User::factory()->admin()->invited()->create(['last_name' => 'Lejart']);
    User::factory()->admin()->locked()->create(['last_name' => 'Zarolt']);
    User::factory()->admin()->deactivated()->create(['last_name' => 'Kivezetett']);
    User::factory()->create(['last_name' => 'Ugyfel']);

    $component = Livewire::test(Index::class);

    $component->assertSee('Meghivott')->assertSee('Meghívva')
        ->assertSee('Lejart')->assertSee('Meghívó lejárt')
        ->assertSee('Zarolt')->assertSee('Zárolva')
        ->assertDontSee('Kivezetett')
        ->assertDontSee('Ugyfel');
});

it('shows inactive admins when asked', function () {
    actingAsAdmin();
    User::factory()->admin()->deactivated()->create(['last_name' => 'Kivezetett']);

    $component = Livewire::test(Index::class)->set('showInactive', true);

    $component->assertSee('Kivezetett');
});

it('searches by name and e-mail', function () {
    actingAsAdmin();
    User::factory()->admin()->create(['last_name' => 'Keresett', 'email' => 'keresett@megis.hu']);
    User::factory()->admin()->create(['last_name' => 'Masik']);

    $component = Livewire::test(Index::class)->set('search', 'keresett@');

    $component->assertSee('Keresett')->assertDontSee('Masik');
});

it('resets a password: the old one stops working, sessions end and a link is sent', function () {
    Mail::fake();
    $actor = actingAsAdmin();
    $target = User::factory()->admin()->create();

    Livewire::test(Index::class)->call('resetPassword', $target->id);

    $target->refresh();
    expect($target->password)->toBeNull()
        ->and($target->auth_epoch)->toBe(1);
    Mail::assertQueued(AdminPasswordResetMail::class, fn (AdminPasswordResetMail $mail) => $mail->hasTo($target->email) && $mail->admin->is($actor));
});

it('deactivates an admin, ending sessions and invitation links', function () {
    actingAsAdmin();
    $target = User::factory()->admin()->invited()->create();
    $issued = app(TokenService::class)->issue($target, TokenPurpose::AdminInvitation);

    Livewire::test(Index::class)->call('deactivate', $target->id);

    expect($target->fresh()->deactivated_at)->not->toBeNull()
        ->and($issued->token->fresh()->invalidated_at)->not->toBeNull();
});

it('refuses to deactivate yourself', function () {
    $actor = actingAsAdmin();

    Livewire::test(Index::class)->call('deactivate', $actor->id)->assertForbidden();

    expect($actor->fresh()->deactivated_at)->toBeNull();
});

it('reactivates an admin who keeps the old password', function () {
    actingAsAdmin();
    $target = User::factory()->admin()->deactivated()->create();
    $passwordHash = $target->password;

    Livewire::test(Index::class)->call('activate', $target->id);

    expect($target->fresh())
        ->deactivated_at->toBeNull()
        ->password->toBe($passwordHash);
});

it('resends the invitation of an admin who never logged in', function () {
    Mail::fake();
    actingAsAdmin();
    $target = User::factory()->admin()->invited()->create();

    Livewire::test(Index::class)->call('resendInvitation', $target->id);

    Mail::assertQueued(AdminInvitationMail::class, fn (AdminInvitationMail $mail) => $mail->hasTo($target->email));
});

it('warns when the mail allowlist will drop a resent invitation or a password reset link', function (string $action, bool $isActivated) {
    Mail::fake();
    actingAsAdmin();
    config(['megis.mail_allowlist' => ['@exot.hu']]);
    $factory = User::factory()->admin();
    $target = ($isActivated ? $factory : $factory->invited())->create(['email' => 'luca@gmail.com']);

    Livewire::test(Index::class)->call($action, $target->id)
        ->assertSee('Nem ment ki levél ide: luca@gmail.com. Ebben a környezetben csak a MEGIS_MAIL_ALLOWLIST címzettjei kapnak levelet.')
        ->assertDontSee('küldtünk');
})->with([
    'resent invitation' => ['resendInvitation', false],
    'password reset' => ['resetPassword', true],
]);

it('shows which addresses receive mail while the allowlist filters', function () {
    actingAsAdmin();

    config(['megis.mail_allowlist' => ['@exot.hu', 'tester@megis.team']]);
    $this->get(route('admin.admins.index'))->assertOk()
        ->assertSee('Teszt környezet: levél csak ezekre a címekre megy ki: @exot.hu, tester@megis.team.');

    config(['megis.mail_allowlist' => []]);
    $this->get(route('admin.admins.index'))->assertOk()->assertDontSee('Teszt környezet');
});
