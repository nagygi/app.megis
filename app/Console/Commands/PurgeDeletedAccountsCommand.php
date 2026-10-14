<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Accounts\AccountDeletionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

/**
 * The final step of "Adataim törlése": the personal data goes within 24 hours
 * of the request, the user row stays as a tombstone (31 · 31.7).
 */
#[Signature('megis:accounts:purge')]
#[Description('A törölt fiókok személyes adatainak végleges törlése (31 · 31.7)')]
class PurgeDeletedAccountsCommand extends Command
{
    public function handle(AccountDeletionService $deletions): int
    {
        $count = 0;

        User::query()->whereNotNull('deletion_requested_at')->whereNull('anonymized_at')->each(function (User $user) use ($deletions, &$count): void {
            try {
                $deletions->purge($user);
                $count++;
            } catch (Throwable $exception) {
                report($exception);
            }
        });

        $this->info(__('console.accounts_purge.done', ['count' => $count]));

        return self::SUCCESS;
    }
}
