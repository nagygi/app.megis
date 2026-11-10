<?php

namespace App\Mail;

use App\Models\ExternalClient;
use App\Models\MainDiagnosis;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A client user's invitation to the Ügyfélmérés when the diagnosis starts
 * running (35 · 35.5): a personal link, no password needed.
 */
class ExternalClientInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public MainDiagnosis $main,
        public ExternalClient $client,
        public User $user,
        public string $token,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.external_client.subject', ['organization' => $this->client->organization->name]));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.external-client-invitation',
            with: [
                'intro' => __('mail.external_client.body', [
                    'organization' => $this->client->organization->name,
                    'date' => $this->main->readingStartsOn()->copy()->subDay()->format('Y.m.d.'),
                ]),
                'url' => route('external.invitation.show', $this->token),
            ],
        );
    }
}
