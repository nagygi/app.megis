<?php

namespace App\Console\Commands;

use App\Services\Auth\ImpersonationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Closes the "nevében" works an admin left without "Kilépés", so the log
 * shows their end (33 · 33.6). Scheduled every ten minutes (docs/cron.md).
 */
#[Signature('megis:impersonations:close')]
#[Description('Félbehagyott „nevében” munkamenetek lezárása (33 · 33.6)')]
class CloseImpersonationsCommand extends Command
{
    public function handle(ImpersonationService $impersonations): int
    {
        $count = $impersonations->closeAbandoned();

        $this->info(__('console.impersonations_close.done', ['count' => $count]));

        return self::SUCCESS;
    }
}
