<?php

namespace App\Events;

use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An admin put a user into a team (02 · 2.5).
 */
class TeamMemberAdded implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Team $team,
        public User $user,
    ) {}
}
