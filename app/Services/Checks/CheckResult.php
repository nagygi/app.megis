<?php

namespace App\Services\Checks;

use App\Enums\CheckStatus;

final readonly class CheckResult
{
    public function __construct(
        public string $label,
        public CheckStatus $status,
        public string $message = '',
    ) {}

    public static function ok(string $label, string $message = ''): self
    {
        return new self($label, CheckStatus::Ok, $message);
    }

    public static function warning(string $label, string $message): self
    {
        return new self($label, CheckStatus::Warning, $message);
    }

    public static function error(string $label, string $message): self
    {
        return new self($label, CheckStatus::Error, $message);
    }

    public function isError(): bool
    {
        return $this->status === CheckStatus::Error;
    }

    /**
     * @return array{label: string, status: string, message: string}
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'status' => $this->status->value,
            'message' => $this->message,
        ];
    }
}
