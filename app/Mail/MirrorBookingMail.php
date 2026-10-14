<?php

namespace App\Mail;

use App\Models\InboxItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Időpontot foglalok" is time-sensitive: the only Beérkező row that is
 * mailed at once (22, question 2).
 */
class MirrorBookingMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public InboxItem $item)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.mirror_booking.subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.mirror-booking',
            with: ['summary' => $this->item->summary, 'url' => route('admin.inbox.index')],
        );
    }
}
