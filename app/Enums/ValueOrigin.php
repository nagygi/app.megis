<?php

namespace App\Enums;

/**
 * Where an Értéktár value came from (03 · 3.7). Only values from outside the
 * admin form carry a label and wait for review.
 */
enum ValueOrigin: string
{
    case Admin = 'admin';
    case Diagnosis = 'diagnosis';
    case Ai = 'ai';
    case Organization = 'organization';

    public function label(): string
    {
        return __('enums.value_origin.'.$this->value);
    }

    public function needsReview(): bool
    {
        return $this !== self::Admin;
    }
}
