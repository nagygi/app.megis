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
 * A 6-digit code and a link, both valid for 15 minutes (14 · 14.2).
 */
class LoginCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $token,
        public string $code,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.login_code.subject', ['code' => $this->code]));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.login-code',
            with: ['url' => route('client.login.link', $this->token)],
        );
    }
}
