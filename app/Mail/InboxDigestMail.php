<?php

namespace App\Mail;

use App\Models\InboxItem;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * The 8:00 summary for a contact (22, question 2): the open rows of their
 * organizations, one line each, with a link to the Beérkező.
 */
class InboxDigestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, InboxItem>  $items
     */
    public function __construct(
        public User $admin,
        public Collection $items,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.inbox_digest.subject', ['count' => $this->items->count()]));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.inbox-digest',
            with: [
                'lines' => $this->items->map(fn (InboxItem $item) => collect([
                    $item->type->label(),
                    $item->organization?->name,
                    $item->summary,
                ])->filter()->implode(' · '))->all(),
                'url' => route('admin.inbox.index'),
            ],
        );
    }
}
