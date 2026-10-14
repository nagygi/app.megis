<?php

use App\Services\Checks\Heartbeat;
use Illuminate\Support\Facades\Storage;

function markServicesAlive(): void
{
    $heartbeat = app(Heartbeat::class);
    $heartbeat->record(Heartbeat::SCHEDULER);
    $heartbeat->record(Heartbeat::QUEUE);
}

beforeEach(function () {
    config(['megis.health.min_free_disk_mb' => 0]);
});

it('returns ok with 200 when every dependency is healthy', function () {
    Storage::fake('materials');
    markServicesAlive();

    $response = $this->get('/health');

    $response->assertOk();
    expect($response->getContent())->toBe('ok');
});

it('returns fail with 503 and no details when the scheduler has not run', function () {
    Storage::fake('materials');

    $response = $this->get('/health');

    $response->assertServiceUnavailable();
    expect($response->getContent())->toBe('fail');
});

it('returns the check details when the health token is sent', function () {
    Storage::fake('materials');
    config(['megis.health.token' => 'secret-health-token']);

    $response = $this->getJson('/health', ['X-Health-Token' => 'secret-health-token']);

    $response->assertServiceUnavailable()
        ->assertJsonPath('status', 'fail')
        ->assertJsonPath('version', config('megis.version'))
        ->assertJsonFragment(['label' => 'Ütemező', 'status' => 'error']);
});

it('hides the details when the health token is wrong', function () {
    Storage::fake('materials');
    markServicesAlive();
    config(['megis.health.token' => 'secret-health-token']);

    $response = $this->get('/health?token=wrong-token');

    $response->assertOk();
    expect($response->getContent())->toBe('ok');
});

it('does not start a session', function () {
    Storage::fake('materials');
    markServicesAlive();

    $response = $this->get('/health');

    $response->assertCookieMissing(config('session.cookie'));
});
