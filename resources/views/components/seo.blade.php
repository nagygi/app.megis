{{--
    Title, description, canonical and Open Graph tags for public pages (NFR · 9).
    Outside production every page is noindex.
--}}
@props([
    'title' => null,
    'description' => null,
    'canonical' => null,
    'noindex' => false,
])

@php
    $fullTitle = $title ? $title.' · '.__('ui.seo.title_suffix') : __('ui.seo.title_suffix');
    $description ??= __('ui.seo.default_description');
    $canonical ??= url()->current();
@endphp

<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ $canonical }}">
@if ($noindex || ! app()->isProduction())
    <meta name="robots" content="noindex, nofollow">
@endif
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ __('ui.brand') }}">
<meta property="og:locale" content="hu_HU">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
