{{--
    A row of the report (prototype .r): the name with a small line under it,
    the six-step bar, the number (coral up to 2) and the chevron. A link, a
    plain row, or "soon" for a place not built yet. The rows are divided by
    a line; the last one has none.
    - meta: the small line ("5-nek látjátok, 2-t mutattok", "6 alkalom …"),
      metaTone coral for the biggest gap or a crack;
    - value: the bar's value (null: no bar); number: the text on the right.
--}}
@props(['href' => null, 'title' => null, 'meta' => null, 'metaTone' => null, 'value' => null, 'bar' => true, 'number' => null, 'low' => false, 'soon' => false, 'pill' => true, 'symbol' => null])

@php($tag = $href && ! $soon ? 'a' : 'div')

<{{ $tag }} @if ($tag === 'a') href="{{ $href }}" @endif {{ $attributes->class([
    'flex items-center gap-2.5 border-b border-grey-200 py-2.5 text-charcoal no-underline last:border-b-0',
    'press hover:bg-paper' => $tag === 'a',
]) }}>
    @if ($symbol)
        <x-megis.symbol :name="$symbol" :class="\Illuminate\Support\Arr::toCssClasses(['size-6 shrink-0', 'text-coral' => $symbol === 'spiral' && ! $soon, 'text-grey-600' => $soon])" />
    @endif
    <span class="min-w-0 flex-1 leading-snug break-words">
        <span @class(['block', 'text-grey-700' => $soon])>{{ $title ?? $slot }}</span>
        @if (filled($meta))
            <span @class(['mt-px block text-xs', 'font-semibold text-coral-ink' => $metaTone === 'coral', 'text-grey-700' => $metaTone !== 'coral'])>{{ $meta }}</span>
        @endif
    </span>
    @if ($soon)
        {{-- In a group that is coming as a whole, the group's label says it once. --}}
        @if ($pill)
            <x-report.soon />
        @else
            <x-report.bar6 inactive />
        @endif
    @else
        @if ($bar)
            <x-report.bar6 :value="$value" />
        @endif
        @if ($number !== null)
            <span @class(['min-w-5 shrink-0 text-right font-extrabold tabular-nums', 'text-coral-ink' => $low])>{{ $number }}</span>
        @endif
    @endif
    @if ($tag === 'a')
        <x-megis.icon name="chevron-right" class="shrink-0 text-grey-600" />
    @endif
</{{ $tag }}>
