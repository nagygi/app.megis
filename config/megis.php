<?php

use App\Services\Checks;

return [

    /*
    |--------------------------------------------------------------------------
    | Application version
    |--------------------------------------------------------------------------
    |
    | The single place where the version is set (NFR · 2). It appears at the
    | bottom of the admin menu. Raise it on every release; the major version
    | is only ever raised by hand.
    |
    */

    'version' => '0.30',

    // Set by the deploy script, shown on Rendszer → Health (docs/uzemeltetes.md · 3).
    'deployed_at' => env('MEGIS_DEPLOYED_AT'),

    /*
    |--------------------------------------------------------------------------
    | Sessions
    |--------------------------------------------------------------------------
    |
    | Customers stay logged in for 30 days, renewed on every use (14 · 14.2,
    | SESSION_LIFETIME). Admin sessions also end after 6 idle hours (01 · 1.2).
    |
    */

    'admin_idle_timeout_minutes' => 360,

    /*
    |--------------------------------------------------------------------------
    | Contact points
    |--------------------------------------------------------------------------
    */

    'support_email' => env('MEGIS_SUPPORT_EMAIL'),

    // External booking calendar used by "Időpontot foglalok" and "Beszéljünk róla" (06, 26, 27).
    'booking_url' => env('MEGIS_BOOKING_URL'),

    /*
    |--------------------------------------------------------------------------
    | Error alerts (docs/uzemeltetes.md · 5)
    |--------------------------------------------------------------------------
    */

    'alerts' => [
        'recipients' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('MEGIS_ALERT_RECIPIENTS', '')),
        ))),
        // The same error is sent at most once within this window.
        'throttle_minutes' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Health check (docs/uzemeltetes.md · 3)
    |--------------------------------------------------------------------------
    */

    'health' => [
        // Details are only returned when this token is sent; empty disables details.
        'token' => env('MEGIS_HEALTH_TOKEN'),
        'max_heartbeat_age_seconds' => 300,
        'min_free_disk_mb' => (int) env('MEGIS_HEALTH_MIN_FREE_DISK_MB', 1024),
        'checks' => [
            Checks\DatabaseCheck::class,
            Checks\RedisCheck::class,
            Checks\MaterialsStorageCheck::class,
            Checks\QueueWorkerCheck::class,
            Checks\SchedulerCheck::class,
            Checks\DiskSpaceCheck::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Setup check (php artisan megis:setup:check, docs/uzemeltetes.md · 7)
    |--------------------------------------------------------------------------
    |
    | Every ticket that adds an external dependency registers its own check here.
    |
    */

    'setup_checks' => [
        Checks\RequiredEnvironmentCheck::class,
        Checks\DatabaseCheck::class,
        Checks\RedisCheck::class,
        Checks\MaterialsStorageCheck::class,
        Checks\MailConfigurationCheck::class,
        Checks\TurnstileConfigurationCheck::class,
        Checks\QueueWorkerCheck::class,
        Checks\SchedulerCheck::class,
        Checks\LegalDocumentsCheck::class,
        Checks\AiConfigurationCheck::class,
        Checks\EnvironmentPolicyCheck::class,
    ],

    // Configuration values that must be set, keyed by config key, with the
    // environment variable that provides them (for the error message).
    'required_config' => [
        'app.key' => 'APP_KEY',
        'app.url' => 'APP_URL',
        'mail.from.address' => 'MAIL_FROM_ADDRESS',
        'megis.support_email' => 'MEGIS_SUPPORT_EMAIL',
        'megis.alerts.recipients' => 'MEGIS_ALERT_RECIPIENTS',
    ],

    /*
    |--------------------------------------------------------------------------
    | Staging (docs/uzemeltetes.md · 2)
    |--------------------------------------------------------------------------
    */

    'staging' => [
        'basic_auth_user' => env('MEGIS_STAGING_USER'),
        'basic_auth_password' => env('MEGIS_STAGING_PASSWORD'),
        'allowed_ips' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('MEGIS_STAGING_ALLOWED_IPS', '')),
        ))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Outgoing mail outside production
    |--------------------------------------------------------------------------
    |
    | Outside production, mail is only delivered to these addresses or domains
    | (e.g. "@megis.hu"). An empty list delivers nothing but the local catcher
    | (Mailpit, log), which is configured through MAIL_MAILER.
    |
    */

    'mail_allowlist' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('MEGIS_MAIL_ALLOWLIST', '')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Public pages
    |--------------------------------------------------------------------------
    */

    // Named routes listed in sitemap.xml. Only public pages belong here (NFR · 6).
    'sitemap_routes' => [
        'public.home',
    ],

    // First URL segments that can never become an organization slug, on top
    // of every first segment the router already uses (02 · 2.3, 15.1).
    'reserved_slugs' => [
        // Legal documents use a constrained route parameter, so they are listed here (05 · 5.2).
        'adatkezelesi-tajekoztato',
        'aszf',
        'cookie-tajekoztato',
        'api',
        'login',
        'logout',
        'register',
        'build',
        'storage',
        'fonts',
        'images',
        'favicon.ico',
        'manifest.webmanifest',
        'offline',
    ],

];
