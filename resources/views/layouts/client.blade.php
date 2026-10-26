{{--
    Frame of every customer page (15). Mobile first; from 1200px the menu stays
    open on the side. The header colour is set by the page (15 · 15.2).
    On phones the round button on the left opens the menu, the avatar on the
    right the user menu.
--}}
@props([
    'title',
    'color' => 'cream',
    'back' => null,
    'backLabel' => null,
    'subtitle' => null,
    // The second, paler line under the subtitle, e.g. "kb. 13 perc · 03.02.-ig" (23 · 23.1).
    'note' => null,
    // A symbol before the title, e.g. the spiral of "Mi vár rád".
    'symbol' => null,
    'guest' => false,
    // "3 / 6" on the filling pages (23 · 23.3).
    'step' => null,
    // [3, 6] draws the step bar under the title (prototype .prog).
    'progress' => null,
    // The page label right of the logo when there is no step, e.g. "Első belépés".
    'label' => null,
    // The coral plus to V11, on the homes of a running diagnosis (15 · 15.5).
    'plus' => null,
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
    $onDark = in_array($color, ['charcoal', 'green'], true);
    // Cream circles on dark headers, charcoal ones on light headers.
    $circleClasses = $onDark ? 'bg-cream text-charcoal' : 'bg-charcoal text-cream';

    $user = $guest ? null : auth('web')->user();
    $impersonation = $guest ? null : app(\App\Services\Auth\ImpersonationService::class)->current();

    $organizations = $user ? $user->accessibleOrganizations()->orderBy('organizations.name')->get() : collect();
    $currentOrganization = $user
        ? (app(\App\Support\CurrentOrganization::class)->get() ?? app(\App\Services\Auth\LandingResolver::class)->organizationFor($user))
        : null;
    $position = $currentOrganization ? $organizations->firstWhere('id', $currentOrganization->id)?->pivot->position : null;
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
    <a href="#main" class="sr-only rounded-pill no-underline bg-charcoal px-4 py-2 text-cream focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50">
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
            {{--
                Below 1200px the closed menu is moved out and invisible, so its links
                leave the tab order; Alpine takes both classes off when it opens, and
                the focus stays inside until it closes. From 1200px it always shows.
                Only the slide is animated: visibility must turn at once, or the focus
                trap finds nothing to focus as it opens.
            --}}
            <aside
                id="client-menu"
                class="fixed inset-y-0 left-0 z-30 w-80 max-w-full overflow-y-auto border-r border-grey-250 bg-white transition-[translate] duration-200 ease-standard max-desktop:invisible max-desktop:-translate-x-full desktop:static desktop:order-first"
                :class="{ 'max-desktop:invisible max-desktop:-translate-x-full': ! menuOpen }"
                x-trap.noscroll="menuOpen"
                aria-label="{{ __('client.menu.title') }}"
            >
                @include('client.partials.menu', ['user' => $user, 'currentOrganization' => $currentOrganization, 'organizations' => $organizations])
            </aside>

            <div x-show="menuOpen" x-cloak class="fixed inset-0 z-20 bg-charcoal/40 desktop:hidden" @click="menuOpen = false"></div>
        @endif

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="{{ $headerClasses }}">
                <div class="mx-auto flex max-w-3xl flex-col gap-3 px-6 pt-3 pb-4 desktop:max-w-5xl">
                    <div class="flex min-h-11 items-center gap-3">
                        @if ($user)
                            {{-- The circles stay 36px; the button around them is the 44px target. --}}
                            <button
                                type="button"
                                class="press -ml-1 grid size-11 place-items-center rounded-pill desktop:hidden"
                                aria-controls="client-menu"
                                :aria-expanded="menuOpen"
                                @click="menuOpen = ! menuOpen"
                            >
                                <span aria-hidden="true" class="grid size-9 place-items-center rounded-pill {{ $circleClasses }}">
                                    <x-megis.icon name="menu" />
                                </span>
                                <span class="sr-only">{{ __('client.menu.open') }}</span>
                            </button>
                        @endif

                        @if ($back)
                            {{-- A 44px target with the chevron; the label gives way before the step.
                                 It reaches into the padding only where it starts the row. --}}
                            <a href="{{ $back }}" @class([
                                'inline-flex min-h-11 min-w-0 items-center gap-1 rounded-pill pr-3 pl-1 text-sm font-semibold no-underline [color:inherit] hover:underline',
                                '-ml-2' => ! $user,
                                'desktop:-ml-2' => $user,
                            ])>
                                <x-megis.icon name="chevron-left" />
                                <span class="truncate">{{ $backLabel ?? __('ui.back') }}</span>
                            </a>
                        @else
                            {{-- From 1200px the menu shows the logo. The wrapper hides this one:
                                 .megis-logo sets its display outside the utility layer. --}}
                            @php($logo = in_array($color, ['charcoal', 'green'], true) ? 'white' : 'red-dark')
                            @if ($currentOrganization)
                                <a href="{{ route('client.home', $currentOrganization) }}" class="flex no-underline desktop:hidden">
                                    <x-megis.logo :variant="$logo" class="h-7" />
                                    <span class="sr-only">{{ __('client.menu.home') }}</span>
                                </a>
                            @else
                                <span class="flex desktop:hidden">
                                    <x-megis.logo :variant="$logo" class="h-7" />
                                </span>
                            @endif
                        @endif

                        <span @class([
                            'flex-1 text-right text-xs font-semibold tracking-wider uppercase tabular-nums',
                            'shrink-0' => $step,
                            'min-w-0 truncate' => ! $step,
                        ])>{{ $step ?? $label }}</span>

                        @if ($user)
                            <x-ui.dropdown id="client-user-menu" :label="__('client.menu.user_menu')" :width="256" trigger-class="press -mr-1 grid size-11 place-items-center rounded-pill">
                                <x-slot:trigger>
                                    <span aria-hidden="true" class="grid size-9 place-items-center rounded-pill text-xs font-extrabold {{ $circleClasses }}">{{ $user->initials }}</span>
                                </x-slot:trigger>

                                <div class="border-b border-grey-200 px-3 pt-1 pb-3">
                                    <p class="font-semibold">{{ $user->name }}</p>
                                    @if ($currentOrganization)
                                        <p class="mt-0.5 text-sm text-grey-700">{{ collect([$currentOrganization->name, $position])->filter()->implode(' · ') }}</p>
                                    @endif
                                </div>
                                @if ($currentOrganization)
                                    <x-ui.dropdown-item :href="route('client.profile', $currentOrganization)" class="mt-1">{{ __('client.menu.profile') }}</x-ui.dropdown-item>
                                @endif
                                <form method="POST" action="{{ route('client.logout') }}">
                                    @csrf
                                    <x-ui.dropdown-item type="submit">{{ __('client.menu.logout') }}</x-ui.dropdown-item>
                                </form>
                            </x-ui.dropdown>
                        @endif
                    </div>

                    <div class="flex items-center gap-3">
                        @if ($symbol)
                            <x-megis.symbol :name="$symbol" class="{{ $onDark ? 'text-yellow' : 'text-coral' }}" />
                        @endif
                        <div>
                            <h1 class="text-2xl leading-tight font-extrabold uppercase [text-wrap:pretty]">{{ $title }}</h1>
                            @if ($subtitle)
                                <p class="mt-1 text-sm">{{ $subtitle }}</p>
                            @endif
                            @if ($note)
                                <p class="text-sm">{{ $note }}</p>
                            @endif
                        </div>
                    </div>

                    @if ($progress)
                        @php([$current, $total] = $progress)
                        <div
                            class="flex gap-1"
                            role="progressbar"
                            aria-valuemin="1"
                            aria-valuemax="{{ $total }}"
                            aria-valuenow="{{ $current }}"
                            aria-label="{{ __('diagnosis.progress', ['current' => $current, 'total' => $total]) }}"
                        >
                            @for ($index = 1; $index <= $total; $index++)
                                <span @class([
                                    'h-1.5 flex-1 rounded-pill',
                                    ($onDark ? 'bg-yellow' : 'bg-charcoal') => $index <= $current,
                                    ($onDark ? 'bg-grey-700' : 'bg-grey-300') => $index > $current,
                                ])></span>
                            @endfor
                        </div>
                    @endif
                </div>
            </header>

            {{-- With the plus button, the page ends above it. --}}
            <main id="main" @class([
                'mx-auto w-full max-w-3xl flex-1 px-6 py-6 desktop:max-w-5xl',
                'pb-28' => $plus,
            ])>
                {{ $slot }}
            </main>
        </div>
    </div>

    @if ($plus)
        {{-- Under the open menu's backdrop (z-20), above the iPhone's home bar. --}}
        <a
            href="{{ $plus }}"
            class="press fixed bottom-0 left-1/2 z-10 grid h-[calc(3.5rem+env(safe-area-inset-bottom))] w-28 -translate-x-1/2 place-items-center rounded-t-full bg-coral pt-2 pb-[env(safe-area-inset-bottom)] text-black no-underline hover:bg-coral-light focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-charcoal"
        >
            <x-megis.icon name="plus" class="size-6" />
            <span class="sr-only">{{ __('processes.occasions.contribute_title') }}</span>
        </a>
    @endif

    <x-ui.toasts />
    <x-ui.confirm-dialog />

    @livewireScriptConfig
</body>
</html>
