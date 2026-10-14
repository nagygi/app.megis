{{--
    A brand system icon (_ds icons), painted in currentColor.
    Decorative by default; pass a label when the icon carries meaning on its own.
--}}
@props(['name', 'label' => null])

<span
    {{ $attributes->class(['megis-mask', 'megis-icon-'.$name, 'size-4']) }}
    @if ($label)
        role="img" aria-label="{{ $label }}"
    @else
        aria-hidden="true"
    @endif
></span>
