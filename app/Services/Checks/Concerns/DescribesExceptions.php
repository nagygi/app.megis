<?php

namespace App\Services\Checks\Concerns;

use Illuminate\Support\Str;
use Throwable;

trait DescribesExceptions
{
    /**
     * A short, single-line description that is safe to show in check output.
     */
    protected function describe(Throwable $exception): string
    {
        $message = Str::of($exception->getMessage())->squish()->limit(200)->toString();

        return class_basename($exception).($message === '' ? '' : ': '.$message);
    }
}
