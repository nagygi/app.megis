{{--
    The Vissza of an admin page: back to the admin page it was opened from,
    the fallback (its list) when it was opened from outside (AdminBackLink).
    home: the URL its tabs hang under. The label names the fallback, so it
    shows only when the link leads there.
--}}
@props([
    'fallback',
    'home' => null,
    'label' => null,
])

@php
    $href = app(\App\Support\AdminBackLink::class)->url($fallback, $home);
    $toFallback = parse_url($href, PHP_URL_PATH) === parse_url($fallback, PHP_URL_PATH);
@endphp

<a href="{{ $href }}" {{ $attributes->class('-ml-1 inline-flex min-h-10 items-center gap-1 self-start text-sm font-semibold text-charcoal no-underline hover:underline') }}>
    <x-megis.icon name="chevron-left" />
    {{ $label !== null && $toFallback ? $label : __('ui.back') }}
</a>
