<?php

namespace App\Services\Capabilities\Measurement;

use App\Enums\CapabilityLevel;

/**
 * The subject of a state (6.2): its level, and what the confidence rules
 * need to know about the capability and the organization (KO-05 – KO-07).
 */
final readonly class SubjectContext
{
    public function __construct(
        public CapabilityLevel $level,
        public bool $tempting = false,
        public ?int $organizationSize = null,
        public bool $sourcesExisted = true,
        // RC-67, RC-69–72: an audit or ESG report alone is no presence signal (6.1).
        public bool $auditSensitive = false,
    ) {}

    /**
     * 1–3 people: the team and the organization level coincide (KO-06).
     */
    public function isMicro(): bool
    {
        return $this->organizationSize !== null && $this->organizationSize <= 3;
    }
}
