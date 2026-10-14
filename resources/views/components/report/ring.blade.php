{{--
    The one ring of the report: Belső működés with its %, a coral mark at the
    80% optimum (18 · 18.4, 27 · 27.2). Decorative; the numbers are in text next to it.
--}}
@props(['percent' => null, 'size' => 'size-44'])

@php
    $radius = 54;
    $circumference = 2 * M_PI * $radius;
    $filled = $percent === null ? 0 : max(0, min(100, $percent)) / 100;
    $optimum = deg2rad(0.8 * 360 - 90);
    [$x1, $y1] = [60 + 47 * cos($optimum), 60 + 47 * sin($optimum)];
    [$x2, $y2] = [60 + 61 * cos($optimum), 60 + 61 * sin($optimum)];
@endphp

<div {{ $attributes->class(['relative shrink-0', $size]) }}>
    <svg viewBox="0 0 120 120" class="size-full" aria-hidden="true">
        <circle cx="60" cy="60" r="{{ $radius }}" fill="none" stroke-width="10" class="stroke-grey-200" />
        <circle
            cx="60" cy="60" r="{{ $radius }}" fill="none" stroke-width="10" stroke-linecap="round"
            class="stroke-green"
            stroke-dasharray="{{ $circumference }}"
            stroke-dashoffset="{{ $circumference * (1 - $filled) }}"
            transform="rotate(-90 60 60)"
        />
        <line x1="{{ $x1 }}" y1="{{ $y1 }}" x2="{{ $x2 }}" y2="{{ $y2 }}" stroke-width="3" class="stroke-coral" />
    </svg>
    <span class="absolute inset-0 grid place-items-center text-3xl font-extrabold tabular-nums">{{ $percent === null ? '–' : $percent.'%' }}</span>
</div>
