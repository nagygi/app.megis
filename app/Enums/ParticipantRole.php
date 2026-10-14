<?php

namespace App\Enums;

/**
 * A user's role in a main diagnosis (17 · 17.2, 25). One person can be the
 * leader of one diagnosis and a peer in another (17 · 17.6).
 */
enum ParticipantRole: string
{
    case Leader = 'leader';
    case Peer = 'peer';

    public function label(): string
    {
        return __('enums.participant_role.'.$this->value);
    }
}
