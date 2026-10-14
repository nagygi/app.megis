<?php

namespace App\Livewire\Admin\System;

use App\Services\Ai\PromptRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Rendszer → Promptok (16 · 16.5): the versions of each task's prompt, the
 * active one marked, and any version's text. Read-only: prompts change in
 * the code base, after review (16 · 16.2).
 */
class AiPrompts extends Component
{
    #[Url(as: 'prompt')]
    public string $openPrompt = '';

    #[Url(as: 'verzio')]
    public ?int $openVersion = null;

    public function mount(): void
    {
        Gate::authorize('viewSystem');
    }

    public function open(string $prompt, int $version): void
    {
        $this->openPrompt = $prompt;
        $this->openVersion = $version;
    }

    public function render(PromptRepository $prompts): View
    {
        $groups = collect(config('ai.tasks'))
            ->filter(fn (array $task) => $task['prompt'] !== null)
            ->map(fn (array $task, string $key) => [
                'task' => $key,
                'prompt' => $task['prompt'],
                'active' => $task['prompt_version'],
                'versions' => $prompts->versions($task['prompt']),
            ])
            ->values();

        $opened = $this->openPrompt !== '' && $this->openVersion !== null && $groups->contains('prompt', $this->openPrompt)
            ? $prompts->find($this->openPrompt, $this->openVersion)
            : null;

        return view('livewire.admin.system.ai-prompts', [
            'groups' => $groups,
            'opened' => $opened,
        ])->layout('layouts::admin')->title(__('admin.ai.prompts_title'));
    }
}
