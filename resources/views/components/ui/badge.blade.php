{{-- Small status pill. Every tone keeps AA contrast for its text. --}}
@props(['tone' => 'neutral'])

@php
    $tones = [
        'neutral' => 'bg-grey-200 text-charcoal',
        'green' => 'bg-green-soft text-green',
        'yellow' => 'bg-yellow-pale text-charcoal',
        'coral' => 'bg-coral-light text-black',
        'dark' => 'bg-charcoal text-cream',
        'muted' => 'bg-grey-100 text-grey-700',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-pill px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap', $tones[$tone]]) }}>{{ $slot }}</span>
