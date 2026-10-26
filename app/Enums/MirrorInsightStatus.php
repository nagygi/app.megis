<?php

namespace App\Enums;

/**
 * Where the ET4 suggestions of a mirror stand (NM-06).
 */
enum MirrorInsightStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case Failed = 'failed';
}
