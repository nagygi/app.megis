<?php

use App\Enums\TokenPurpose;
use App\Livewire\Client\Profile;
use App\Mail\PasswordChangedMail;
use App\Mail\PasswordConfirmationCodeMail;
use App\Models\Organization;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('saves the name when the field is left', function () {
    $organization = Organization::factory()->create();
    $customer = actingAsCustomer(customerOf($organization));

    Livewire::test(Profile::class, ['organization' => $organization])
        ->set('last_name', 'Új')
        ->assertSet('nameSaved', true)
        ->assertSee('Mentve');

    expect($customer->fresh()->last_name)->toBe('Új');
});

it('changes the password with the current one and ends other sessions', function () {
    fakeTurnstile();
    Mail::fake();
    $organization = Organization::factory()->create();
    $customer = customerOf($organization);
    $customer->forceFill(['password' => Hash::make('regi-jelszo')])->save();
    actingAsCustomer($customer);

    Livewire::test(Profile::class, ['organization' => $organization])
        ->set('current_password', 'regi-jelszo')
        ->set('password', 'uj-jelszo-1')
        ->set('password_confirmation', 'uj-jelszo-1')
        ->call('savePassword')
        ->assertHasNoErrors()
        ->assertSet('passwordSaved', true);

    $customer->refresh();
    expect(Hash::check('uj-jelszo-1', $customer->password))->toBeTrue()
        ->and($customer->auth_epoch)->toBe(1);
    Mail::assertQueued(PasswordChangedMail::class);
});

it('rejects a wrong current password', function () {
    fakeTurnstile();
    $organization = Organization::factory()->create();
    $customer = customerOf($organization);
    $customer->forceFill(['password' => Hash::make('regi-jelszo')])->save();
    actingAsCustomer($customer);

    Livewire::test(Profile::class, ['organization' => $organization])
        ->set('current_password', 'rossz-jelszo')
        ->set('password', 'uj-jelszo-1')
        ->set('password_confirmation', 'uj-jelszo-1')
        ->call('savePassword')
        ->assertHasErrors(['current_password']);
});

it('sets a first password confirmed by an e-mailed code', function () {
    fakeTurnstile();
    Mail::fake();
    $organization = Organization::factory()->create();
    $customer = customerOf($organization);
    $customer->forceFill(['password' => null])->save();
    actingAsCustomer($customer);

    $component = Livewire::test(Profile::class, ['organization' => $organization])->call('sendCode');

    $code = null;
    Mail::assertQueued(PasswordConfirmationCodeMail::class, function (PasswordConfirmationCodeMail $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    $component->set('code', $code)
        ->set('password', 'elso-jelszo')
        ->set('password_confirmation', 'elso-jelszo')
        ->call('savePassword')
        ->assertHasNoErrors();

    expect(Hash::check('elso-jelszo', $customer->fresh()->password))->toBeTrue()
        ->and($customer->tokens()->where('purpose', TokenPurpose::PasswordConfirmationCode)->sole()->consumed_at)->not->toBeNull();
});

it('rejects a wrong confirmation code', function () {
    fakeTurnstile();
    Mail::fake();
    $organization = Organization::factory()->create();
    $customer = customerOf($organization);
    $customer->forceFill(['password' => null])->save();
    actingAsCustomer($customer);

    Livewire::test(Profile::class, ['organization' => $organization])
        ->call('sendCode')
        ->set('code', '000000')
        ->set('password', 'elso-jelszo')
        ->set('password_confirmation', 'elso-jelszo')
        ->call('savePassword')
        ->assertHasErrors(['code' => 'Hibás vagy lejárt kód.']);
});

it('requires at least 8 characters for a customer password', function () {
    fakeTurnstile();
    $organization = Organization::factory()->create();
    $customer = customerOf($organization);
    $customer->forceFill(['password' => Hash::make('regi-jelszo')])->save();
    actingAsCustomer($customer);

    Livewire::test(Profile::class, ['organization' => $organization])
        ->set('current_password', 'regi-jelszo')
        ->set('password', 'rovid')
        ->set('password_confirmation', 'rovid')
        ->call('savePassword')
        ->assertHasErrors(['password']);
});

it('renders the profile page with a white header', function () {
    $organization = Organization::factory()->create();
    actingAsCustomer(customerOf($organization));

    $response = $this->get(route('client.profile', $organization));

    $response->assertOk()->assertSee('Profil')->assertSee('Az e-mail címed módosítását a mégistől kérheted.');
});
