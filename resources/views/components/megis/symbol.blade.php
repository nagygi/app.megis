{{--
    One of the twelve hand-drawn living-systems symbols (_ds symbols), painted in currentColor.
    Charcoal on light grounds, yellow on dark grounds.
--}}
@props(['name', 'label' => null])

<span
    {{ $attributes->class(['megis-mask', 'megis-symbol-'.$name, 'size-8']) }}
    @if ($label)
        role="img" aria-label="{{ $label }}"
    @else
        aria-hidden="true"
    @endif
></span>
