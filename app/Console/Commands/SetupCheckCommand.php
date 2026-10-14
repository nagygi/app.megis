<?php

namespace App\Console\Commands;

use App\Enums\CheckStatus;
use App\Services\Checks\CheckResult;
use App\Services\Checks\CheckRunner;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('megis:setup:check')]
#[Description('Checks that the environment is set up correctly (docs/uzemeltetes.md · 7)')]
class SetupCheckCommand extends Command
{
    public function handle(CheckRunner $runner): int
    {
        $results = $runner->run(config('megis.setup_checks'));

        $this->table(
            [__('checks.command.status'), __('checks.command.check'), __('checks.command.details')],
            $results->map(fn (CheckResult $result): array => [
                sprintf('<%s>%s</>', $this->colorFor($result->status), $result->status->label()),
                $result->label,
                $result->message,
            ])->all(),
        );

        $errorCount = $results->filter->isError()->count();
        $warningCount = $results->where('status', CheckStatus::Warning)->count();

        if ($errorCount > 0) {
            $this->error(__('checks.command.summary_errors', ['count' => $errorCount]));

            return self::FAILURE;
        }

        $warningCount > 0
            ? $this->warn(__('checks.command.summary_warnings', ['count' => $warningCount]))
            : $this->info(__('checks.command.summary_ok'));

        return self::SUCCESS;
    }

    private function colorFor(CheckStatus $status): string
    {
        return match ($status) {
            CheckStatus::Ok => 'fg=green',
            CheckStatus::Warning => 'fg=yellow',
            CheckStatus::Error => 'fg=red',
        };
    }
}
