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
 * New password link after another admin reset the password (01 · 1.3).
 */
class AdminPasswordResetMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public User $admin,
        public string $token,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.admin_password_reset.subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.admin-password-reset',
            with: ['url' => route('admin.password.setup', $this->token)],
        );
    }
}
