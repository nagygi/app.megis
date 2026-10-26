{{--
    One pill of an x-ui.segmented group: filled when chosen. A button tells
    its state with aria-pressed, a link (href) with aria-current. A disabled
    one (disabledReason) stays readable and says why under the pointer and to
    screen readers. "active" is an Alpine expression for a choice that stays
    in the browser (x-form.long-text); "selected" then draws the first state,
    so the pill does not fade in when Alpine starts.
--}}
@props([
    'selected' => false,
    'href' => null,
    'disabledReason' => null,
    'active' => null,
])

@php
    $base = 'press inline-flex min-h-10 items-center gap-2 rounded-pill border-[1.5px] px-4 text-sm font-semibold no-underline';
    $chosen = 'bg-charcoal text-cream';
    $open = 'bg-transparent text-charcoal hover:bg-yellow-pale';
    $classes = [
        $base,
        'border-charcoal '.$chosen => $selected,
        'border-charcoal '.$open => ! $selected && ! $disabledReason,
        'cursor-not-allowed border-grey-300 text-grey-600' => ! $selected && $disabledReason,
    ];
@endphp

@if ($active)
    {{-- The object form also takes off the first state's classes. --}}
    <button
        type="button"
        aria-pressed="{{ $selected ? 'true' : 'false' }}"
        x-bind:aria-pressed="({{ $active }}).toString()"
        x-bind:class="{ @js($chosen): ({{ $active }}), @js($open): ! ({{ $active }}) }"
        {{ $attributes->class([$base, 'border-charcoal', $selected ? $chosen : $open]) }}
    >{{ $slot }}</button>
@elseif ($disabledReason)
    <span aria-disabled="true" title="{{ $disabledReason }}" {{ $attributes->class($classes) }}>
        {{ $slot }}
        <span class="sr-only">({{ $disabledReason }})</span>
    </span>
@elseif ($href)
    <a href="{{ $href }}" @if ($selected) aria-current="page" @endif {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="button" aria-pressed="{{ $selected ? 'true' : 'false' }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
