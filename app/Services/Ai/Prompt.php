<?php

namespace App\Services\Ai;

/**
 * One version of a task's prompt, as stored in the repository (16 · 16.2).
 */
final readonly class Prompt
{
    public function __construct(
        public string $name,
        public int $version,
        public string $content,
    ) {}

    /**
     * Fills the {{ placeholders }} of the prompt.
     *
     * @param  array<string, string>  $variables
     */
    public function render(array $variables): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-z0-9_]+)\s*\}\}/i',
            fn (array $match): string => $variables[$match[1]] ?? $match[0],
            $this->content,
        );
    }
}
