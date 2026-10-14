<?php

use App\Mail\AdminLockedMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;

function postAdminLogin(string $email, string $password): TestResponse
{
    return test()->post(route('admin.login.store'), [
        'email' => $email,
        'password' => $password,
        'cf-turnstile-response' => 'turnstile-token',
    ]);
}

it('renders the admin login page without a password reset link', function () {
    $response = $this->get(route('admin.login'));

    $response->assertOk()
        ->assertSee('Admin belépés')
        ->assertSee('Kérd meg egy másik admint, hogy küldjön új beállító linket.');
});

it('logs an active admin in and resets the failure counter', function () {
    fakeTurnstile();
    $admin = User::factory()->admin()->create(['password' => Hash::make('correct-horse-battery'), 'failed_login_attempts' => 3]);

    $response = postAdminLogin($admin->email, 'correct-horse-battery');

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($admin, 'admin');
    expect($admin->fresh())
        ->failed_login_attempts->toBe(0)
        ->last_login_at->not->toBeNull();
});

it('answers a wrong password, a customer and an inactive admin with the same message', function (Closure $makeUser) {
    fakeTurnstile();
    $user = $makeUser();

    $response = postAdminLogin($user->email, 'correct-horse-battery');

    $response->assertSessionHasErrors(['email' => 'Hibás e-mail cím vagy jelszó.']);
    $this->assertGuest('admin');
})->with([
    'wrong password' => fn () => User::factory()->admin()->create(['password' => Hash::make('another-password-1')]),
    'customer account' => fn () => User::factory()->create(['password' => Hash::make('correct-horse-battery')]),
    'inactive admin' => fn () => User::factory()->admin()->deactivated()->create(['password' => Hash::make('correct-horse-battery')]),
]);

it('locks the account on the tenth failure and alerts the superadmins', function () {
    fakeTurnstile();
    Mail::fake();
    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create(['failed_login_attempts' => 9]);

    $response = postAdminLogin($admin->email, 'wrong-password-123');

    $response->assertSessionHasErrors(['email' => 'A fiókod zárolva van. Kérd egy szuperadmin segítségét.']);
    expect($admin->fresh()->locked_at)->not->toBeNull();
    Mail::assertQueued(AdminLockedMail::class, fn (AdminLockedMail $mail) => $mail->hasTo($superadmin->email));
});

it('keeps a locked admin out even with the right password', function () {
    fakeTurnstile();
    $admin = User::factory()->admin()->locked()->create(['password' => Hash::make('correct-horse-battery')]);

    $response = postAdminLogin($admin->email, 'correct-horse-battery');

    $response->assertSessionHasErrors(['email' => 'A fiókod zárolva van. Kérd egy szuperadmin segítségét.']);
    $this->assertGuest('admin');
});

it('allows five attempts per minute for an address and IP', function () {
    fakeTurnstile();
    $admin = User::factory()->admin()->create();

    foreach (range(1, 5) as $attempt) {
        postAdminLogin($admin->email, 'wrong-password-123');
    }
    $response = postAdminLogin($admin->email, 'wrong-password-123');

    $response->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toStartWith('Túl sok próbálkozás.');
});

it('refuses the login when Turnstile rejects the visitor', function () {
    fakeTurnstile(success: false);
    $admin = User::factory()->admin()->create(['password' => Hash::make('correct-horse-battery')]);

    $response = postAdminLogin($admin->email, 'correct-horse-battery');

    $response->assertSessionHasErrors('cf-turnstile-response');
    $this->assertGuest('admin');
});

it('logs the admin out', function () {
    actingAsAdmin();

    $response = $this->post(route('admin.logout'));

    $response->assertRedirect(route('admin.login'));
    $this->assertGuest('admin');
});
