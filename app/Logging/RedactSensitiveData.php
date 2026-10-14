<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\LogRecord;

/**
 * Masks secrets and personal content in log context before it is written.
 *
 * docs/uzemeltetes.md · 4: passwords, login codes, tokens, API keys and the
 * content of transcripts or recordings must never reach the logs.
 */
class RedactSensitiveData
{
    public const MASK = '[redacted]';

    /**
     * Context keys are matched case-insensitively as substrings.
     *
     * @var list<string>
     */
    private const SENSITIVE_KEY_FRAGMENTS = [
        'password',
        'token',
        'secret',
        'api_key',
        'apikey',
        'authorization',
        'cookie',
        'transcript',
        'recording',
        'credential',
    ];

    /**
     * Matched exactly, so that keys like "status_code" stay readable.
     *
     * @var list<string>
     */
    private const SENSITIVE_KEYS = [
        'code',
        'login_code',
        'otp',
    ];

    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->pushProcessor(fn (LogRecord $record): LogRecord => $this->redactRecord($record));
        }
    }

    public function redactRecord(LogRecord $record): LogRecord
    {
        return $record->with(
            context: $this->redact($record->context),
            extra: $this->redact($record->extra),
        );
    }

    /**
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    public function redact(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_string($key) && $this->isSensitiveKey($key)) {
                $values[$key] = self::MASK;

                continue;
            }

            if (is_array($value)) {
                $values[$key] = $this->redact($value);
            }
        }

        return $values;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalizedKey = mb_strtolower($key);

        if (in_array($normalizedKey, self::SENSITIVE_KEYS, true)) {
            return true;
        }

        foreach (self::SENSITIVE_KEY_FRAGMENTS as $fragment) {
            if (str_contains($normalizedKey, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
