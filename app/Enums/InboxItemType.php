<?php

namespace App\Enums;

/**
 * Row types of the Beérkező (22 · 22.2). Each comes with the ticket that opens it.
 */
enum InboxItemType: string
{
    case Classification = 'classification';
    case ProcessingError = 'processing_error';
    case Feedback = 'feedback';
    case Invitation = 'invitation';
    case Completion = 'completion';
    case Threshold = 'threshold';
    case Publication = 'publication';
    case MirrorBooking = 'mirror_booking';

    public function label(): string
    {
        return __('enums.inbox_item_type.'.$this->value);
    }
}
