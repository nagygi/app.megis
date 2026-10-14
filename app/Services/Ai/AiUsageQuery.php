<?php

namespace App\Services\Ai;

use App\Enums\AiCallStatus;
use App\Models\AiCall;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The calls of a period with the list filters (16 · 16.5); the same query
 * feeds the summary, the breakdowns, the list and the CSV export.
 */
class AiUsageQuery
{
    public const PERIODS = ['today', '7d', '30d', 'month', 'custom'];

    public const PUBLIC_MIRROR = 'public-mirror';

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function range(string $period, ?string $from = null, ?string $until = null): array
    {
        $now = CarbonImmutable::now();

        return match ($period) {
            'today' => [$now->startOfDay(), $now->endOfDay()],
            '30d' => [$now->subDays(29)->startOfDay(), $now->endOfDay()],
            'month' => [$now->startOfMonth(), $now->endOfDay()],
            'custom' => [
                $from ? CarbonImmutable::parse($from)->startOfDay() : $now->subDays(6)->startOfDay(),
                $until ? CarbonImmutable::parse($until)->endOfDay() : $now->endOfDay(),
            ],
            default => [$now->subDays(6)->startOfDay(), $now->endOfDay()],
        };
    }

    /**
     * @param  array<string, string>  $filters  task, model, organization, status
     * @return Builder<AiCall>
     */
    public function build(string $period, ?string $from, ?string $until, array $filters = []): Builder
    {
        [$start, $end] = $this->range($period, $from, $until);

        $query = AiCall::query()->whereBetween('created_at', [$start, $end]);

        if (filled($filters['task'] ?? null)) {
            $query->where('task', $filters['task']);
        }

        if (filled($filters['model'] ?? null)) {
            $query->where(DB::raw('coalesce(model_version, model)'), $filters['model']);
        }

        if (($filters['organization'] ?? null) === self::PUBLIC_MIRROR) {
            $query->where('public_mirror', true);
        } elseif (filled($filters['organization'] ?? null)) {
            $query->where('organization_id', $filters['organization']);
        }

        if (($status = AiCallStatus::tryFrom((string) ($filters['status'] ?? ''))) !== null) {
            $query->where('status', $status);
        }

        return $query;
    }

    /**
     * @param  Builder<AiCall>  $query
     * @return array{calls: int, tokens: int, cost: float, unpriced: int}
     */
    public function summary(Builder $query): array
    {
        $row = (clone $query)->toBase()->selectRaw(
            'count(*) as calls, coalesce(sum(input_tokens + output_tokens), 0) as tokens, coalesce(sum(cost_usd), 0) as cost, sum(case when cost_usd is null then 1 else 0 end) as unpriced'
        )->first();

        return [
            'calls' => (int) $row->calls,
            'tokens' => (int) $row->tokens,
            'cost' => (float) $row->cost,
            'unpriced' => (int) $row->unpriced,
        ];
    }

    /**
     * Calls, tokens and cost grouped by model, task, organization or day.
     *
     * @param  Builder<AiCall>  $query
     * @return list<array{key: string, calls: int, tokens: int, cost: float}>
     */
    public function breakdown(Builder $query, string $by): array
    {
        $expression = match ($by) {
            'model' => 'coalesce(model_version, model)',
            'task' => 'task',
            'organization' => "case when public_mirror then '".self::PUBLIC_MIRROR."' else coalesce(organization_name, '') end",
            'day' => 'date(created_at)',
        };

        return (clone $query)->toBase()
            ->selectRaw("{$expression} as breakdown_key, count(*) as calls, coalesce(sum(input_tokens + output_tokens), 0) as tokens, coalesce(sum(cost_usd), 0) as cost")
            ->groupByRaw($expression)
            ->orderByRaw($by === 'day' ? "{$expression} desc" : 'cost desc, calls desc')
            ->get()
            ->map(fn (object $row) => [
                'key' => (string) $row->breakdown_key,
                'calls' => (int) $row->calls,
                'tokens' => (int) $row->tokens,
                'cost' => (float) $row->cost,
            ])
            ->all();
    }
}
