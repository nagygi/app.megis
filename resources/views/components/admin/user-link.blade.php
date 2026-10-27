{{--
    A customer's name that opens their datasheet (33 · 33.1, NFR: the entity
    name opens it). Without a user it prints nothing; a deleted user reads
    "Törölt felhasználó · #id" and still opens the log.
--}}
@props(['user'])

@if ($user)
    <a href="{{ route('admin.clients.show', $user) }}" {{ $attributes->class('font-semibold text-charcoal') }}>{{ $user->name }}</a>
@endif
