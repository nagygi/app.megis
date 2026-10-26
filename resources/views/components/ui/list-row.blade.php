{{--
    A row of a library list (prototype .r): no card, the list divides the rows
    with a line (ul.divide-y divide-grey-200). Értéktár, Folyamatok, Bölcsességtár.
    - href: a link with the chevron; expanded (true/false): a button that opens
      the row in place, its chevron turning; neither: a plain row;
    - title, meta (a string or a slot), symbol before the text;
    - metaTone coral: the meta in coral ink, a state and not an error (M4);
    - score: the number right of the text, scoreLow paints it coral ink.
--}}
@props(['href' => null, 'expanded' => null, 'title' => null, 'meta' => null, 'metaTone' => null, 'symbol' => null, 'score' => null, 'scoreLow' => false])

@php
    $tag = $href ? 'a' : ($expanded !== null ? 'button' : 'div');
    $classes = [
        'flex w-full items-start gap-3 py-3.5 text-left text-charcoal no-underline',
        'press hover:bg-paper' => $tag !== 'div',
    ];
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    @if ($tag === 'button') type="button" aria-expanded="{{ $expanded ? 'true' : 'false' }}" @endif
    {{ $attributes->class($classes) }}
>
    @if ($symbol)
        <x-megis.symbol :name="$symbol" class="size-7 shrink-0 text-charcoal" />
    @endif
    <span class="min-w-0 flex-1 break-words">
        <span class="block font-semibold">{{ $title ?? $slot }}</span>
        @if ($meta !== null && (string) $meta !== '')
            <span @class(['mt-0.5 block text-sm', 'text-coral-ink' => $metaTone === 'coral', 'text-grey-700' => $metaTone !== 'coral'])>{{ $meta }}</span>
        @endif
    </span>
    @if ($score !== null)
        <span @class(['shrink-0 text-base font-extrabold tabular-nums', 'text-coral-ink' => $scoreLow])>{{ $score }}</span>
    @endif
    @if ($href)
        <x-megis.icon name="chevron-right" class="mt-1 shrink-0 text-grey-600" />
    @elseif ($tag === 'button')
        <x-megis.icon name="chevron-down" :class="'mt-1 shrink-0 text-grey-600 transition-transform duration-200'.($expanded ? ' rotate-180' : '')" />
    @endif
</{{ $tag }}>
