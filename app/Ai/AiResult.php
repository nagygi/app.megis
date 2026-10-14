<?php

namespace App\Ai;

use App\Models\AiCall;

/**
 * The answer of a task with the log row it came from; the feature stores the
 * answer and keeps the call's id next to it (16 · 16.3).
 */
final readonly class AiResult
{
    /**
     * @param  array<string, mixed>|null  $structured
     */
    public function __construct(
        public string $text,
        public ?array $structured,
        public AiCall $call,
    ) {}
}
