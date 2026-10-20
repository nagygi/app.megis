{{-- One item of a row-actions menu: a link with href, otherwise a button. With a
     disabledReason it is greyed out, says why on hover and leaves the menu open. --}}
@props(['href' => null, 'type' => 'button', 'disabledReason' => null])

@php
    $classes = 'block w-full rounded-field px-3 py-1.5 text-left no-underline';
@endphp

@if ($disabledReason)
    {{-- aria-disabled rather than disabled: it stays focusable, and screen readers read the reason. --}}
    <button type="button" aria-disabled="true" title="{{ $disabledReason }}" x-on:click.stop {{ $attributes->class([$classes, 'cursor-not-allowed text-grey-600']) }}>{{ $slot }}</button>
@elseif ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes, 'text-charcoal hover:bg-grey-100']) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$classes, 'text-charcoal hover:bg-grey-100']) }}>{{ $slot }}</button>
@endif
