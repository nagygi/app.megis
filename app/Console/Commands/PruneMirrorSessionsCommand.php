<?php

namespace App\Console\Commands;

use App\Services\Mirror\MirrorService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Deletes anonymous Értéktükör runs after 24 hours; only the anonymous
 * statistics stay (06 · 6.1). Scheduled hourly (docs/cron.md).
 */
#[Signature('megis:mirror:prune')]
#[Description('Lejárt névtelen Értéktükrök törlése (06 · 6.1)')]
class PruneMirrorSessionsCommand extends Command
{
    public function handle(MirrorService $mirrors): int
    {
        $count = $mirrors->pruneExpired();

        $this->info(__('console.mirror_prune.done', ['count' => $count]));

        return self::SUCCESS;
    }
}
