{{--
    Admin layout (01). The header is charcoal globally and green inside an
    organization, with the organization's name and tabs (02 · 2.3).
--}}
@props([
    'title' => null,
    'organization' => null,
])

@php
    $admin = auth('admin')->user();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ? $title.' · ' : '' }}{{ __('admin.title') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body class="min-h-dvh bg-grey-100 text-charcoal">
    <a href="#main" class="sr-only rounded-pill no-underline bg-charcoal px-4 py-2 text-cream focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50">
        {{ __('ui.skip_to_content') }}
    </a>

    {{--
        Below 1024px the menu is a drawer: closed it is moved out and invisible,
        so its links leave the tab order; open, the focus stays inside and Esc
        or the backdrop closes it.
    --}}
    <div x-data="{ navOpen: false }" @keydown.escape.window="navOpen = false" class="lg:flex lg:min-h-dvh">
        <aside
            id="admin-nav"
            class="fixed inset-y-0 left-0 z-40 w-64 overflow-y-auto border-r border-grey-250 bg-white transition-[translate] duration-200 ease-standard max-lg:invisible max-lg:-translate-x-full lg:static"
            :class="{ 'max-lg:invisible max-lg:-translate-x-full': ! navOpen }"
            x-trap.noscroll="navOpen"
        >
            @include('admin.partials.nav')
        </aside>

        <div x-show="navOpen" x-cloak class="fixed inset-0 z-30 bg-charcoal/40 lg:hidden" @click="navOpen = false"></div>

        <div class="flex min-w-0 flex-1 flex-col">
            <header @class([
                'on-dark text-cream',
                'bg-green' => $organization,
                'bg-charcoal' => ! $organization,
            ])>
                <div class="flex items-center gap-3 px-4 py-3 lg:px-8">
                    <button
                        type="button"
                        class="press -ml-2 grid size-11 place-items-center rounded-pill hover:text-yellow lg:hidden"
                        aria-controls="admin-nav"
                        :aria-expanded="navOpen"
                        @click="navOpen = ! navOpen"
                    >
                        <x-megis.icon name="menu" class="size-5" :label="__('admin.menu_open')" />
                    </button>

                    <div class="min-w-0 flex-1">
                        @if ($organization)
                            <p class="text-sm opacity-90">{{ __('admin.nav.organizations') }}</p>
                            <p class="truncate text-lg font-semibold">{{ $organization->name }}</p>
                        @else
                            <p class="truncate text-lg font-semibold">{{ $title }}</p>
                        @endif
                    </div>

                    @if ($admin)
                        <div x-data="{ open: false }" class="relative" @keydown.escape.window="open = false">
                            <button
                                type="button"
                                class="press -mr-1 grid size-11 place-items-center rounded-pill"
                                aria-haspopup="true"
                                :aria-expanded="open"
                                @click="open = ! open"
                            >
                                <span class="grid size-9 place-items-center rounded-pill bg-cream text-xs font-extrabold text-charcoal">{{ $admin->initials }}</span>
                                <span class="sr-only">{{ __('admin.user_menu.open') }}</span>
                            </button>

                            <div
                                x-show="open"
                                x-cloak
                                @click.outside="open = false"
                                class="absolute right-0 z-50 mt-2 w-64 rounded-card border border-grey-250 bg-white p-3 text-charcoal"
                            >
                                <p class="font-semibold">{{ $admin->name }}</p>
                                <p class="mt-1">
                                    <x-ui.badge :tone="$admin->isSuperadmin() ? 'dark' : 'neutral'">{{ $admin->role->label() }}</x-ui.badge>
                                </p>
                                <ul class="mt-3 flex flex-col gap-1 border-t border-grey-200 pt-3">
                                    <li>
                                        <a href="{{ route('admin.profile') }}" class="block rounded-field px-2 py-1.5 text-charcoal no-underline hover:bg-grey-100">{{ __('admin.user_menu.profile') }}</a>
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('admin.logout') }}">
                                            @csrf
                                            <button type="submit" class="w-full rounded-field px-2 py-1.5 text-left hover:bg-grey-100">{{ __('admin.user_menu.logout') }}</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>

                @if ($organization)
                    <nav class="px-4 lg:px-8" aria-label="{{ $organization->name }}">
                        <x-admin.organization-tabs :organization="$organization" />
                    </nav>
                @endif
            </header>

            {{-- Livewire pages show their own flash messages, so they update after actions too. --}}
            <main id="main" class="flex-1 px-4 py-6 lg:px-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-ui.toasts />
    <x-ui.confirm-dialog />

    @livewireScriptConfig
</body>
</html>
