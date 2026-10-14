<?php

namespace App\Services\Checks;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;

class CheckRunner
{
    public function __construct(private Container $container) {}

    /**
     * @param  list<class-string<Check>>  $checkClasses
     * @return Collection<int, CheckResult>
     */
    public function run(array $checkClasses): Collection
    {
        return collect($checkClasses)
            ->map(fn (string $checkClass): CheckResult => $this->container->make($checkClass)->run())
            ->values();
    }
}
