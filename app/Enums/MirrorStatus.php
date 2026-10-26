<?php

namespace App\Enums;

/**
 * State of an Értéktükör run (06 · 6.1).
 */
enum MirrorStatus: string
{
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';
    case AwaitingConfirmation = 'awaiting_confirmation';
    case Claimed = 'claimed';

    /**
     * The processing state only; whose the mirror is shows apart (MirrorSession::accountState).
     */
    public function label(): string
    {
        return __('enums.mirror_status.'.$this->value);
    }

    public function isDone(): bool
    {
        return in_array($this, [self::Ready, self::AwaitingConfirmation, self::Claimed], true);
    }
}
