@props(['tagline' => null])
@if ($tagline)
{{ $tagline }}

@endif
{{ $slot }}
