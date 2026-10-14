<?php

use App\Enums\AuditEventType;
use App\Enums\LegalDocumentType;
use App\Livewire\Client\Profile;
use App\Models\AuditEvent;
use App\Models\Impersonation;
use App\Models\LegalDocumentVersion;
use App\Models\Organization;
use App\Models\User;
use App\Services\Auth\GuardSession;
use Illuminate\Support\Facades\Route;

it('opens the customer side in the customer’s name with the coral bar', function () {
    $admin = actingAsAdmin();
    $organization = Organization::factory()->create();
    $customer = customerOf($organization);

    $this->post(route('admin.impersonate', $customer))->assertRedirect(route('client.landing'));

    $this->assertAuthenticatedAs($customer, 'web');
    $this->assertAuthenticatedAs($admin, 'admin');
    $this->get(route('client.home', $organization))
        ->assertOk()
        ->assertSee($customer->name.' nevében dolgozol');
    expect(Impersonation::query()->sole())->admin_id->toBe($admin->id)->user_id->toBe($customer->id)
        ->and(AuditEvent::query()->where('type', AuditEventType::ImpersonationStarted)->count())->toBe(1);
});

it('does not touch the customer’s last login', function () {
    actingAsAdmin();
    $customer = customerOf();

    $this->post(route('admin.impersonate', $customer));

    expect($customer->fresh()->last_login_at)->toBeNull();
});

it('refuses to work in the name of an admin account', function () {
    actingAsAdmin();
    $otherAdmin = User::factory()->admin()->create();

    $response = $this->post(route('admin.impersonate', $otherAdmin));

    $response->assertForbidden();
});

it('logs each change made in the customer’s name, but not reads or refusals', function () {
    Route::middleware(['web', 'customer'])->group(function () {
        Route::post('/_test/valtoztatas', fn () => 'ok')->name('test.change');
        Route::get('/_test/olvasas', fn () => 'ok')->name('test.read');
        Route::post('/_test/tiltott', fn () => abort(403))->name('test.refused');
    });
    $admin = actingAsAdmin();
    $customer = customerOf();
    $this->post(route('admin.impersonate', $customer));

    $this->get('/_test/olvasas')->assertOk();
    $this->post('/_test/tiltott')->assertForbidden();
    $this->post('/_test/valtoztatas')->assertOk();

    $action = AuditEvent::query()->where('type', AuditEventType::ImpersonationAction)->sole();
    expect($action->actor_id)->toBe($admin->id)
        ->and($action->subject_user_id)->toBe($customer->id)
        ->and($action->payload)->toBe(['actions' => ['test.change']]);
});

it('forbids accepting legal documents in the customer’s name', function () {
    actingAsAdmin();
    $customer = customerOf();
    LegalDocumentVersion::factory()->ofType(LegalDocumentType::Terms)->requiringReacceptance()->create();
    $this->post(route('admin.impersonate', $customer));

    $this->get(route('client.legal.show'))->assertSee('A felhasználó nevében nem fogadhatod el.');
    $this->post(route('client.legal.store'))->assertForbidden();

    $this->assertDatabaseCount('legal_acceptances', 0);
});

it('forbids changing the password in the customer’s name', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $customer = customerOf($organization);
    $this->post(route('admin.impersonate', $customer));

    Livewire\Livewire::test(Profile::class, ['organization' => $organization])
        ->set('current_password', 'password')
        ->set('password', 'uj-jelszo-1234')
        ->set('password_confirmation', 'uj-jelszo-1234')
        ->call('savePassword')
        ->assertForbidden();
});

it('ends the work with "Kilépés" and returns to the customer datasheet', function () {
    actingAsAdmin();
    $customer = customerOf();
    $this->post(route('admin.impersonate', $customer));

    $response = $this->post(route('client.impersonation.end'));

    $response->assertRedirect(route('admin.clients.show', $customer));
    $this->assertGuest('web');
    expect(Impersonation::query()->sole()->end_reason)->toBe(Impersonation::END_EXIT);
});

it('ends the work when the admin session expires', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $customer = customerOf($organization);
    $this->post(route('admin.impersonate', $customer));
    session()->put(GuardSession::ADMIN_LAST_ACTIVITY_KEY, now()->subHours(7)->getTimestamp());

    $response = $this->get(route('client.home', $organization));

    $response->assertRedirect(route('admin.login'));
    expect(Impersonation::query()->sole()->end_reason)->toBe(Impersonation::END_ADMIN_SESSION_EXPIRED);
});
