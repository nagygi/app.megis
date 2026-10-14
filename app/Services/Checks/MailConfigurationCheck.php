<?php

namespace App\Services\Checks;

use App\Services\Checks\Concerns\DescribesExceptions;
use Illuminate\Mail\MailManager;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Throwable;

class MailConfigurationCheck implements Check
{
    use DescribesExceptions;

    private const LOCAL_TRANSPORTS = ['log', 'array'];

    public function __construct(private MailManager $mail) {}

    public function run(): CheckResult
    {
        $label = __('checks.mail.label');
        $mailer = config('mail.default');
        $transport = config("mail.mailers.{$mailer}.transport");

        if (in_array($transport, self::LOCAL_TRANSPORTS, true)) {
            return app()->isProduction()
                ? CheckResult::error($label, __('checks.mail.production_local', ['mailer' => $mailer]))
                : CheckResult::ok($label, __('checks.mail.local', ['mailer' => $mailer]));
        }

        try {
            $symfonyTransport = $this->mail->mailer($mailer)->getSymfonyTransport();

            if ($symfonyTransport instanceof SmtpTransport) {
                $symfonyTransport->start();
                $symfonyTransport->stop();

                return CheckResult::ok($label, __('checks.mail.ok_connected', ['mailer' => $mailer]));
            }

            return CheckResult::ok($label, __('checks.mail.ok', ['mailer' => $mailer]));
        } catch (Throwable $exception) {
            return CheckResult::error($label, __('checks.mail.error', ['error' => $this->describe($exception)]));
        }
    }
}
