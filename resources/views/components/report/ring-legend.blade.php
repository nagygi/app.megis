{{--
    One ring in words next to the rings (R0, S1): the colour, the % large,
    its name, the points and a bar with the coral 80% optimum. "soon" keeps
    the place of a ring not built yet (Brand).
--}}
@props(['tone' => 'green', 'percent' => null, 'label', 'points' => null, 'href' => null, 'soon' => false])

@php($tag = $href && ! $soon ? 'a' : 'div')

<{{ $tag }} @if ($tag === 'a') href="{{ $href }}" @endif {{ $attributes->class(['block text-charcoal no-underline', 'press' => $tag === 'a']) }}>
    <span class="flex items-baseline gap-2">
        <span @class(['size-2.5 shrink-0 rounded-[3px]', 'bg-green' => $tone === 'green', 'border border-dashed border-grey-600' => $tone === 'yellow' && $soon, 'bg-yellow' => $tone === 'yellow' && ! $soon]) aria-hidden="true"></span>
        @if ($soon)
            <x-report.soon />
        @else
            <span class="text-2xl leading-none font-extrabold tabular-nums">{{ $percent === null ? '–' : $percent.'%' }}</span>
        @endif
    </span>
    <span class="mt-1 block text-sm font-semibold">{{ $label }}</span>
    @if ($points)
        <span class="block text-xs text-grey-700">{{ $points }}</span>
    @endif
    <span class="relative mt-1.5 block h-[5px] rounded-pill bg-grey-150" aria-hidden="true">
        @unless ($soon)
            <span class="absolute inset-y-0 left-0 rounded-pill bg-green" style="width: {{ max(0, min(100, $percent ?? 0)) }}%"></span>
        @endunless
        <span class="absolute -top-[3px] left-[80%] h-[11px] w-0.5 bg-coral"></span>
    </span>
</{{ $tag }}>
