<?php

namespace App\Mail;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notice after a password change (01, 14 · 14.3). Admins are pointed to a
 * superadmin, customers to the support address.
 */
class PasswordChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public CarbonImmutable $changedAt,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.password_changed.subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.password-changed',
            with: [
                'time' => $this->changedAt->setTimezone(config('app.timezone'))->format('Y.m.d. H:i'),
                'note' => $this->user->isAdmin()
                    ? __('mail.password_changed.admin_note')
                    : __('mail.password_changed.customer_note', ['email' => config('megis.support_email')]),
            ],
        );
    }
}
