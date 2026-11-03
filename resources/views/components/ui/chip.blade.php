{{--
    A short label in an outline pill: the signs of a value (ET4, NM-06), a user's tags in admin tables (sm).
    `strong` gives the sm chip a dark outline and bold text. Not a button; for a choice use x-ui.segment.
--}}
@props(['size' => 'md', 'strong' => false])

<span {{ $attributes->class([
    'inline-flex items-center rounded-pill',
    'border-[1.5px] border-charcoal px-3 py-1.5 text-sm' => $size === 'md',
    'border px-2 py-0.5 text-xs whitespace-nowrap' => $size === 'sm',
    'border-grey-400' => $size === 'sm' && ! $strong,
    'border-charcoal font-semibold' => $size === 'sm' && $strong,
]) }}>{{ $slot }}</span>
