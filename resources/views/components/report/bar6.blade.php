{{--
    The six-step mini bar of a row (prototype .s6): the whole value filled,
    charcoal, coral up to 2 (csak szavak); the sixth step dashed yellow until
    reached. Decorative: the number stands next to it.
--}}
@props(['value' => null, 'inactive' => false])

@php
    $whole = $value === null ? 0 : max(0, min(6, (int) floor($value + 0.5)));
    $fill = $whole <= 2 ? 'bg-coral' : 'bg-charcoal';
@endphp

<span {{ $attributes->class(['flex w-21 shrink-0 gap-0.5']) }} aria-hidden="true">
    @foreach (range(1, 6) as $step)
        <span @class([
            'h-[7px] flex-1 rounded-pill',
            $fill => ! $inactive && $step <= $whole,
            'bg-grey-200' => $inactive || ($step > $whole && $step < 6),
            'border border-dashed border-yellow-dark' => ! $inactive && $step === 6 && $whole < 6,
        ])></span>
    @endforeach
</span>
