<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Outside production, mail only reaches allowlisted addresses or domains, so
 * staging never writes to real customers (docs/uzemeltetes.md · 2).
 */
class EnforceMailAllowlist
{
    public function handle(MessageSending $event): ?bool
    {
        $allowlist = config('megis.mail_allowlist');

        if (app()->isProduction() || $allowlist === []) {
            return null;
        }

        $message = $event->message;

        $remainingRecipients = $this->restrict($message, 'To', $message->getTo(), $allowlist)
            + $this->restrict($message, 'Cc', $message->getCc(), $allowlist)
            + $this->restrict($message, 'Bcc', $message->getBcc(), $allowlist);

        if ($remainingRecipients === 0) {
            Log::channel('mail')->info('Mail suppressed: no allowlisted recipient.', [
                'subject' => $message->getSubject(),
            ]);

            return false;
        }

        return null;
    }

    /**
     * Keeps only allowlisted addresses in the given header.
     *
     * @param  list<Address>  $addresses
     * @param  list<string>  $allowlist
     * @return int The number of addresses kept.
     */
    private function restrict(Email $message, string $header, array $addresses, array $allowlist): int
    {
        $allowedAddresses = array_values(array_filter(
            $addresses,
            fn (Address $address): bool => $this->isAllowed($address->getAddress(), $allowlist),
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
     * @param  list<string>  $allowlist  Full addresses or domains written as "@example.com".
     */
    private function isAllowed(string $address, array $allowlist): bool
    {
        $address = mb_strtolower($address);

        foreach ($allowlist as $entry) {
            $entry = mb_strtolower($entry);

            if ($address === $entry || (str_starts_with($entry, '@') && str_ends_with($address, $entry))) {
                return true;
            }
        }

        return false;
    }
}
