<?php

use App\Enums\AuditEventType;
use App\Livewire\Admin\Clients\Index;
use App\Livewire\Admin\Clients\Show;
use App\Mail\EmailChangedMail;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('lets a superadmin change the e-mail, telling both addresses and logging it', function () {
    Mail::fake();
    $superadmin = actingAsAdmin(User::factory()->superadmin()->create());
    $customer = customerOf();
    $oldEmail = $customer->email;

    Livewire::test(Show::class, ['user' => $customer])
        ->call('startEditing')
        ->set('email', 'uj.cim@example.com')
        ->call('save')
        ->assertHasNoErrors();

    expect($customer->fresh()->email)->toBe('uj.cim@example.com');
    Mail::assertQueued(EmailChangedMail::class, fn (EmailChangedMail $mail) => $mail->hasTo($oldEmail) && $mail->toOldAddress);
    Mail::assertQueued(EmailChangedMail::class, fn (EmailChangedMail $mail) => $mail->hasTo('uj.cim@example.com') && ! $mail->toOldAddress);
    expect(AuditEvent::query()->sole())
        ->type->toBe(AuditEventType::EmailChanged)
        ->actor_id->toBe($superadmin->id);
});

it('forbids an admin from changing a customer e-mail', function () {
    actingAsAdmin();
    $customer = customerOf();

    Livewire::test(Show::class, ['user' => $customer])
        ->call('startEditing')
        ->set('email', 'uj.cim@example.com')
        ->call('save')
        ->assertForbidden();

    expect($customer->fresh()->email)->not->toBe('uj.cim@example.com');
});

it('lets any admin correct the name', function () {
    actingAsAdmin();
    $customer = customerOf();

    Livewire::test(Show::class, ['user' => $customer])
        ->call('startEditing')
        ->set('last_name', 'Javított')
        ->call('save')
        ->assertHasNoErrors();

    expect($customer->fresh()->last_name)->toBe('Javított');
});

it('lists customers, not pure admins', function () {
    actingAsAdmin();
    customerOf()->update(['last_name' => 'Ugyfelnev']);
    User::factory()->admin()->create(['last_name' => 'Csakadmin']);

    $component = Livewire::test(Index::class);

    $component->assertSee('Ugyfelnev')->assertDontSee('Csakadmin');
});
