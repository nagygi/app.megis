{{-- The mégis wordmark. Variants: red-dark, red-light, black, white, yellow-light. --}}
@props(['variant' => 'red-dark'])

<span
    role="img"
    aria-label="{{ __('ui.brand') }}"
    {{ $attributes->class(['megis-logo', 'megis-logo-'.$variant, 'h-8']) }}
></span>
