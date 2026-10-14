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
}
