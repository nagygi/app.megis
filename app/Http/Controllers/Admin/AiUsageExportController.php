<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiCall;
use App\Services\Ai\AiUsageQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV export of the filtered AI call list (16 · 16.5). Superadmin only.
 */
class AiUsageExportController extends Controller
{
    public function __invoke(Request $request, AiUsageQuery $usage): StreamedResponse
    {
        Gate::authorize('viewSystem');

        $period = in_array($request->query('idoszak'), AiUsageQuery::PERIODS, true) ? (string) $request->query('idoszak') : '7d';
        $filters = array_map('strval', array_filter((array) $request->query('szuro', []), 'is_scalar'));
        // lazyById() walks the calls in id order, which is the order they were made.
        $query = $usage->build($period, $request->query('tol'), $request->query('ig'), $filters);

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'w');
            // Excel opens UTF-8 with the BOM correctly.
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                __('admin.ai.time'), __('admin.ai.task'), __('admin.ai.provider'), __('admin.ai.model'),
                __('admin.ai.prompt_version'), __('admin.ai.organization'), 'input_tokens', 'output_tokens',
                'cache_read_tokens', 'cache_write_tokens', __('admin.ai.audio_minutes'), 'cost_usd',
                __('admin.ai.status'), 'duration_ms', __('admin.ai.error'),
            ], ';');

            $query->with('organization')->lazyById(500)->each(function (AiCall $call) use ($output): void {
                fputcsv($output, [
                    $call->created_at->format('Y-m-d H:i:s'),
                    $call->task,
                    $call->provider,
                    $call->model_version ?? $call->model,
                    $call->prompt ? $call->prompt.'/v'.$call->prompt_version : '',
                    $call->contextLabel(),
                    $call->input_tokens,
                    $call->output_tokens,
                    $call->cache_read_tokens,
                    $call->cache_write_tokens,
                    $call->audio_minutes,
                    $call->cost_usd,
                    $call->status->value,
                    $call->duration_ms,
                    $call->error,
                ], ';');
            });

            fclose($output);
        }, 'ai-hivasok-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
