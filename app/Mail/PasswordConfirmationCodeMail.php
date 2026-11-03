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
 * Code that confirms setting a password without knowing the old one
 * (14 · 14.3), or deleting the account (31 · 31.7).
 */
class PasswordConfirmationCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public const FOR_PASSWORD = 'password';

    public const FOR_DELETION = 'deletion';

    public function __construct(
        public User $user,
        public string $code,
        public string $purpose = self::FOR_PASSWORD,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.password_confirmation_code.subject', ['code' => $this->code]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.password-confirmation-code', with: ['body' => __('mail.password_confirmation_code.body_'.$this->purpose)]);
    }
}
