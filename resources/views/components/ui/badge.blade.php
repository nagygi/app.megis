{{-- Small status pill. Every tone keeps AA contrast for its text. With `dot` a coloured dot leads the label. --}}
@props(['tone' => 'neutral', 'dot' => false])

@php
    $tones = [
        'neutral' => 'bg-grey-200 text-charcoal',
        'green' => 'bg-green-soft text-green',
        'yellow' => 'bg-yellow-pale text-charcoal',
        'coral' => 'bg-coral-light text-black',
        'dark' => 'bg-charcoal text-cream',
        'muted' => 'bg-grey-100 text-grey-700',
    ];
    $dots = [
        'neutral' => 'bg-grey-500',
        'green' => 'bg-green',
        'yellow' => 'bg-yellow-dark',
        'coral' => 'bg-coral-dark',
        'dark' => 'bg-coral',
        'muted' => 'bg-grey-500',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-pill px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap', 'gap-1.5' => $dot, $tones[$tone]]) }}>@if ($dot)<span class="size-1.5 shrink-0 rounded-full {{ $dots[$tone] }}" aria-hidden="true"></span>@endif{{ $slot }}</span>
