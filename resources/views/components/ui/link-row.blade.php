{{--
    A row that leads somewhere: a title, an optional line under it and the
    chevron. Lists of diagnoses, topics, processes, report pages, and the
    choices of a step. A link with href, otherwise a button (wire:click).
    - title: the slot unless given (the slot may hold a badge too);
    - meta: the line under it, a string or a slot;
    - symbol: a brand symbol before the text;
    - trailing: a slot right of the text, before the chevron (e.g. points);
    - tone: white (default), pale (the recommended choice), yellow and green
      (the strong rows of a published report: Márkád alapja, Belső működés);
    - plain: the same card without the chevron, a place that no longer opens
      (what the peer brought into a closed diagnosis, T5).
--}}
@props(['href' => null, 'title' => null, 'meta' => null, 'symbol' => null, 'tone' => 'white', 'plain' => false])

@php
    $tag = $plain ? 'div' : ($href ? 'a' : 'button');
    $classes = [
        'flex w-full items-center justify-between gap-3 rounded-card px-5 py-4 text-left no-underline',
        'press' => ! $plain,
        'border border-grey-250 bg-white text-charcoal' => $tone === 'white',
        'bg-yellow-pale text-charcoal' => $tone === 'pale',
        'bg-yellow text-charcoal' => $tone === 'yellow',
        'on-dark bg-green text-cream' => $tone === 'green',
        'hover:bg-paper' => ! $plain && $tone === 'white',
        'hover:bg-yellow-light' => ! $plain && in_array($tone, ['pale', 'yellow'], true),
        'hover:bg-green-muted' => ! $plain && $tone === 'green',
    ];
@endphp

@if ($tag === 'a')
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
@elseif ($tag === 'button')
    <button type="button" {{ $attributes->class($classes) }}>
@else
    <div {{ $attributes->class($classes) }}>
@endif
    <span class="flex min-w-0 items-center gap-3">
        @if ($symbol)
            <x-megis.symbol :name="$symbol" class="size-8 shrink-0 text-charcoal" />
        @endif
        <span class="min-w-0">
            <span class="block font-semibold">{{ $title ?? $slot }}</span>
            @if ($meta !== null && (string) $meta !== '')
                <span @class(['block text-sm', 'text-grey-700' => $tone !== 'green'])>{{ $meta }}</span>
            @endif
        </span>
    </span>
    <span class="flex shrink-0 items-center gap-2">
        @isset($trailing)
            <span class="text-sm tabular-nums">{{ $trailing }}</span>
        @endisset
        @unless ($plain)
            <x-megis.icon name="chevron-right" />
        @endunless
    </span>
@if ($tag === 'a')
    </a>
@elseif ($tag === 'button')
    </button>
@else
    </div>
@endif
