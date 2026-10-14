<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

/**
 * The single agent behind every task: the prompt, token limit and
 * temperature come from the task's configuration (16 · 16.1, 16.2).
 */
class TaskAgent implements Agent
{
    use Promptable;

    public function __construct(
        protected string $prompt,
        protected ?int $maxTokens = null,
        protected ?float $temperature = null,
    ) {}

    public function instructions(): string
    {
        return $this->prompt;
    }

    public function maxTokens(): ?int
    {
        return $this->maxTokens;
    }

    public function temperature(): ?float
    {
        return $this->temperature;
    }
}
