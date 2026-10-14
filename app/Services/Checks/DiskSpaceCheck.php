<?php

namespace App\Services\Checks;

class DiskSpaceCheck implements Check
{
    public function run(): CheckResult
    {
        $label = __('checks.disk_space.label');
        $freeBytes = $this->freeBytes();

        if ($freeBytes === null) {
            return CheckResult::warning($label, __('checks.disk_space.unknown'));
        }

        $freeMegabytes = intdiv((int) $freeBytes, 1024 * 1024);
        $minimumMegabytes = config('megis.health.min_free_disk_mb');

        if ($freeMegabytes < $minimumMegabytes) {
            return CheckResult::error($label, __('checks.disk_space.error', ['free' => $freeMegabytes, 'min' => $minimumMegabytes]));
        }

        return CheckResult::ok($label, __('checks.disk_space.ok', ['free' => $freeMegabytes]));
    }

    protected function freeBytes(): ?float
    {
        $freeBytes = disk_free_space(storage_path());

        return $freeBytes === false ? null : $freeBytes;
    }
}
