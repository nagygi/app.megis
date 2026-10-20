{{--
    One pill of an x-ui.segmented group: filled when chosen. A button tells
    its state with aria-pressed, a link (href) with aria-current. A disabled
    one (disabledReason) stays readable and says why under the pointer and to
    screen readers.
--}}
@props([
    'selected' => false,
    'href' => null,
    'disabledReason' => null,
])

@php
    $classes = [
        'press inline-flex min-h-10 items-center gap-2 rounded-pill border-[1.5px] px-4 text-sm font-semibold no-underline',
        'border-charcoal bg-charcoal text-cream' => $selected,
        'border-charcoal bg-transparent text-charcoal hover:bg-yellow-pale' => ! $selected && ! $disabledReason,
        'cursor-not-allowed border-grey-300 text-grey-600' => ! $selected && $disabledReason,
    ];
@endphp

@if ($disabledReason)
    <span aria-disabled="true" title="{{ $disabledReason }}" {{ $attributes->class($classes) }}>
        {{ $slot }}
        <span class="sr-only">({{ $disabledReason }})</span>
    </span>
@elseif ($href)
    <a href="{{ $href }}" @if ($selected) aria-current="page" @endif {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="button" aria-pressed="{{ $selected ? 'true' : 'false' }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
