<?php

namespace App\Livewire\Admin\Measurements;

use App\Enums\InboxItemType;
use App\Livewire\Admin\Inbox\Index as InboxIndex;

/**
 * Értékmérések: the Beérkező rows of the Értéktükör (06, 22), i.e. the
 * bookings and the runs that failed to process, with the same buttons.
 */
class Index extends InboxIndex
{
    protected bool $measurements = true;

    protected function title(): string
    {
        return __('admin.inbox.measurements_title');
    }

    protected function types(): array
    {
        return [InboxItemType::MirrorBooking, InboxItemType::ProcessingError];
    }
}
