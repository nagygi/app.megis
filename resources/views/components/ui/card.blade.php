{{--
    Card: the flat surface of the identity, no shadow (elevation comes from
    contrast). Every white box of the app is one of these.
    - tone: white (default), paper, cream;
    - bordered: the grey hairline, on by default: white on the cream ground
      has no contrast of its own. Off only for a card with a border of its own;
    - padding: none, sm (16px, list items), md (20px, default), lg (24px, forms);
    - as: the element, e.g. section, li, article, form.
--}}
@props([
    'tone' => 'white',
    'bordered' => true,
    'padding' => 'md',
    'as' => 'div',
])

@php
    $classes = [
        'rounded-card',
        ['white' => 'bg-white', 'paper' => 'bg-paper', 'cream' => 'bg-cream'][$tone],
        'border border-grey-250' => $bordered,
        ['none' => '', 'sm' => 'p-4', 'md' => 'p-5', 'lg' => 'p-6'][$padding],
    ];
@endphp

<{{ $as }} {{ $attributes->class($classes) }}>{{ $slot }}</{{ $as }}>
