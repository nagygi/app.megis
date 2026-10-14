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
 * Invitation to the admin interface with a 24-hour password link (01 · 1.3).
 */
class AdminInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?User $inviter,
        public string $token,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.admin_invitation.subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.admin-invitation',
            with: ['url' => route('admin.password.setup', $this->token)],
        );
    }
}
