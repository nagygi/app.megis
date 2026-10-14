<?php

namespace App\Mail;

use Carbon\CarbonImmutable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sent synchronously to the alert list: the queue itself may be what failed.
 * Carries no personal data (docs/uzemeltetes.md · 5).
 */
class ErrorAlert extends Mailable
{
    public function __construct(
        public string $environment,
        public string $errorClass,
        public string $errorMessage,
        public ?string $url,
        public ?string $jobName,
        public CarbonImmutable $occurredAt,
        public ?string $logId,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.error_alert.subject', [
                'environment' => $this->environment,
                'error' => $this->errorClass,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.error-alert',
            with: [
                'throttleMinutes' => config('megis.alerts.throttle_minutes'),
            ],
        );
    }
}
