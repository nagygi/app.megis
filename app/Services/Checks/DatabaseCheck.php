<?php

namespace App\Services\Checks;

use App\Services\Checks\Concerns\DescribesExceptions;
use Illuminate\Database\DatabaseManager;
use Throwable;

class DatabaseCheck implements Check
{
    use DescribesExceptions;

    public function __construct(private DatabaseManager $database) {}

    public function run(): CheckResult
    {
        $label = __('checks.database.label');

        try {
            $connection = $this->database->connection();
            $connection->select('select 1');

            return CheckResult::ok($label, __('checks.database.ok', ['driver' => $connection->getDriverName()]));
        } catch (Throwable $exception) {
            return CheckResult::error($label, __('checks.database.error', ['error' => $this->describe($exception)]));
        }
    }
}
