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
    <a href="#main" class="sr-only rounded-pill no-underline bg-charcoal px-4 py-2 text-cream focus:not-sr-only focus:absolute focus:top-3 focus:left-3">
        {{ __('ui.skip_to_content') }}
    </a>

    {{--
        Decorations may bleed past the content column; this full-width box clips them.
        Not the body: its overflow passes to the viewport, and Chrome on Android still
        lets the page pan sideways over what the viewport hides.
    --}}
    <div class="overflow-x-clip">
        <header class="mx-auto flex max-w-5xl items-center px-4 py-5 sm:px-8">
            <a href="{{ route('public.home') }}" class="inline-flex no-underline">
                <x-megis.logo />
            </a>
        </header>

        <main id="main" class="mx-auto max-w-5xl px-4 pb-16 sm:px-8">
            {{ $slot }}
        </main>

        <x-public.legal-links :tone="$tone" />
    </div>

    <x-ui.toasts />
    <x-ui.confirm-dialog />

    @livewireScriptConfig
</body>
</html>
