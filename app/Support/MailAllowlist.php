<?php

namespace App\Support;

/**
 * Outside production, a non-empty MEGIS_MAIL_ALLOWLIST limits outgoing mail
 * to its addresses and domains (docs/uzemeltetes.md · 2). An empty list
 * does not filter.
 */
class MailAllowlist
{
    public function isRestricting(): bool
    {
        return ! app()->isProduction() && $this->entries() !== [];
    }

    /**
     * Full addresses match exactly, domains written as "@example.com" by their ending, both case-insensitively.
     */
    public function allows(string $address): bool
    {
        if (! $this->isRestricting()) {
            return true;
        }

        $address = mb_strtolower($address);

        foreach ($this->entries() as $entry) {
            $entry = mb_strtolower($entry);

            if ($address === $entry || (str_starts_with($entry, '@') && str_ends_with($address, $entry))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function entries(): array
    {
        return config('megis.mail_allowlist');
    }
}
