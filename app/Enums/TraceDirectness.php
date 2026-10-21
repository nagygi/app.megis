<?php

namespace App\Enums;

/**
 * How directly a trace shows what it says (6.1): an observed behaviour,
 * a self-report, someone else's opinion, or an expert's interpretation.
 */
enum TraceDirectness: string
{
    case Observed = 'observed';
    case SelfReport = 'self_report';
    case OthersOpinion = 'others_opinion';
    case Interpretation = 'interpretation';

    public function label(): string
    {
        return __('enums.trace_directness.'.$this->value);
    }
}
