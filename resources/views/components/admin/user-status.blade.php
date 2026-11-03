{{--
    Status badge of a user, the same on every user list: clients, members, admins (02 · 2.5).
    "status" is the shown state's key; inactive is dark so it stands out from the rest of the list.
--}}
@props(['status'])

<x-ui.badge dot :tone="match ($status) {
    'active' => 'green',
    'added', 'invited', 'pending' => 'yellow',
    'invitation_expired', 'locked' => 'coral',
    'inactive' => 'dark',
    default => 'muted',
}" {{ $attributes }}>{{ $slot }}</x-ui.badge>
