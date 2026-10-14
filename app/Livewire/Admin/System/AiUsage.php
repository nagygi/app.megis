<?php

namespace App\Livewire\Admin\System;

use App\Enums\AiCallStatus;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Models\AiCall;
use App\Models\Organization;
use App\Services\Ai\AiUsageQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Rendszer → AI használat (16 · 16.5): totals, breakdowns and the calls of a
 * period, with CSV export of the filtered list. Superadmin only.
 */
class AiUsage extends Component
{
    use WithDataTable;

    #[Url(as: 'idoszak')]
    public string $period = '7d';

    #[Url(as: 'tol')]
    public string $from = '';

    #[Url(as: 'ig')]
    public string $until = '';

    public ?int $openCallId = null;

    public function mount(): void
    {
        Gate::authorize('viewSystem');
    }

    public function updatedPeriod(): void
    {
        $this->resetPage();
    }

    public function toggleCall(int $callId): void
    {
        $this->openCallId = $this->openCallId === $callId ? null : $callId;
    }

    public function render(AiUsageQuery $usage): View
    {
        $period = in_array($this->period, AiUsageQuery::PERIODS, true) ? $this->period : '7d';
        $query = $usage->build($period, $this->from ?: null, $this->until ?: null, $this->filters);
        [$start, $end] = $usage->range($period, $this->from ?: null, $this->until ?: null);

        return view('livewire.admin.system.ai-usage', [
            'start' => $start,
            'end' => $end,
            'summary' => $usage->summary($query),
            'breakdowns' => collect(['model', 'task', 'organization', 'day'])
                ->mapWithKeys(fn (string $by) => [$by => $usage->breakdown($query, $by)])
                ->all(),
            'calls' => $this->applySorting((clone $query)->with('organization'))->paginate(50),
            'taskOptions' => collect(array_keys(config('ai.tasks')))->mapWithKeys(fn (string $task) => [$task => __('admin.ai.tasks.'.$task)])->all(),
            'modelOptions' => AiCall::query()->toBase()->selectRaw('distinct coalesce(model_version, model) as model_key')->orderBy('model_key')->pluck('model_key')
                ->mapWithKeys(fn (string $model) => [$model => $model])->all(),
            'organizationOptions' => [AiUsageQuery::PUBLIC_MIRROR => __('admin.ai.public_mirror')]
                + Organization::query()->orderBy('name')->pluck('name', 'id')->all(),
            'statusOptions' => collect(AiCallStatus::cases())->mapWithKeys(fn (AiCallStatus $status) => [$status->value => $status->label()])->all(),
            'exportUrl' => route('admin.system.ai.export', array_filter([
                'idoszak' => $period,
                'tol' => $this->from,
                'ig' => $this->until,
                'szuro' => array_filter($this->filters),
            ])),
        ])->layout('layouts::admin')->title(__('admin.ai.usage_title'));
    }

    protected function sortableColumns(): array
    {
        return [
            'time' => 'created_at',
            'task' => 'task',
            'model' => 'model',
            'organization' => 'organization_name',
            'tokens' => DB::raw('input_tokens + output_tokens'),
            'cost' => 'cost_usd',
            'status' => 'status',
            'duration' => 'duration_ms',
        ];
    }

    protected function defaultSort(): array
    {
        return ['time', 'desc'];
    }
}
