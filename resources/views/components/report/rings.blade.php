{{--
    The rings of R0 and S1 (27 · 27.2): Belső működés green outside with a
    coral mark at the 80% optimum; on R0 the Brand ring inside, a dashed
    track until the Brand riport is built. No text inside: the legend next
    to it carries the numbers.
--}}
@props(['percent' => null, 'brand' => true])

@php
    $circumference = 2 * M_PI * 56;
    $filled = $circumference * max(0, min(100, $percent ?? 0)) / 100;
    $optimum = deg2rad(0.8 * 360 - 90);
@endphp

<svg {{ $attributes->class(['size-28 shrink-0']) }} viewBox="0 0 136 136" aria-hidden="true">
    <circle cx="68" cy="68" r="56" fill="none" stroke-width="13" class="stroke-grey-200" />
    @if ($percent !== null)
        <circle cx="68" cy="68" r="56" fill="none" stroke-width="13" stroke-linecap="round" class="stroke-green" stroke-dasharray="{{ round($filled, 2) }} {{ round($circumference, 2) }}" transform="rotate(-90 68 68)" />
    @endif
    <line x1="{{ round(68 + 47 * cos($optimum), 1) }}" y1="{{ round(68 + 47 * sin($optimum), 1) }}" x2="{{ round(68 + 65 * cos($optimum), 1) }}" y2="{{ round(68 + 65 * sin($optimum), 1) }}" stroke-width="2.4" class="stroke-coral" />
    @if ($brand)
        <circle cx="68" cy="68" r="38" fill="none" stroke-width="2" stroke-dasharray="4 5" class="stroke-grey-400" />
    @endif
</svg>
