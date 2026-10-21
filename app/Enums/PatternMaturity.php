<?php

namespace App\Enums;

/**
 * How well tested a pattern is (6.6). It only changes the wording, e.g. "the
 * literature says this is common; with you we saw it"; the force of a
 * statement comes from the confidence of the finding.
 */
enum PatternMaturity: string
{
    case HypothesisLiterature = 'hypothesis_literature';
    case HypothesisOwn = 'hypothesis_own';
    case SeenOnce = 'seen_once';
    case Used = 'used';
    case Obsolete = 'obsolete';
    case Rejected = 'rejected';

    /**
     * The formula of the "minta-érettség" column of the mégis Sheet: obsolete
     * or rejected first, then by the clients with an approved finding, then
     * by where the pattern comes from.
     */
    public static function compute(PatternValidity $validity, int $approvedClients, ?PatternOrigin $origin): self
    {
        return match (true) {
            $validity === PatternValidity::Rejected => self::Rejected,
            $validity === PatternValidity::Obsolete => self::Obsolete,
            $approvedClients >= 2 => self::Used,
            $approvedClients === 1 => self::SeenOnce,
            in_array($origin, [PatternOrigin::External, PatternOrigin::Both], true) => self::HypothesisLiterature,
            default => self::HypothesisOwn,
        };
    }

    /**
     * A hypothesis pattern's first finding needs a person's approval (NY-26).
     */
    public function isHypothesis(): bool
    {
        return in_array($this, [self::HypothesisLiterature, self::HypothesisOwn], true);
    }

    public function label(): string
    {
        return __('enums.pattern_maturity.'.$this->value);
    }
}
