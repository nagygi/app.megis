<?php

namespace App\Services\Mirror;

/**
 * One value on ET3: a matched Értéktár value or a free name, with its quotes,
 * no number and no rating (06 · 6.1). The name is the user's own when they
 * renamed it on ET4; then originalName is the detected one. "approved": the
 * user looked it through and approved it; "edited": they wrote some of it.
 */
final readonly class MirrorValueGroup
{
    /**
     * @param  list<string>  $quotes
     * @param  list<int>  $detectionIds
     */
    public function __construct(
        public string $key,
        public string $name,
        public array $quotes,
        public array $detectionIds,
        public bool $excluded,
        public ?int $valueId,
        public ?string $originalName = null,
        public bool $approved = false,
        public bool $edited = false,
    ) {}

    public function isRenamed(): bool
    {
        return $this->originalName !== null;
    }
}
