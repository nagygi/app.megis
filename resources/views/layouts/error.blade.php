{{--
    Branded error page (15 · 15.8). Deliberately free of session, auth and
    Livewire, so it still renders when those are what failed.
--}}
@props(['title', 'message', 'symbol' => 'spiral'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · {{ __('ui.brand') }}</title>
    @fonts
    @vite(['resources/css/app.css'])
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
