{{--
    Pill button of the design system. Renders a link when href is given.

    Tones keep WCAG AA contrast: cream text on coral would only reach 3:1,
    so the coral tone uses black text.
--}}
@props([
    'tone' => 'dark',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $tones = [
        'dark' => 'bg-charcoal text-cream hover:bg-grey-700',
        'coral' => 'bg-coral text-black hover:bg-coral-dark',
        'yellow' => 'bg-yellow text-charcoal hover:bg-yellow-dark',
        'cream' => 'border border-charcoal bg-cream text-charcoal hover:bg-paper',
        'outline' => 'border-[1.5px] border-charcoal bg-transparent text-charcoal hover:bg-charcoal/5',
    ];

    $sizes = [
        'sm' => 'px-3.5 py-1.5 text-sm',
        'md' => 'px-5 py-2.5 text-base',
        'lg' => 'px-7 py-3.5 text-lg',
    ];

    $classes = [
        'press inline-flex items-center justify-center gap-2 rounded-pill font-semibold leading-tight whitespace-nowrap no-underline',
        'disabled:cursor-not-allowed disabled:bg-grey-300 disabled:text-grey-600 aria-disabled:cursor-not-allowed aria-disabled:bg-grey-300 aria-disabled:text-grey-600',
        $tones[$tone],
        $sizes[$size],
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
