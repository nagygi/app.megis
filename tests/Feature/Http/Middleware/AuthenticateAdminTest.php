<?php

use App\Models\User;
use App\Services\Auth\GuardSession;

it('sends guests to the admin login', function () {
    $response = $this->get(route('admin.organizations.index'));

    $response->assertRedirect(route('admin.login'));
});

it('shows "Nincs jogosultságod" to a logged-in customer', function () {
    actingAsCustomer(customerOf());

    $response = $this->get(route('admin.organizations.index'));

    $response->assertForbidden();
});

it('lets an active admin in', function () {
    actingAsAdmin();

    $response = $this->get(route('admin.organizations.index'));

    $response->assertOk();
});

it('ends the admin session after six idle hours', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin, 'admin')->withSession([
        'auth_epoch.admin' => $admin->auth_epoch,
        GuardSession::ADMIN_LAST_ACTIVITY_KEY => now()->subHours(6)->subMinute()->getTimestamp(),
    ]);

    $response = $this->get(route('admin.organizations.index'));

    $response->assertRedirect(route('admin.login'));
    $this->assertGuest('admin');
});

it('ends the session after the password was changed elsewhere', function () {
    $admin = actingAsAdmin();
    $admin->invalidateSessions();

    $response = $this->get(route('admin.organizations.index'));

    $response->assertRedirect(route('admin.login'));
});

it('ends the session of a deactivated admin', function () {
    $admin = actingAsAdmin();
    $admin->forceFill(['deactivated_at' => now()])->save();

    $response = $this->get(route('admin.organizations.index'));

    $response->assertRedirect(route('admin.login'));
});

it('keeps the Rendszer pages for superadmins', function () {
    actingAsAdmin();

    $response = $this->get(route('admin.system.index'));

    $response->assertForbidden();
});

it('shows the Rendszer pages to a superadmin', function () {
    actingAsAdmin(User::factory()->superadmin()->create());

    $response = $this->get(route('admin.system.health'));

    $response->assertOk()->assertSee('Health');
});
