{{--
    A wait in words, its three dots lighting up one after the other, so it is
    clear the work goes on and the page has not frozen (MEGIS-5). The text's
    own "…" gives way to the dots; a screen reader gets the text as it is.
    Without motion (prefers-reduced-motion) the three dots stand still.
    - text: the wait, e.g. "Olvassuk, amit mondtál…".
--}}
@props(['text'])

@php
    $words = preg_replace('/\s*(?:…|\.{3})$/u', '', $text);
@endphp

<span {{ $attributes }}><span aria-hidden="true">{{ $words }}<span class="working-dots"><span>.</span><span>.</span><span>.</span></span></span><span class="sr-only">{{ $text }}</span></span>
