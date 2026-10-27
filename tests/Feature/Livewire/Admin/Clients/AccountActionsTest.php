<?php

use App\Enums\AuditEventType;
use App\Livewire\Admin\Clients\Index;
use App\Livewire\Admin\Clients\Show;
use App\Mail\AccountDeletedMail;
use App\Models\AuditEvent;
use App\Models\Membership;
use App\Models\User;
use App\Services\Accounts\AccountDeletionService;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('lets an admin inactivate a customer from the list, and activate them again (33 · 33.8)', function () {
    $admin = actingAsAdmin();
    $customer = customerOf();
    $epoch = (int) $customer->auth_epoch;

    Livewire::test(Index::class)->call('deactivate', $customer->id)->assertHasNoErrors();

    $customer->refresh();
    expect($customer->isActive())->toBeFalse()
        ->and($customer->auth_epoch)->toBeGreaterThan($epoch)
        ->and(AuditEvent::query()->sole())
        ->type->toBe(AuditEventType::AccountDeactivated)
        ->actor_id->toBe($admin->id);

    Livewire::test(Index::class)->call('activate', $customer->id)->assertHasNoErrors();

    expect($customer->refresh()->isActive())->toBeTrue()
        ->and(AuditEvent::query()->latest('id')->first()->type)->toBe(AuditEventType::AccountActivated);
});

it('greys out "Archiválom" for an admin, and the server refuses it', function () {
    actingAsAdmin();
    $customer = customerOf();

    Livewire::test(Index::class)
        ->assertSee('Archiválom')
        ->assertSee('Archiválni csak szuperadmin tud.')
        ->call('archive', $customer->id)
        ->assertForbidden();

    expect($customer->refresh()->isDeletionRequested())->toBeFalse();
});

it('lets a superadmin archive a customer: the account deletion starts (33 · 33.8)', function () {
    Mail::fake();
    $superadmin = actingAsAdmin(User::factory()->superadmin()->create());
    $customer = customerOf();
    $customer->update(['last_name' => 'Archivalando']);
    $email = $customer->email;

    Livewire::test(Index::class)->call('archive', $customer->id)->assertHasNoErrors();

    $customer->refresh();
    expect($customer->isDeletionRequested())->toBeTrue()
        ->and($customer->isActive())->toBeFalse()
        ->and($customer->clientStatus())->toBe('archived')
        ->and(AuditEvent::query()->sole())
        ->type->toBe(AuditEventType::AccountDeletionRequested)
        ->actor_id->toBe($superadmin->id);

    Livewire::test(Index::class)->set('filters.status', 'archived')->assertSee('Archivalando');
    Livewire::test(Index::class)->set('filters.status', 'inactive')->assertDontSee('Archivalando');

    $this->artisan('megis:accounts:purge')->assertSuccessful();

    expect($customer->refresh()->isAnonymized())->toBeTrue();
    Mail::assertQueued(AccountDeletedMail::class, fn (AccountDeletedMail $mail) => $mail->hasTo($email) && $mail->byAdmin);
});

it('tells in the mail who deleted the account', function () {
    expect((new AccountDeletedMail(byAdmin: true))->render())->toContain('A mégis adminisztrátora törölte a fiókodat')
        ->and((new AccountDeletedMail)->render())->toContain('Ha nem te kérted');
});

it('greys out every account action of an admin account on the list', function () {
    actingAsAdmin(User::factory()->superadmin()->create());
    $adminMember = User::factory()->admin()->create(['last_name' => 'Adminugyfel']);
    Membership::factory()->for($adminMember)->create();

    Livewire::test(Index::class)
        ->assertSee('Adminugyfel')
        ->assertSee('Admin fiók nevében nem lehet belépni.')
        ->assertSee('Admin fiók: az Admin felhasználók között kezelhető.')
        ->assertDontSee(route('admin.impersonate', $adminMember));

    Livewire::test(Index::class)->call('deactivate', $adminMember->id)->assertForbidden();
    Livewire::test(Index::class)->call('archive', $adminMember->id)->assertForbidden();

    expect($adminMember->refresh())
        ->isActive()->toBeTrue()
        ->isDeletionRequested()->toBeFalse();
});

it('offers "Belépek a nevében" on the list only where it works', function () {
    actingAsAdmin();
    $active = customerOf();
    $inactive = customerOf();
    $inactive->forceFill(['deactivated_at' => now()])->save();
    $unregistered = customerOf();
    $unregistered->forceFill(['activated_at' => null])->save();

    Livewire::test(Index::class)
        ->assertSee(route('admin.impersonate', $active))
        ->assertDontSee(route('admin.impersonate', $inactive))
        ->assertDontSee(route('admin.impersonate', $unregistered))
        ->assertSee('A felhasználó fiókja inaktív.')
        ->assertSee('A felhasználó még nem regisztrált.');
});

it('does not activate an archived account', function () {
    actingAsAdmin(User::factory()->superadmin()->create());
    $customer = customerOf();
    app(AccountDeletionService::class)->request($customer);

    Livewire::test(Index::class)
        ->assertSee('A felhasználó archivált.')
        ->call('activate', $customer->id)
        ->assertForbidden();

    expect($customer->refresh()->isActive())->toBeFalse();
});

it('inactivates from the datasheet, and the header shows it at once (33 · 33.2)', function () {
    actingAsAdmin();
    $customer = customerOf();

    Livewire::test(Show::class, ['user' => $customer])
        ->assertSee(route('admin.impersonate', $customer))
        ->call('deactivate', $customer->id)
        ->assertSee('Inaktív')
        ->assertDontSee(route('admin.impersonate', $customer));

    expect($customer->refresh()->isActive())->toBeFalse();
});

it('acts on the datasheet\'s own account only', function () {
    actingAsAdmin();
    $customer = customerOf();
    $other = customerOf();

    Livewire::test(Show::class, ['user' => $customer])->call('deactivate', $other->id)->assertStatus(404);

    expect($other->refresh()->isActive())->toBeTrue();
});
