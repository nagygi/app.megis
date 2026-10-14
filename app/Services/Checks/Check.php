<?php

namespace App\Services\Checks;

/**
 * One environment or dependency check, shared by GET /health,
 * the admin health page and php artisan megis:setup:check.
 */
interface Check
{
    public function run(): CheckResult;
}
