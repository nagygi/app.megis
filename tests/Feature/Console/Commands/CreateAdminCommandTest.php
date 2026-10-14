<?php

use App\Enums\UserRole;
use App\Mail\AdminInvitationMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

it('creates the first superadmin with a hidden, confirmed password', function () {
    fakeTurnstile();

    $this->artisan('megis:admin:create')
        ->expectsQuestion('Vezetéknév', 'Nagy')
        ->expectsQuestion('Keresztnév', 'Luca')
        ->expectsQuestion('E-mail', 'Luca@Megis.hu')
        ->expectsQuestion('Jelszó', 'a-long-enough-password')
        ->expectsQuestion('Jelszó még egyszer', 'a-long-enough-password')
        ->assertSuccessful();

    $user = User::query()->firstWhere('email', 'luca@megis.hu');
    expect($user)
        ->role->toBe(UserRole::Superadmin)
        ->activated_at->not->toBeNull()
        ->and(Hash::check('a-long-enough-password', $user->password))->toBeTrue();
});

it('sends an invitation instead of asking for a password', function () {
    Mail::fake();

    $this->artisan('megis:admin:create', ['--role' => 'admin', '--invite' => true])
        ->expectsQuestion('Vezetéknév', 'Kiss')
        ->expectsQuestion('Keresztnév', 'Péter')
        ->expectsQuestion('E-mail', 'peter@megis.hu')
        ->assertSuccessful();

    expect(User::query()->firstWhere('email', 'peter@megis.hu'))
        ->role->toBe(UserRole::Admin)
        ->password->toBeNull();
    Mail::assertQueued(AdminInvitationMail::class, fn (AdminInvitationMail $mail) => $mail->inviter === null);
});

it('asks before giving the role to an existing account', function () {
    $customer = User::factory()->create(['email' => 'meglevo@megis.hu']);

    $this->artisan('megis:admin:create', ['--invite' => true])
        ->expectsQuestion('Vezetéknév', 'Meg')
        ->expectsQuestion('Keresztnév', 'Levo')
        ->expectsQuestion('E-mail', 'meglevo@megis.hu')
        ->expectsConfirmation('Ez az e-mail cím már létezik. Kapjon Szuperadmin szerepkört?', 'no')
        ->assertFailed();

    expect($customer->fresh()->role)->toBeNull();
});

it('rejects an unknown role', function () {
    $this->artisan('megis:admin:create', ['--role' => 'owner'])->assertFailed();
});
