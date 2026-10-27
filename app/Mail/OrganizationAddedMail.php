<?php

namespace App\Mail;

use App\Http\Controllers\Client\CustomerLoginController;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notice for an existing account that was added to another organization (02 · 2.5).
 */
class OrganizationAddedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Organization $organization,
        public ?User $inviter = null,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.organization_added.subject', ['organization' => $this->organization->name]));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.organization-added',
            // B1 then says who invited them and where (14 · 14.2).
            with: ['url' => $this->inviter !== null ? CustomerLoginController::invitedUrl($this->organization, $this->inviter) : route('client.login')],
        );
    }
}
