{{--
    One of the twelve hand-drawn living-systems symbols (_ds symbols), painted in currentColor.
    Charcoal on light grounds, yellow on dark grounds. 32px unless the class gives a size.
--}}
@props(['name', 'label' => null])

<span
    {{ $attributes->class(['megis-mask', 'megis-symbol-'.$name, 'size-8' => ! preg_match('/(?<![\w-])size-/', (string) $attributes->get('class'))]) }}
    @if ($label)
        role="img" aria-label="{{ $label }}"
    @else
        aria-hidden="true"
    @endif
></span>
