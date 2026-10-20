<?php

namespace App\Livewire\Admin\System;

use App\Enums\AiCallStatus;
use App\Models\AiCall;
use App\Models\AiModelPrice;
use App\Services\Ai\CostCalculator;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Rendszer → AI konfiguráció (16 · 16.5): read-only, one row per task.
 * Models change through config/ai.php and a deploy, never here.
 */
class AiConfiguration extends Component
{
    public function mount(): void
    {
        Gate::authorize('viewSystem');
    }

    public function render(CostCalculator $costs): View
    {
        $lastSuccess = AiCall::query()
            ->where('status', AiCallStatus::Succeeded)
            ->groupBy('task')
            ->selectRaw('task, max(created_at) as last_at')
            ->pluck('last_at', 'task');

        $tasks = collect(config('ai.tasks'))->map(fn (array $task, string $key) => [
            'key' => $key,
            'provider' => $task['provider'],
            'model' => $task['model'],
            'prompt' => $task['prompt'],
            'prompt_version' => $task['prompt_version'],
            'price' => $task['model'] !== null ? $costs->price($task['model']) : null,
            'last_success' => $lastSuccess->get($key),
        ])->values();

        return view('livewire.admin.system.ai-configuration', [
            'tasks' => $tasks,
            'pricesSyncedAt' => AiModelPrice::query()->max('synced_at'),
        ])
            ->layout('layouts::admin')
            ->title(__('admin.ai.configuration_title'));
    }
}
