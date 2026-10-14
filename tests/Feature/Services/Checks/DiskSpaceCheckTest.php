<?php

use App\Enums\CheckStatus;
use App\Services\Checks\DiskSpaceCheck;

function diskSpaceCheckWithFreeBytes(?float $freeBytes): DiskSpaceCheck
{
    return new class($freeBytes) extends DiskSpaceCheck
    {
        public function __construct(private ?float $fakeFreeBytes) {}

        protected function freeBytes(): ?float
        {
            return $this->fakeFreeBytes;
        }
    };
}

beforeEach(function () {
    config(['megis.health.min_free_disk_mb' => 1024]);
});

it('reports an error below the minimum free space', function () {
    $result = diskSpaceCheckWithFreeBytes(500 * 1024 * 1024)->run();

    expect($result->status)->toBe(CheckStatus::Error)
        ->and($result->message)->toBe('Csak 500 MB szabad (legalább 1024 MB kell).');
});

it('passes at or above the minimum free space', function () {
    $result = diskSpaceCheckWithFreeBytes(1024 * 1024 * 1024)->run();

    expect($result->status)->toBe(CheckStatus::Ok);
});

it('warns when the free space cannot be read', function () {
    $result = diskSpaceCheckWithFreeBytes(null)->run();

    expect($result->status)->toBe(CheckStatus::Warning);
});
