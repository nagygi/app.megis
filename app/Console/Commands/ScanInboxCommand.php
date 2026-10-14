<?php

namespace App\Console\Commands;

use App\Services\Inbox\InboxScanner;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * The daily scan of the Beérkező before the 8:00 summary (22 · 22.2):
 * invitations, completion, threshold, publishing; resolved rows close.
 */
#[Signature('megis:inbox:scan')]
#[Description('A Beérkező napi átnézése (22 · 22.2)')]
class ScanInboxCommand extends Command
{
    public function handle(InboxScanner $scanner): int
    {
        $result = $scanner->scan();

        $this->info(__('console.inbox_scan.done', $result));

        return self::SUCCESS;
    }
}
