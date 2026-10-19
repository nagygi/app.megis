{{--
    Branded error page (15 · 15.8). Deliberately free of session, auth and
    Livewire, so it still renders when those are what failed. Free of the Vite
    build too: during a deploy `npm run build` leaves public/build empty for a
    while, and the 503 page shows exactly then. Without a manifest a few inline
    rules stand in for the stylesheet.
--}}
@props(['title', 'message', 'symbol' => 'spiral'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · {{ __('ui.brand') }}</title>
    @if (Vite::isRunningHot() || Vite::manifestHash() !== null)
        @fonts
        @vite(['resources/css/app.css'])
    @else
        <style>
            body { margin: 0; background: #fffef8; color: #3a3a3a; font-family: ui-sans-serif, system-ui, sans-serif; }
            main { box-sizing: border-box; display: flex; flex-direction: column; justify-content: center; gap: 1.5rem; max-width: 36rem; min-height: 100dvh; margin: 0 auto; padding: 4rem 1rem; }
            h1 { margin: 0; font-size: 1.875rem; font-weight: 800; line-height: 1.25; text-transform: uppercase; }
            p { margin: 0; font-size: 1.125rem; }
            a { color: inherit; font-weight: 600; text-decoration-thickness: 2px; text-underline-offset: 3px; }
        </style>
    @endif
</head>
<body class="min-h-dvh bg-cream text-charcoal">
    <main class="mx-auto flex min-h-dvh max-w-xl flex-col justify-center gap-6 px-4 py-16 sm:px-8">
        <x-megis.symbol :name="$symbol" class="size-16 text-charcoal" />
        <h1 class="text-3xl font-extrabold uppercase leading-tight">{{ $title }}</h1>
        <p class="text-lg">{{ $message }}</p>
        {{ $slot }}
    </main>
</body>
</html>
