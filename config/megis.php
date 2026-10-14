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

    'version' => '0.60',

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

    // The product of the private-person organization created from the Értéktükör
    // (06 · 6.1); the real product is chosen at the conversation.
    'individual_product' => env('MEGIS_INDIVIDUAL_PRODUCT', 'Snapshot · Belső'),

    /*
    |--------------------------------------------------------------------------
    | Materials and the value measurement (06)
    |--------------------------------------------------------------------------
    */

    'materials' => [
        // Shown on every upload form (NFR · 3). ".doc" is not read: it needs
        // LibreOffice, so the form asks for docx (plan, open question 7).
        'audio_extensions' => ['mp3', 'm4a', 'wav', 'webm'],
        'text_extensions' => ['txt', 'md', 'docx', 'pdf'],
        'audio_max_kilobytes' => 300 * 1024,
        'audio_max_minutes' => 90,
        'text_max_kilobytes' => 10 * 1024,
        // The browser recorder (06 · 6.1, ET2).
        'recording_max_seconds' => 600,
        'recording_warning_seconds' => 480,
        'recording_recommended_seconds' => 60,
        'silence_seconds' => 5,
        'text_max_characters' => 20000,
        // A spoken field instead of a typed one (08 · 8.1, 10 · 10.1, 24).
        'dictation_max_seconds' => 180,
    ],

    // "ai": the provider of config('ai.tasks.transcription'); "fake": a fixed
    // sample transcript, for local work without an API key.
    'transcriber' => env('MEGIS_TRANSCRIBER', 'ai'),

    // Measures audio length; MediaRecorder webm files carry none (plan 3.3).
    'ffprobe_path' => env('FFPROBE_PATH', 'ffprobe'),

    'engine' => [
        // A match counts from this confidence on (06 · 6.4, step B).
        'match_threshold' => 0.5,
        // Long transcripts are read in parts by step A.
        'chunk_characters' => 12000,
        // Detections sent to step B in one call.
        'matching_batch' => 60,
    ],

    // An uploaded ICS export (09 · 9.2): the format and the size are shown on the form.
    'calendar' => [
        'ics_max_kilobytes' => 5 * 1024,
    ],

    'mirror' => [
        // An unclaimed mirror is deleted after this; only the anonymous statistics stay (06 · 6.1).
        'anonymous_hours' => 24,
        // Public submissions per IP address and hour.
        'submissions_per_hour' => 5,
    ],

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
        Checks\TranscriptionCheck::class,
        Checks\CalendarConfigurationCheck::class,
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
        'mirror.record',
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
