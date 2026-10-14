<?php

namespace App\Services\Checks;

use App\Services\Checks\Concerns\DescribesExceptions;
use Illuminate\Filesystem\FilesystemManager;
use RuntimeException;
use Throwable;

class MaterialsStorageCheck implements Check
{
    use DescribesExceptions;

    public const PROBE_PATH = '.health/probe.txt';

    private const PROBE_CONTENT = 'ok';

    public function __construct(private FilesystemManager $storage) {}

    public function run(): CheckResult
    {
        $label = __('checks.materials_storage.label');

        try {
            $disk = $this->storage->disk('materials');

            if (! $disk->exists(self::PROBE_PATH)) {
                $disk->put(self::PROBE_PATH, self::PROBE_CONTENT);
            }

            if ($disk->get(self::PROBE_PATH) !== self::PROBE_CONTENT) {
                throw new RuntimeException('unexpected probe content');
            }

            return CheckResult::ok($label, __('checks.materials_storage.ok', [
                'driver' => config('filesystems.disks.materials.driver'),
            ]));
        } catch (Throwable $exception) {
            return CheckResult::error($label, __('checks.materials_storage.error', ['error' => $this->describe($exception)]));
        }
    }
}
