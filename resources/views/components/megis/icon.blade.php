{{--
    A brand system icon (_ds icons), painted in currentColor, 16px unless the
    class gives a size (the _ds minimum is 16px).
    Decorative by default; pass a label when the icon carries meaning on its own.
--}}
@props(['name', 'label' => null])

<span
    {{ $attributes->class(['megis-mask', 'megis-icon-'.$name, 'size-4' => ! preg_match('/(?<![\w-])size-/', (string) $attributes->get('class'))]) }}
    @if ($label)
        role="img" aria-label="{{ $label }}"
    @else
        aria-hidden="true"
    @endif
></span>
