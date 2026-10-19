{{-- The organizations of an admin select; a name that is not unique gets its address too. --}}
@props(['organizations'])

@php($duplicateNames = $organizations->countBy(fn ($organization) => mb_strtolower($organization->name))->filter(fn (int $count) => $count > 1))

@foreach ($organizations as $organization)
    <option value="{{ $organization->id }}">{{ $organization->name.($duplicateNames->has(mb_strtolower($organization->name)) ? ' · /'.$organization->slug : '') }}</option>
@endforeach
