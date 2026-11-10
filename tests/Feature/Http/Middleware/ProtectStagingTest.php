<?php

use Exot\OnsiteComment\Auth\ParticipantSessions;
use Exot\OnsiteComment\Models\ParticipantSession;

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

it('keeps the health check reachable for the monitor', function () {
    $response = $this->get('/health');

    expect($response->status())->not->toBe(401);
});

it('does nothing outside staging', function () {
    $this->app['env'] = 'production';

    $response = $this->get('/');

    $response->assertOk();
});
