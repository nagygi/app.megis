{{--
    A round button with only an icon: remove, move up or down, close, more.
    The target is 44px whatever the icon; "label" names it for screen readers
    and as a tooltip. Renders a link when href is given.
--}}
@props([
    'icon',
    'label',
    'href' => null,
    'type' => 'button',
])

@php
    $classes = 'press inline-grid size-11 shrink-0 place-items-center rounded-pill text-charcoal no-underline hover:bg-grey-100 disabled:cursor-not-allowed disabled:opacity-40 data-loading:pointer-events-none data-loading:opacity-60';
@endphp

@if ($href)
    <a href="{{ $href }}" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->class($classes) }}>
        <x-megis.icon :name="$icon" class="size-5" />
    </a>
@else
    <button type="{{ $type }}" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->class($classes) }}>
        <x-megis.icon :name="$icon" class="size-5" />
    </button>
@endif
