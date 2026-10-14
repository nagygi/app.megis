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
 */
class AccountDeletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.account_deleted.subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.account-deleted',
            with: ['support' => config('megis.support_email')],
        );
    }
}
