<?php

namespace App\Services\Capabilities\Measurement;

use App\Enums\TraceDirection;
use App\Enums\TraceDirectness;

/**
 * A trace as the state calculation sees it (6.1, KO-02): who said it, by
 * which method, on which occasion, how directly, and its marks.
 */
final readonly class TraceInput
{
    /**
     * @param  list<string>  $flags
     */
    public function __construct(
        public int $id,
        public string $personKey,
        public string $method,
        public string $occasionKey,
        public bool $isRaw,
        public TraceDirectness $directness,
        public TraceDirection $direction,
        public array $flags = [],
        public bool $externalVoice = false,
    ) {}
}
