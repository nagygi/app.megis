<?php

use App\Logging\RedactSensitiveData;
use Monolog\Level;
use Monolog\LogRecord;

function logRecordWithContext(array $context): LogRecord
{
    return new LogRecord(
        datetime: new DateTimeImmutable('2026-10-14 09:00:00'),
        channel: 'app',
        level: Level::Info,
        message: 'Something happened.',
        context: $context,
    );
}

it('masks secrets, codes and recording content in the log context', function () {
    $record = logRecordWithContext([
        'password' => 'hunter2hunter2',
        'login_code' => '123456',
        'Authorization' => 'Bearer abc',
        'transcript' => 'Beszélő 1: ...',
        'request' => ['api_key' => 'sk-123', 'remember_token' => 'xyz'],
    ]);

    $redacted = (new RedactSensitiveData)->redactRecord($record);

    expect($redacted->context)->toBe([
        'password' => '[redacted]',
        'login_code' => '[redacted]',
        'Authorization' => '[redacted]',
        'transcript' => '[redacted]',
        'request' => ['api_key' => '[redacted]', 'remember_token' => '[redacted]'],
    ]);
});

it('keeps harmless keys that merely contain the word code', function () {
    $record = logRecordWithContext(['status_code' => 503, 'user_id' => 42]);

    $redacted = (new RedactSensitiveData)->redactRecord($record);

    expect($redacted->context)->toBe(['status_code' => 503, 'user_id' => 42]);
});
