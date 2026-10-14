<?php

namespace App\Mail;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Registration link for a new customer, valid for 14 days (02 · 2.5, 14 · 14.1).
 */
class ClientInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Organization $organization,
        public User $inviter,
        public string $token,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.client_invitation.subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.client-invitation',
            with: ['url' => route('client.invitation.show', $this->token)],
        );
    }
}
