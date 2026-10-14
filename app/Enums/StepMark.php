<?php

namespace App\Enums;

/**
 * What the user did with a step besides filling it in: a read-only step is
 * done once viewed, the processes step can be skipped (23 · 23.3, 11 · 11.1).
 */
enum StepMark: string
{
    case Viewed = 'viewed';
    case Skipped = 'skipped';
}
