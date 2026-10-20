{{--
    Pill button of the design system. Renders a link when href is given.

    One role, one look:
    - dark: the primary action (Tovább, Mentem, Hozzáadom); lg with an arrow in a flow;
    - outline: the secondary one (Mégse, Kihagyom, Később);
    - ghost: a tertiary text action (Szerkesztem, Bővebben);
    - danger: deleting, withdrawing, archiving, always confirmed first;
    - coral, yellow, cream: accents, used sparingly.

    Hover makes the fill lighter, never darker (_ds motion). Tones keep WCAG AA
    contrast: cream text on coral would only reach 3:1, so coral uses black text.
    While Livewire runs the button's request it carries data-loading: it dims,
    takes no clicks and shows a spinner. A disabled link (aria-disabled) loses
    its href, so it cannot be followed either.
--}}
@props([
    'tone' => 'dark',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'icon' => null,
    'iconRight' => null,
])

@php
    $tones = [
        'dark' => 'bg-charcoal text-cream hover:bg-green',
        'coral' => 'bg-coral text-black hover:bg-coral-light',
        'yellow' => 'bg-yellow text-charcoal hover:bg-yellow-light',
        'cream' => 'border border-charcoal bg-cream text-charcoal hover:bg-white',
        'outline' => 'border-[1.5px] border-charcoal bg-transparent text-charcoal hover:bg-yellow-pale',
        'danger' => 'border-[1.5px] border-coral-ink bg-transparent text-coral-ink hover:bg-coral-light hover:text-black',
        'ghost' => 'link bg-transparent text-charcoal',
    ];

    $sizes = $tone === 'ghost'
        ? ['sm' => 'min-h-10 px-1.5 text-sm', 'md' => 'min-h-11 px-1.5 text-base', 'lg' => 'min-h-13 px-2 text-lg']
        : ['sm' => 'min-h-10 px-4 py-2 text-sm', 'md' => 'min-h-11 px-5 py-2.5 text-base', 'lg' => 'min-h-13 px-7 py-3.5 text-lg'];

    $iconSize = $size === 'lg' ? 'size-5' : 'size-4';
    $disabledLink = $href && in_array($attributes->get('aria-disabled'), [true, 'true'], true);

    $classes = [
        'press group/button inline-flex items-center justify-center gap-2 rounded-pill font-semibold leading-tight whitespace-nowrap',
        'no-underline' => $tone !== 'ghost',
        'data-loading:pointer-events-none data-loading:cursor-wait data-loading:opacity-70',
        'disabled:not-data-loading:cursor-not-allowed disabled:not-data-loading:border-grey-300 disabled:not-data-loading:bg-grey-300 disabled:not-data-loading:text-grey-600',
        'aria-disabled:cursor-not-allowed aria-disabled:border-grey-300 aria-disabled:bg-grey-300 aria-disabled:text-grey-600',
        $tones[$tone],
        $sizes[$size],
    ];
@endphp

@if ($href)
    <a @unless ($disabledLink) href="{{ $href }}" @else role="link" @endunless {{ $attributes->class($classes) }}>
        @if ($icon)
            <x-megis.icon :name="$icon" :class="$iconSize" />
        @endif
        {{ $slot }}
        @if ($iconRight)
            <x-megis.icon :name="$iconRight" :class="$iconSize" />
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        <span aria-hidden="true" class="hidden size-4 shrink-0 animate-spin rounded-full border-2 border-current border-r-transparent group-data-loading/button:inline-block"></span>
        @if ($icon)
            <x-megis.icon :name="$icon" :class="$iconSize.' group-data-loading/button:hidden'" />
        @endif
        {{ $slot }}
        @if ($iconRight)
            <x-megis.icon :name="$iconRight" :class="$iconSize" />
        @endif
    </button>
@endif
