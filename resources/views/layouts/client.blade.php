{{--
    Frame of every customer page (15). Mobile first; from 1200px the menu stays
    open on the side. The header colour is set by the page (15 · 15.2).
--}}
@props([
    'title',
    'color' => 'cream',
    'back' => null,
    'backLabel' => null,
    'subtitle' => null,
    'guest' => false,
])

@php
    $headerClasses = [
        'charcoal' => 'bg-charcoal text-cream on-dark',
        'green' => 'bg-green text-cream on-dark',
        'light-yellow' => 'bg-yellow-light text-charcoal',
        'cream' => 'border-b border-grey-250 bg-cream text-charcoal',
        'cream-line' => 'border-b-[4.5px] border-yellow bg-cream text-charcoal',
        // Cream on coral would only reach 3:1, so coral headers use black text.
        'coral' => 'bg-coral text-black',
        'white' => 'border-b border-grey-250 bg-white text-charcoal',
    ][$color];

    $user = $guest ? null : auth('web')->user();
    $impersonation = $guest ? null : app(\App\Services\Auth\ImpersonationService::class)->current();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#3a3a3a">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/pwa/icon-192.png') }}">
    <title>{{ $title }} · {{ __('ui.brand') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body class="min-h-dvh bg-cream text-charcoal">
    <a href="#main" class="sr-only rounded-pill bg-charcoal px-4 py-2 text-cream focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50">
        {{ __('ui.skip_to_content') }}
    </a>

    @if ($impersonation)
        <div class="sticky top-0 z-40 flex items-center justify-center gap-3 bg-coral px-4 py-2 text-sm font-semibold text-black" role="status">
            <span>{{ __('impersonation.banner', ['name' => $impersonation->user->name]) }}</span>
            <form method="POST" action="{{ route('client.impersonation.end') }}">
                @csrf
                <button type="submit" class="underline">{{ __('impersonation.exit') }}</button>
            </form>
        </div>
    @endif

    <div x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false" class="desktop:flex desktop:min-h-dvh">
        @if ($user)
            <aside
                id="client-menu"
                class="fixed inset-y-0 right-0 z-30 w-80 max-w-full translate-x-full overflow-y-auto border-l border-grey-250 bg-white transition-transform desktop:static desktop:order-first desktop:translate-x-0 desktop:border-r desktop:border-l-0"
                :class="menuOpen && 'translate-x-0'"
                aria-label="{{ __('client.menu.title') }}"
            >
                @include('client.partials.menu', ['user' => $user])
            </aside>

            <div x-show="menuOpen" x-cloak class="fixed inset-0 z-20 bg-charcoal/40 desktop:hidden" @click="menuOpen = false"></div>
        @endif

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="{{ $headerClasses }}">
                <div class="mx-auto flex max-w-3xl flex-col gap-3 px-6 pt-3 pb-4 desktop:max-w-5xl">
                    <div class="flex min-h-10 items-center gap-3">
                        @if ($back)
                            <a href="{{ $back }}" class="inline-flex items-center gap-1 text-sm no-underline opacity-90 [color:inherit]">
                                <span aria-hidden="true">‹</span> {{ $backLabel ?? __('ui.back') }}
                            </a>
                        @else
                            <x-megis.logo :variant="in_array($color, ['charcoal', 'green'], true) ? 'white' : 'red-dark'" class="h-7" />
                        @endif

                        <span class="flex-1"></span>

                        @if ($user)
                            <button
                                type="button"
                                class="grid size-9 place-items-center rounded-pill bg-charcoal text-xs font-bold text-cream ring-2 ring-cream desktop:hidden"
                                aria-controls="client-menu"
                                :aria-expanded="menuOpen"
                                @click="menuOpen = ! menuOpen"
                            >
                                <span aria-hidden="true">{{ $user->initials }}</span>
                                <span class="sr-only">{{ __('client.menu.open') }}</span>
                            </button>
                        @endif
                    </div>

                    <div>
                        <h1 class="text-2xl leading-tight font-extrabold uppercase [text-wrap:pretty]">{{ $title }}</h1>
                        @if ($subtitle)
                            <p class="mt-1 text-sm opacity-90">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>
            </header>

            <main id="main" class="mx-auto w-full max-w-3xl flex-1 px-6 py-6 desktop:max-w-5xl">
                <x-ui.flash class="mb-6" />
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScriptConfig
</body>
</html>
