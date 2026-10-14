<?php

use App\Enums\LegalDocumentType;
use App\Models\LegalDocumentVersion;
use App\Services\Checks\Heartbeat;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config([
        'megis.support_email' => 'support@megis.hu',
        'megis.alerts.recipients' => ['dev@megis.hu'],
        'mail.from.address' => 'noreply@megis.hu',
        'services.turnstile.site_key' => 'site-key',
        'services.turnstile.secret_key' => 'secret-key',
        'megis.health.min_free_disk_mb' => 0,
        // The AI tasks with a prompt have a key and a price; audio is handled locally.
        'ai.providers.anthropic.key' => 'test-key',
        'ai.prices' => ['claude-sonnet-5-5' => ['input' => 3.0, 'output' => 15.0]],
        'megis.transcriber' => 'fake',
    ]);
    Process::fake();

    foreach (LegalDocumentType::cases() as $type) {
        LegalDocumentVersion::factory()->ofType($type)->create();
    }
});

it('succeeds when every check passes', function () {
    Storage::fake('materials');
    app(Heartbeat::class)->record(Heartbeat::SCHEDULER);

    $this->artisan('megis:setup:check')
        ->expectsOutputToContain('Minden ellenőrzés rendben.')
        ->assertSuccessful();
});

it('fails and names the missing setting when a required value is empty', function () {
    Storage::fake('materials');
    app(Heartbeat::class)->record(Heartbeat::SCHEDULER);
    config(['megis.support_email' => null]);

    $this->artisan('megis:setup:check')
        ->expectsOutputToContain('Hiányzik: MEGIS_SUPPORT_EMAIL')
        ->expectsOutputToContain('1 hiba. A környezet nincs rendben.')
        ->assertFailed();
});

it('succeeds with a warning summary when only warnings are found', function () {
    Storage::fake('materials');
    app(Heartbeat::class)->record(Heartbeat::SCHEDULER);
    $this->app['env'] = 'staging';
    config(['megis.staging.allowed_ips' => ['10.0.0.1'], 'megis.mail_allowlist' => []]);

    $this->artisan('megis:setup:check')
        ->expectsOutputToContain('1 figyelmeztetés, hiba nincs.')
        ->assertSuccessful();
});
