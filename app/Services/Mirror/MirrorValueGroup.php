<?php

namespace App\Services\Mirror;

/**
 * One value on ET3: a matched Értéktár value or a free name, with its quotes,
 * no number and no rating (06 · 6.1).
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
    ) {}
}
