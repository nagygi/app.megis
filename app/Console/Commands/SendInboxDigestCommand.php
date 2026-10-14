<?php

namespace App\Console\Commands;

use App\Mail\InboxDigestMail;
use App\Models\InboxItem;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Every morning at 8:00 each contact gets the open rows of their own
 * organizations in one e-mail; nothing open, no e-mail (22, question 2).
 */
#[Signature('megis:inbox:digest')]
#[Description('Reggeli Beérkező-összefoglaló a kapcsolattartóknak (22)')]
class SendInboxDigestCommand extends Command
{
    public function handle(): int
    {
        $sent = 0;

        User::query()->admins()->whereNull('deactivated_at')->each(function (User $admin) use (&$sent): void {
            $items = InboxItem::query()
                ->open()
                ->whereHas('organization', fn ($organization) => $organization->where('contact_admin_id', $admin->id))
                ->with(['organization', 'diagnosis.team'])
                ->oldest()
                ->get();

            if ($items->isEmpty()) {
                return;
            }

            Mail::to($admin)->send(new InboxDigestMail($admin, $items));
            $sent++;
        });

        $this->info(__('console.inbox_digest.done', ['count' => $sent]));

        return self::SUCCESS;
    }
}
