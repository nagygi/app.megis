<?php

namespace App\Enums;

/**
 * Result of step C for a matched value: 1–6, or "nincs adat" (06 · 6.4).
 */
enum ScoreResultState: string
{
    case Scored = 'scored';
    case NoData = 'no_data';
}
