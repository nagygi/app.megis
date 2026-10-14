{{-- Public pages: mobile first, with SEO and Open Graph tags (NFR · 9, 15 · 15.8). --}}
@props([
    'title' => null,
    'description' => null,
    'noindex' => false,
    // "coral": the Értéktükör opening (06 · ET1) speaks on the brand colour.
    'tone' => 'cream',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-seo :title="$title" :description="$description" :noindex="$noindex" />
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body @class([
    'min-h-dvh text-charcoal',
    'bg-cream' => $tone === 'cream',
    'bg-coral' => $tone === 'coral',
])>
    <a href="#main" class="sr-only rounded-pill bg-charcoal px-4 py-2 text-cream focus:not-sr-only focus:absolute focus:top-3 focus:left-3">
        {{ __('ui.skip_to_content') }}
    </a>

    <header class="mx-auto flex max-w-5xl items-center px-4 py-5 sm:px-8">
        <a href="{{ route('public.home') }}" class="inline-flex">
            <x-megis.logo />
        </a>
    </header>

    <main id="main" class="mx-auto max-w-5xl px-4 pb-16 sm:px-8">
        {{ $slot }}
    </main>

    @isset($footer)
        <footer @class([
            'mx-auto max-w-5xl border-t px-4 py-6 text-sm sm:px-8',
            'border-grey-250 text-grey-600' => $tone === 'cream',
            'border-black/20 text-black' => $tone === 'coral',
        ])>
            {{ $footer }}
        </footer>
    @endisset

    @livewireScriptConfig
</body>
</html>
