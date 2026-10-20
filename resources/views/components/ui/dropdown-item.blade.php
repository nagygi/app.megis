{{--
    One item of an x-ui.dropdown: a link with href, otherwise a button.
    tone="danger" marks a destructive one. With a disabledReason it is greyed
    out, says why on hover and leaves the menu open.
--}}
@props([
    'href' => null,
    'type' => 'button',
    'tone' => 'default',
    'disabledReason' => null,
])

@php
    $classes = 'flex min-h-10 w-full items-center rounded-field px-3 py-2 text-left no-underline';
    $toneClasses = $tone === 'danger'
        ? 'text-coral-ink hover:bg-coral-light hover:text-black'
        : 'text-charcoal hover:bg-grey-100';
@endphp

@if ($disabledReason)
    {{-- aria-disabled rather than disabled: it stays focusable, and screen readers read the reason. --}}
    <button type="button" aria-disabled="true" title="{{ $disabledReason }}" x-on:click.stop {{ $attributes->class([$classes, 'cursor-not-allowed text-grey-600']) }}>{{ $slot }}</button>
@elseif ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes, $toneClasses]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$classes, $toneClasses]) }}>{{ $slot }}</button>
@endif
