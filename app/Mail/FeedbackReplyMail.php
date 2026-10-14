<?php

namespace App\Mail;

use App\Models\ReportFeedback;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Válaszolok": the mégis answers a feedback by e-mail, by name; the answer
 * does not appear in the report (22, question 1).
 */
class FeedbackReplyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ReportFeedback $feedback,
        public string $reply,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.feedback_reply.subject', ['chapter' => __('report.chapters.'.$this->feedback->chapter)]));
    }

    public function content(): Content
    {
        $diagnosis = $this->feedback->mainDiagnosis->diagnosis;

        return new Content(
            markdown: 'mail.feedback-reply',
            with: [
                'greeting' => __('mail.feedback_reply.greeting', ['name' => $this->feedback->user?->first_name ?? $this->feedback->user?->name ?? '']),
                'quote' => $this->feedback->text,
                'reply' => $this->reply,
                'url' => route('client.report.show', [$diagnosis->organization, $diagnosis]),
            ],
        );
    }
}
