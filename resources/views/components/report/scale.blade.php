{{--
    The PHI scale 1–6 with the value marked, and the step's name (27 · 27.2, 27.5).
--}}
@props(['value' => null])

@php($whole = $value === null ? null : (int) floor($value + 0.5))

<div {{ $attributes->class(['flex flex-col gap-1']) }}>
    <ol class="grid grid-cols-6 gap-1" aria-label="{{ __('report.band', ['step' => $whole === null ? '–' : $whole.' '.__('report.scale.'.$whole)]) }}">
        @foreach (range(1, 6) as $number)
            <li @class([
                'h-2 rounded-pill',
                'bg-charcoal' => $whole !== null && $number <= $whole,
                'bg-grey-200' => $whole === null || $number > $whole,
            ])></li>
        @endforeach
    </ol>
    <p class="flex justify-between text-xs text-grey-700">
        <span>1 {{ __('report.scale.1') }}</span>
        @if ($whole !== null && $whole > 1 && $whole < 6)
            <span class="font-semibold text-charcoal">{{ $whole }} {{ __('report.scale.'.$whole) }}</span>
        @endif
        <span>6 {{ __('report.scale.6') }}</span>
    </p>
</div>
