<?php

namespace App\Services\Ai;

use RuntimeException;

/**
 * Versioned prompts in the code base: {prompts_path}/{prompt}/v{n}.md
 * (16 · 16.2). A new version is always a new file; the active one is set
 * in config/ai.php.
 */
class PromptRepository
{
    /**
     * @return list<int> ascending
     */
    public function versions(string $prompt): array
    {
        $files = glob($this->directory($prompt).DIRECTORY_SEPARATOR.'v*.md') ?: [];

        $versions = array_map(fn (string $file): int => (int) substr(pathinfo($file, PATHINFO_FILENAME), 1), $files);
        sort($versions);

        return array_values(array_filter($versions, fn (int $version): bool => $version > 0));
    }

    public function find(string $prompt, int $version): ?Prompt
    {
        $path = $this->directory($prompt).DIRECTORY_SEPARATOR.'v'.$version.'.md';

        return is_file($path) ? new Prompt($prompt, $version, (string) file_get_contents($path)) : null;
    }

    /**
     * The active prompt of a task, or an error when the task cannot run yet.
     */
    public function activeFor(string $task): Prompt
    {
        $config = config('ai.tasks.'.$task);
        $name = $config['prompt'] ?? null;
        $version = $config['prompt_version'] ?? null;

        if ($name === null || $version === null) {
            throw new RuntimeException("The AI task [{$task}] has no active prompt.");
        }

        return $this->find($name, (int) $version)
            ?? throw new RuntimeException("Prompt [{$name}/v{$version}] of task [{$task}] is missing.");
    }

    private function directory(string $prompt): string
    {
        return rtrim((string) config('ai.prompts_path'), '/\\').DIRECTORY_SEPARATOR.basename($prompt);
    }
}
