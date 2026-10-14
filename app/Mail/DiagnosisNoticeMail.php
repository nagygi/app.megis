<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The notices of a running diagnosis from the Futás tab (19 · E-mailek):
 * the reminders to the leader and the peers, the threshold to the leader
 * (edited before sending) and the changed time window. One text, one button.
 */
class DiagnosisNoticeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $title,
        public string $body,
        public string $action,
        public string $url,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->title);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.diagnosis-notice',
            with: ['body' => $this->body, 'action' => $this->action, 'url' => $this->url],
        );
    }
}
