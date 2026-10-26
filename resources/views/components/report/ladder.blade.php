{{--
    The PHI ladder (prototype .lad): six steps, the step reached filled
    (coral up to 2, charcoal above), the sixth dashed yellow until reached,
    and a triangle at the exact value. "0 nincs nyoma · 2 · csak szavak ·
    6 beépült" (27 · 27.2, 27.5).
--}}
@props(['value' => null, 'marker' => 'green'])

@php
    $step = $value === null ? null : max(1, min(6, (int) floor($value + 1e-9)));
    $fill = $step !== null && $step <= 2 ? 'bg-coral' : 'bg-charcoal';
@endphp

<div {{ $attributes->class(['relative pt-4']) }} role="img" aria-label="{{ __('report.band', ['step' => $step === null ? '–' : $step.' '.__('report.scale.'.$step)]) }}">
    @if ($value !== null)
        <span @class([
            'absolute top-0 -ml-1.5 size-0 border-x-[6px] border-t-[9px] border-x-transparent',
            'border-t-green' => $marker === 'green',
            'border-t-charcoal' => $marker === 'charcoal',
        ]) style="left: {{ round(max(0, min(6, $value)) / 6 * 100, 1) }}%"></span>
    @endif
    <span class="flex gap-[3px]">
        @foreach (range(1, 6) as $segment)
            <span @class([
                'h-2.5 flex-1 rounded-pill',
                $fill => $step !== null && $segment <= $step,
                'bg-grey-200' => ($step === null || $segment > $step) && $segment < 6,
                'border-[1.5px] border-dashed border-yellow-dark' => $segment === 6 && ($step === null || $step < 6),
            ])></span>
        @endforeach
    </span>
    <span class="mt-1.5 flex justify-between gap-2 text-xs text-grey-700" aria-hidden="true">
        <span>0 {{ __('report.no_trace_help') }}</span>
        @if ($step !== null)
            <span class="font-semibold text-charcoal">{{ $step }} · {{ __('report.scale.'.$step) }}</span>
        @endif
        <span>6 {{ __('report.scale.6') }}</span>
    </span>
</div>
