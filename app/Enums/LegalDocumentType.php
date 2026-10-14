<?php

namespace App\Enums;

/**
 * The three legal documents (05). The value is also the permanent public URL.
 */
enum LegalDocumentType: string
{
    case PrivacyPolicy = 'adatkezelesi-tajekoztato';
    case Terms = 'aszf';
    case CookiePolicy = 'cookie-tajekoztato';

    public function label(): string
    {
        return __('enums.legal_document_type.'.$this->translationKey());
    }

    /**
     * The cookie policy never has to be accepted (14 · 14.5).
     */
    public function requiresAcceptance(): bool
    {
        return $this !== self::CookiePolicy;
    }

    public function translationKey(): string
    {
        return match ($this) {
            self::PrivacyPolicy => 'privacy_policy',
            self::Terms => 'terms',
            self::CookiePolicy => 'cookie_policy',
        };
    }
}
