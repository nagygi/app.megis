<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirmation link of the account started on ET5; it lives as long as the
 * anonymous mirror, 24 hours (06 · 6.1).
 */
class MirrorConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $token)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.mirror_confirmation.subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.mirror-confirmation',
            with: ['url' => route('mirror.confirmation.show', $this->token)],
        );
    }
}
