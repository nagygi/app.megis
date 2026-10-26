<?php

namespace App\Mail;

use App\Models\MirrorSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Elküldöm" on ET5: what the user is curious about, to megis.interest_email
 * (06 · 6.1). A reply goes to the user.
 */
class MirrorInterestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  list<string>  $curiosity  the ticked questions when sent
     * @param  list<string>  $values  the kept values' names
     */
    public function __construct(public MirrorSession $session, public array $curiosity, public array $values)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        $email = $this->email();

        return new Envelope(
            replyTo: $email === null ? [] : [new Address($email, $this->session->user?->name)],
            subject: __('mail.mirror_interest.subject', ['name' => $this->name()]),
        );
    }

    public function content(): Content
    {
        $user = $this->session->user;

        return new Content(
            markdown: 'mail.mirror-interest',
            with: [
                'name' => $this->name(),
                'email' => $this->email(),
                'questions' => array_map(fn (string $key) => __('mirror.curiosity.'.$key.'.question'), $this->curiosity),
                'values' => $this->values,
                'url' => $user === null ? null : route('admin.clients.show', $user),
            ],
        );
    }

    private function email(): ?string
    {
        return $this->session->user?->email ?? $this->session->email;
    }

    private function name(): string
    {
        return $this->session->user?->name ?: $this->email() ?? __('mirror.someone');
    }
}
