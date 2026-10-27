<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Törlés megtörtént" (31 · E-mailek): sent to the address the account had.
 * When a superadmin archived the account, the text says so (33 · 33.8).
 */
class AccountDeletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public bool $byAdmin = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.account_deleted.subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.account-deleted',
            with: [
                'body' => __($this->byAdmin ? 'mail.account_deleted.body_by_admin' : 'mail.account_deleted.body', ['support' => config('megis.support_email')]),
            ],
        );
    }
}
