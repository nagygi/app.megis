<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

/**
 * Writes every delivered message to the mail log channel, without addresses.
 */
class LogSentMail
{
    public function handle(MessageSent $event): void
    {
        $email = $event->sent->getOriginalMessage();

        Log::channel('mail')->info('Mail sent.', [
            'message_id' => $event->sent->getMessageId(),
            'subject' => method_exists($email, 'getSubject') ? $email->getSubject() : null,
            'recipient_count' => count($event->sent->getEnvelope()->getRecipients()),
        ]);
    }
}
