{{-- One item of a row-actions menu: a link with href, otherwise a button. --}}
@props(['href' => null, 'type' => 'button'])

@php
    $classes = 'block w-full rounded-lg px-3 py-1.5 text-left text-charcoal no-underline hover:bg-grey-100';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
