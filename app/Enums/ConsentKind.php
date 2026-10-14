<?php

namespace App\Enums;

/**
 * The two consents of a peer on T1 (25 · 25.2).
 */
enum ConsentKind: string
{
    // "A válaszaim névvel csak a mégisnél …": required to start.
    case Answers = 'answers';
    // "Az idézeteim szereppel és dátummal, név nélkül …": optional.
    case Quotes = 'quotes';

    public function label(): string
    {
        return __('enums.consent_kind.'.$this->value);
    }
}
