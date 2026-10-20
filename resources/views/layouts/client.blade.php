{{--
    Frame of every customer page (15). Mobile first; from 1200px the menu stays
    open on the side. The header colour is set by the page (15 · 15.2).
    As in the prototype: the avatar opens the user menu, the round button next
    to it the menu.
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
            {{-- Alpine takes translate-x-full off when it opens; adding translate-x-0 next to it would lose to it in the built CSS. --}}
            <aside
                id="client-menu"
                class="fixed inset-y-0 right-0 z-30 w-80 max-w-full translate-x-full overflow-y-auto border-l border-grey-250 bg-white transition-transform desktop:static desktop:order-first desktop:translate-x-0 desktop:border-r desktop:border-l-0"
                :class="{ 'translate-x-full': ! menuOpen }"
                aria-label="{{ __('client.menu.title') }}"
            >
                @include('client.partials.menu', ['user' => $user, 'currentOrganization' => $currentOrganization, 'organizations' => $organizations])
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
                            {{-- From 1200px the menu shows the logo. The wrapper hides this one:
                                 .megis-logo sets its display outside the utility layer. --}}
                            <span class="flex desktop:hidden">
                                <x-megis.logo :variant="in_array($color, ['charcoal', 'green'], true) ? 'white' : 'red-dark'" class="h-7" />
                            </span>
                        @endif

                        <span class="min-w-0 flex-1 truncate text-right text-xs font-semibold tracking-wider uppercase tabular-nums">{{ $step ?? $label }}</span>

                        @if ($user)
                            <div x-data="{ userOpen: false }" class="relative" @keydown.escape.window="userOpen = false">
                                <button
                                    type="button"
                                    class="grid size-9 place-items-center rounded-pill text-xs font-bold {{ $circleClasses }}"
                                    aria-haspopup="true"
                                    aria-controls="client-user-menu"
                                    :aria-expanded="userOpen"
                                    @click="userOpen = ! userOpen"
                                >
                                    <span aria-hidden="true">{{ $user->initials }}</span>
                                    <span class="sr-only">{{ __('client.menu.user_menu') }}</span>
                                </button>

                                <div
                                    id="client-user-menu"
                                    x-show="userOpen"
                                    x-cloak
                                    @click.outside="userOpen = false"
                                    class="absolute right-0 z-40 mt-2 w-64 rounded-card border border-grey-250 bg-white p-3 text-charcoal shadow-lg"
                                >
                                    <p class="font-semibold">{{ $user->name }}</p>
                                    @if ($currentOrganization)
                                        <p class="mt-0.5 text-sm text-grey-700">{{ collect([$currentOrganization->name, $position])->filter()->implode(' · ') }}</p>
                                    @endif
                                    <ul class="mt-3 flex flex-col gap-1 border-t border-grey-200 pt-3">
                                        @if ($currentOrganization)
                                            <li>
                                                <a href="{{ route('client.profile', $currentOrganization) }}" class="block rounded-lg px-2 py-1.5 text-charcoal no-underline hover:bg-grey-100">{{ __('client.menu.profile') }}</a>
                                            </li>
                                        @endif
                                        <li>
                                            <form method="POST" action="{{ route('client.logout') }}">
                                                @csrf
                                                <button type="submit" class="w-full rounded-lg px-2 py-1.5 text-left hover:bg-grey-100">{{ __('client.menu.logout') }}</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="grid size-9 place-items-center rounded-pill desktop:hidden {{ $circleClasses }}"
                                aria-controls="client-menu"
                                :aria-expanded="menuOpen"
                                @click="menuOpen = ! menuOpen"
                            >
                                <x-megis.icon name="menu" class="size-4" />
                                <span class="sr-only">{{ __('client.menu.open') }}</span>
                            </button>
                        @endif
                    </div>

                    <div class="flex items-center gap-3">
                        @if ($symbol)
                            <x-megis.symbol :name="$symbol" class="{{ $onDark ? 'text-yellow' : 'text-coral' }}" />
                        @endif
                        <div>
                            <h1 class="text-2xl leading-tight font-extrabold uppercase [text-wrap:pretty]">{{ $title }}</h1>
                            @if ($subtitle)
                                <p class="mt-1 text-sm opacity-90">{{ $subtitle }}</p>
                            @endif
                            @if ($note)
                                <p class="text-sm opacity-80">{{ $note }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </header>

            {{-- Pages show their own flash messages, so Livewire pages update them after actions too. --}}
            <main id="main" class="mx-auto w-full max-w-3xl flex-1 px-6 py-6 desktop:max-w-5xl">
                {{ $slot }}
            </main>
        </div>
    </div>

    @if ($plus)
        <a
            href="{{ $plus }}"
            class="fixed bottom-0 left-1/2 z-20 grid h-14 w-28 -translate-x-1/2 place-items-center rounded-t-full bg-coral pt-2 text-3xl font-light text-black no-underline shadow-lg hover:bg-coral-dark hover:text-cream focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-charcoal"
        >
            <span aria-hidden="true">+</span>
            <span class="sr-only">{{ __('processes.occasions.contribute_title') }}</span>
        </a>
    @endif

    <x-ui.toasts />
    <x-ui.confirm-dialog />

    @livewireScriptConfig
</body>
</html>
