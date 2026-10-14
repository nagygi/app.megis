<?php

namespace App\Services\Alerting;

use App\Http\Middleware\AssignLogId;
use App\Mail\ErrorAlert;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Emails the alert list about critical errors and finally failed jobs.
 * The same error is sent at most once per throttle window (docs/uzemeltetes.md · 5).
 */
class ErrorAlerter
{
    public function __construct(
        private Mailer $mailer,
        private Repository $cache,
    ) {}

    public function reportException(Throwable $exception, ?string $url): void
    {
        $this->send($exception, url: $url, jobName: null);
    }

    public function reportFailedJob(string $jobName, Throwable $exception): void
    {
        $this->send($exception, url: null, jobName: $jobName);
    }

    private function send(Throwable $exception, ?string $url, ?string $jobName): void
    {
        $recipients = config('megis.alerts.recipients');

        if ($recipients === [] || ! $this->claimThrottleSlot($exception, $jobName)) {
            return;
        }

        try {
            $this->mailer->to($recipients)->send(new ErrorAlert(
                environment: app()->environment(),
                errorClass: $exception::class,
                errorMessage: $this->sanitize($exception->getMessage()),
                url: $url,
                jobName: $jobName,
                occurredAt: now()->toImmutable(),
                logId: Context::get(AssignLogId::CONTEXT_KEY),
            ));
        } catch (Throwable $mailException) {
            // An alert must never take the request down with it.
            Log::channel('mail')->warning('Error alert could not be sent.', [
                'error' => $mailException::class,
            ]);
        }
    }

    private function claimThrottleSlot(Throwable $exception, ?string $jobName): bool
    {
        $fingerprint = sha1(implode('|', [
            $exception::class,
            $exception->getFile(),
            $exception->getLine(),
            $jobName,
        ]));

        return $this->cache->add(
            'megis:alert:'.$fingerprint,
            true,
            now()->addMinutes(config('megis.alerts.throttle_minutes')),
        );
    }

    /**
     * Error messages may quote user input, so e-mail addresses are masked
     * and the text is shortened before it leaves the system.
     */
    private function sanitize(string $message): string
    {
        $withoutEmails = preg_replace('/[^\s@<>"\']+@[^\s@<>"\']+\.[a-z]{2,}/iu', '[e-mail]', $message) ?? '';

        return Str::of($withoutEmails)->squish()->limit(300)->toString();
    }
}
