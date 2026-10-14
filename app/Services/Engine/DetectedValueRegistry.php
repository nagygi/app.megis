<?php

namespace App\Services\Engine;

use App\Enums\ValueOrigin;
use App\Models\Organization;
use App\Models\RawDetection;
use App\Models\Value;
use App\Services\Values\ValueService;
use Illuminate\Support\Str;

/**
 * Values the engine found that match nothing go to the Értéktár as
 * "AI által detektált", with the AI's reasoning under "Mit jelent?"
 * (06 · 6.4, 03 · 3.7). For anonymous mirrors this happens only after
 * the account is confirmed (plan 3.7).
 */
class DetectedValueRegistry
{
    public const SUFFIX = ' (detektált)';

    public function __construct(private ValueService $values) {}

    /**
     * Name rules (06 · 6.4): an existing AI-detected value gets the new
     * occurrence; a regular Értéktár value that step B did not match gets a
     * new "… (detektált)" value, because the same word means something else here.
     */
    public function register(RawDetection $detection, ?Organization $organization): Value
    {
        $name = trim($detection->free_name);
        $existing = $this->values->findByName($name);

        $value = match (true) {
            $existing === null => $this->create($name, $detection, $organization),
            $existing->origin === ValueOrigin::Ai => $existing,
            default => $this->values->findByName($name.self::SUFFIX)
                ?? $this->create($name.self::SUFFIX, $detection, $organization),
        };

        $detection->forceFill(['unmatched_value_id' => $value->id])->save();

        return $value;
    }

    private function create(string $name, RawDetection $detection, ?Organization $organization): Value
    {
        $reasoning = trim((string) $detection->reasoning);

        return $this->values->createElsewhere($name, ValueOrigin::Ai, $organization, [
            'meanings' => $reasoning === '' ? [] : [Str::limit($reasoning, 250, '…')],
        ]);
    }
}
