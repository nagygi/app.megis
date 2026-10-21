<?php

namespace App\Listeners;

use App\Support\MailAllowlist;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Outside production, mail only reaches allowlisted addresses or domains when
 * MEGIS_MAIL_ALLOWLIST is set (docs/uzemeltetes.md · 2).
 */
class EnforceMailAllowlist
{
    public function __construct(private MailAllowlist $allowlist) {}

    public function handle(MessageSending $event): ?bool
    {
        if (! $this->allowlist->isRestricting()) {
            return null;
        }

        $message = $event->message;
        $recipients = [...$message->getTo(), ...$message->getCc(), ...$message->getBcc()];

        $remainingRecipients = $this->restrict($message, 'To', $message->getTo())
            + $this->restrict($message, 'Cc', $message->getCc())
            + $this->restrict($message, 'Bcc', $message->getBcc());

        if ($remainingRecipients === 0) {
            Log::channel('mail')->info('Mail suppressed: no allowlisted recipient.', [
                'subject' => $message->getSubject(),
                'recipient_domains' => $this->domains($recipients),
            ]);

            return false;
        }

        return null;
    }

    /**
     * Keeps only allowlisted addresses in the given header.
     *
     * @param  list<Address>  $addresses
     * @return int The number of addresses kept.
     */
    private function restrict(Email $message, string $header, array $addresses): int
    {
        $allowedAddresses = array_values(array_filter(
            $addresses,
            fn (Address $address): bool => $this->allowlist->allows($address->getAddress()),
        ));

        $message->getHeaders()->remove($header);

        if ($allowedAddresses !== []) {
            match ($header) {
                'To' => $message->to(...$allowedAddresses),
                'Cc' => $message->cc(...$allowedAddresses),
                'Bcc' => $message->bcc(...$allowedAddresses),
            };
        }

        return count($allowedAddresses);
    }

    /**
     * The log names only the domains, never the addresses.
     *
     * @param  list<Address>  $addresses
     * @return list<string>
     */
    private function domains(array $addresses): array
    {
        return array_values(array_unique(array_map(
            fn (Address $address): string => mb_strtolower(Str::afterLast($address->getAddress(), '@')),
            $addresses,
        )));
    }
}
