<?php

namespace App\Mail;

use App\Models\MainDiagnosis;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A peer's invitation when the diagnosis starts running (25 · 25.1): the
 * registration link for a new account, the T1 link for an existing one.
 */
class DiagnosisPeerInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public MainDiagnosis $main,
        public User $peer,
        public ?string $token,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.diagnosis_peer.subject', ['subject' => $this->main->subjectName()]));
    }

    public function content(): Content
    {
        $diagnosis = $this->main->diagnosis;

        return new Content(
            markdown: 'mail.diagnosis-peer-invitation',
            with: [
                'intro' => __('mail.diagnosis_peer.body', [
                    'leader' => $this->main->leader->name,
                    'subject' => $this->main->subjectName(),
                    'minutes' => (int) config('diagnosis.peer_minutes'),
                ]),
                'url' => $this->token === null
                    ? route('client.diagnosis.show', [$diagnosis->organization, $diagnosis])
                    : route('client.invitation.show', $this->token),
                'isNew' => $this->token !== null,
            ],
        );
    }
}
