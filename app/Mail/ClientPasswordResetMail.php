<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A customer's password link, valid for 60 minutes (14 · 14.3): asked for on
 * B1, or sent by an admin from the user's datasheet (33 · 33.2).
 */
class ClientPasswordResetMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $token,
        public bool $byAdmin = false,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.client_password_reset.subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.client-password-reset',
            with: [
                'url' => route('client.password.reset', $this->token),
                'body' => __($this->byAdmin ? 'mail.client_password_reset.body_admin' : 'mail.client_password_reset.body'),
            ],
        );
    }
}
