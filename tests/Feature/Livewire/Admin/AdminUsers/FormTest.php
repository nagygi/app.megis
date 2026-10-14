<?php

use App\Enums\TokenPurpose;
use App\Enums\UserRole;
use App\Livewire\Admin\AdminUsers\Form;
use App\Mail\AdminInvitationMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('invites a new admin with a 24-hour link', function () {
    Mail::fake();
    $actor = actingAsAdmin();

    Livewire::test(Form::class)
        ->set('last_name', 'Kovács')
        ->set('first_name', 'Anna')
        ->set('email', 'Anna@Megis.hu')
        ->set('role', UserRole::Admin->value)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.admins.index'));

    $invited = User::query()->firstWhere('email', 'anna@megis.hu');
    expect($invited)
        ->role->toBe(UserRole::Admin)
        ->activated_at->toBeNull();
    $token = $invited->tokens()->where('purpose', TokenPurpose::AdminInvitation)->sole();
    expect($token->expires_at->diffInHours(now(), true))->toEqualWithDelta(24, 0.1);
    Mail::assertQueued(AdminInvitationMail::class, fn (AdminInvitationMail $mail) => $mail->hasTo('anna@megis.hu') && $mail->inviter->is($actor));
});

it('gives the admin role to an existing customer account instead of creating another', function () {
    Mail::fake();
    actingAsAdmin();
    $customer = User::factory()->create(['email' => 'luca@megis.hu']);

    Livewire::test(Form::class)
        ->set('last_name', 'Luca')
        ->set('first_name', 'Nagy')
        ->set('email', 'luca@megis.hu')
        ->call('save')
        ->assertHasNoErrors();

    expect(User::query()->where('email', 'luca@megis.hu')->count())->toBe(1)
        ->and($customer->fresh()->role)->toBe(UserRole::Admin);
});

it('rejects an address that already belongs to an admin', function () {
    actingAsAdmin();
    User::factory()->admin()->create(['email' => 'taken@megis.hu']);

    Livewire::test(Form::class)
        ->set('last_name', 'X')
        ->set('first_name', 'Y')
        ->set('email', 'taken@megis.hu')
        ->call('save')
        ->assertHasErrors(['email' => 'Ezzel az e-mail címmel már van admin.']);
});

it('requires names and a valid e-mail', function () {
    actingAsAdmin();

    Livewire::test(Form::class)
        ->set('email', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['last_name' => 'required', 'first_name' => 'required', 'email' => 'email']);
});

it('forbids an admin from inviting a superadmin', function () {
    actingAsAdmin();

    Livewire::test(Form::class)
        ->set('last_name', 'X')
        ->set('first_name', 'Y')
        ->set('email', 'new@megis.hu')
        ->set('role', UserRole::Superadmin->value)
        ->call('save')
        ->assertForbidden();

    expect(User::query()->where('email', 'new@megis.hu')->exists())->toBeFalse();
});

it('lets a superadmin appoint another admin as superadmin', function () {
    actingAsAdmin(User::factory()->superadmin()->create());
    $admin = User::factory()->admin()->create();

    Livewire::test(Form::class, ['user' => $admin])
        ->set('role', UserRole::Superadmin->value)
        ->call('save')
        ->assertHasNoErrors();

    expect($admin->fresh()->role)->toBe(UserRole::Superadmin);
});

it('does not let an admin save a superadmin', function () {
    actingAsAdmin();
    $superadmin = User::factory()->superadmin()->create();

    Livewire::test(Form::class, ['user' => $superadmin])
        ->set('first_name', 'Changed')
        ->call('save')
        ->assertForbidden();
});
