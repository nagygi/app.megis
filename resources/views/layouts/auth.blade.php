{{-- Admin login and password pages: a single centred card, never indexed. --}}
@props(['title'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · {{ __('admin.title') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body class="min-h-dvh bg-charcoal text-charcoal">
    <main class="mx-auto flex min-h-dvh max-w-md flex-col justify-center gap-6 px-4 py-12">
        <x-megis.logo variant="white" class="h-10 self-start" />
        <div class="rounded-card bg-cream p-6 sm:p-8">
            <h1 class="mb-6 text-2xl font-extrabold uppercase">{{ $title }}</h1>
            <x-ui.flash inline class="mb-4" />
            {{ $slot }}
        </div>
    </main>
    <x-ui.toasts />
    <x-ui.confirm-dialog />

    @livewireScriptConfig
</body>
</html>
