<?php

namespace App\Livewire\Admin\System;

use App\Services\Checks\CheckRunner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\View\View;
use Livewire\Component;
use Throwable;

/**
 * Rendszer → Health (docs/uzemeltetes.md · 3): the health checks in detail,
 * with version, environment, last deploy, queue length and failed jobs.
 */
class Health extends Component
{
    public function render(): View
    {
        return view('livewire.admin.system.health', [
            'results' => app(CheckRunner::class)->run(config('megis.health.checks')),
            'version' => config('megis.version'),
            'environment' => app()->environment(),
            'deployedAt' => config('megis.deployed_at'),
            'queueSize' => $this->queueSize(),
            'failedJobs' => DB::table(config('queue.failed.table', 'failed_jobs'))->count(),
        ])->layout('layouts::admin')->title(__('admin.system.health_title'));
    }

    private function queueSize(): ?int
    {
        try {
            return Queue::size();
        } catch (Throwable) {
            return null;
        }
    }
}
