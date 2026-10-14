<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to both the old and the new address when a superadmin changes it (31, 33 · 2. kérdés).
 */
class EmailChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $newEmail,
        public bool $toOldAddress,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.email_changed.subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.email-changed',
            with: ['support' => config('megis.support_email')],
        );
    }
}
