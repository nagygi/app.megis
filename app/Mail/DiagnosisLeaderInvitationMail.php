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
 * The leader's invitation, the first gate of a main diagnosis (17 · 17.2).
 * A new account gets the registration link, an existing one the V1 link;
 * also sent to the new leader after a change (17 · 17.7).
 */
class DiagnosisLeaderInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public MainDiagnosis $main,
        public User $leader,
        public User $inviter,
        public ?string $token,
        public int $minutes,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.diagnosis_leader.subject', ['subject' => $this->main->subjectName()]));
    }

    public function content(): Content
    {
        $diagnosis = $this->main->diagnosis;
        $replace = [
            'admin' => $this->inviter->name,
            'subject' => $this->main->subjectName(),
            'minutes' => $this->minutes,
            'date' => $this->main->starts_on?->format('m.d.'),
        ];

        return new Content(
            markdown: 'mail.diagnosis-leader-invitation',
            with: [
                'intro' => $this->token === null
                    ? __('mail.diagnosis_leader.body_existing', $replace)
                    : __('mail.diagnosis_leader.body_new', $replace),
                'timing' => $this->main->starts_on === null
                    ? __('mail.diagnosis_leader.timing_open', $replace)
                    : __('mail.diagnosis_leader.timing', $replace),
                'url' => $this->token === null
                    ? route('client.diagnosis.show', [$diagnosis->organization, $diagnosis])
                    : route('client.invitation.show', $this->token),
            ],
        );
    }
}
