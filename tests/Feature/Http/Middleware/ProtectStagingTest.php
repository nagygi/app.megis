<?php

use App\Http\Middleware\ProtectStaging;
use App\Models\User;
use Exot\OnsiteComment\Auth\ParticipantSessions;
use Exot\OnsiteComment\Models\ParticipantSession;
use Illuminate\Routing\Router;
use Illuminate\Session\Middleware\StartSession;

beforeEach(function () {
    $this->app['env'] = 'staging';
    config([
        'megis.staging.basic_auth_user' => 'megis',
        'megis.staging.basic_auth_password' => 'staging-secret',
        'megis.staging.allowed_ips' => [],
    ]);
});

it('asks for credentials on staging', function () {
    $response = $this->get('/');

    $response->assertUnauthorized()->assertHeader('WWW-Authenticate', 'Basic realm="megis staging"');
});

it('rejects wrong credentials on staging', function () {
    $response = $this->withBasicAuth('megis', 'wrong')->get('/');

    $response->assertUnauthorized();
});

it('lets the correct credentials through on staging', function () {
    $response = $this->withBasicAuth('megis', 'staging-secret')->get('/');

    $response->assertOk();
});

it('lets an allowlisted address through without credentials', function () {
    config(['megis.staging.allowed_ips' => ['10.0.0.0/24']]);

    $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.7'])->get('/');

    $response->assertOk();
});

it('forbids other addresses when only an IP allowlist is configured', function () {
    config([
        'megis.staging.allowed_ips' => ['10.0.0.0/24'],
        'megis.staging.basic_auth_user' => null,
        'megis.staging.basic_auth_password' => null,
    ]);

    $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.5'])->get('/');

    $response->assertForbidden();
});

it('lets an on-page ticket participant through without credentials', function () {
    $this->mock(ParticipantSessions::class)->shouldReceive('resolve')->andReturn(new ParticipantSession);

    $response = $this->get('/');

    $response->assertOk();
});

it('ignores a ticket session while the on-page tickets are switched off', function () {
    config(['onsite-comment.enabled' => false]);
    $this->mock(ParticipantSessions::class)->shouldReceive('resolve')->andReturn(new ParticipantSession);

    $response = $this->get('/');

    $response->assertUnauthorized();
});

it('tells a visitor without a password prompt where to log in', function () {
    $response = $this->get('/');

    $response->assertUnauthorized()
        ->assertSee(__('errors.401.title'))
        ->assertSee(route('client.landing'), false);
});

it('opens the mail links and the login pages without credentials', function (string $path) {
    $response = $this->get($path);

    expect($response->status())->not->toBe(401);
})->with([
    'invitation' => '/meghivo/no-such-token',
    'login' => '/belepes',
    'password reset' => '/jelszo-visszaallitas/no-such-token',
    'admin password setup' => '/admin/jelszo-beallitas/no-such-token',
    'admin login' => '/admin/belepes',
    'client user invitation' => '/ugyfelmeres/meghivo/no-such-token',
]);

it('sends a guest from a page behind the login to the login page', function () {
    $response = $this->get(route('client.landing'));

    $response->assertRedirect(route('client.login'));
});

it('lets a signed-in user through on every page', function (string $guard) {
    $response = $this->actingAs(User::factory()->create(), $guard)->get('/');

    $response->assertOk();
})->with(['web', 'admin']);

it('runs after the session starts, so it sees who is signed in', function () {
    $router = app(Router::class);
    $middleware = $router->gatherRouteMiddleware($router->getRoutes()->getByName('public.home'));

    expect(array_search(ProtectStaging::class, $middleware, true))
        ->toBeGreaterThan(array_search(StartSession::class, $middleware, true));
});

it('keeps the health check reachable for the monitor', function () {
    $response = $this->get('/health');

    expect($response->status())->not->toBe(401);
});

it('does nothing outside staging', function () {
    $this->app['env'] = 'production';

    $response = $this->get('/');

    $response->assertOk();
});
