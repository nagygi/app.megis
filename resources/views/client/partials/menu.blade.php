{{--
    The customer menu (15 · 15.3). Menu items appear only once their feature
    exists, the user may use it and it has content; the diagnosis tickets add
    them. At the bottom: the organization row (15 · 15.4), Profil, Kijelentkezés.
--}}
@php
    $currentOrganization = app(\App\Support\CurrentOrganization::class)->get()
        ?? app(\App\Services\Auth\LandingResolver::class)->organizationFor($user);
    $organizations = $user->accessibleOrganizations()->orderBy('organizations.name')->get();
@endphp

<div class="flex min-h-full flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        <x-megis.logo class="h-8" />
        <button type="button" class="rounded-pill p-2 desktop:hidden" @click="menuOpen = false">
            <x-megis.icon name="close" class="size-5" :label="__('client.menu.close')" />
        </button>
    </div>

    <nav class="flex-1" aria-label="{{ __('client.menu.title') }}">
        @if ($currentOrganization)
            @php
                $menuItems = [
                    ['route' => 'client.mirror.record', 'active' => 'client.mirror.*', 'label' => __('client.menu.mirror')],
                    ['route' => 'client.values', 'active' => 'client.values', 'label' => __('client.menu.value_library')],
                ];
            @endphp
            {{-- Riportok, Fejezetek, Bölcsességtár: added by tickets 27, 32. --}}
            <ul class="flex flex-col gap-1">
                @foreach ($menuItems as $item)
                    @php($isActive = request()->routeIs($item['active']))
                    <li>
                        <a
                            href="{{ route($item['route'], $currentOrganization) }}"
                            @class([
                                'block rounded-lg px-4 py-2 no-underline',
                                'bg-charcoal font-semibold text-cream' => $isActive,
                                'text-charcoal hover:bg-grey-100' => ! $isActive,
                            ])
                            @if ($isActive) aria-current="page" @endif
                        >{{ $item['label'] }}</a>
                    </li>
                @endforeach
            </ul>
        @endif
    </nav>

    <div class="flex flex-col gap-3 border-t border-grey-250 pt-4">
        @if ($currentOrganization)
            @if ($organizations->count() > 1)
                <div x-data="{ open: false }">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-2 rounded-card bg-grey-100 px-4 py-3 text-left"
                        :aria-expanded="open"
                        aria-controls="organization-switcher"
                        @click="open = ! open"
                    >
                        <span>
                            <span class="block font-semibold">{{ $currentOrganization->name }}</span>
                            <span class="block text-sm text-grey-600">{{ __('client.menu.switch_organization') }}</span>
                        </span>
                        <x-megis.icon name="chevron-right" class="size-4 transition-transform" ::class="open && 'rotate-90'" />
                    </button>

                    <ul id="organization-switcher" x-show="open" x-cloak class="mt-2 flex flex-col gap-1">
                        @foreach ($organizations as $organization)
                            <li>
                                <a
                                    href="{{ route('client.home', $organization) }}"
                                    class="block rounded-lg px-4 py-2 text-charcoal no-underline hover:bg-grey-100"
                                    @if ($organization->is($currentOrganization)) aria-current="true" @endif
                                >{{ $organization->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="rounded-card bg-grey-100 px-4 py-3">
                    <span class="block font-semibold">{{ $currentOrganization->name }}</span>
                </div>
            @endif

            <a href="{{ route('client.profile', $currentOrganization) }}" class="rounded-lg px-4 py-2 text-charcoal no-underline hover:bg-grey-100">{{ __('client.menu.profile') }}</a>
        @endif

        <form method="POST" action="{{ route('client.logout') }}">
            @csrf
            <button type="submit" class="w-full rounded-lg px-4 py-2 text-left hover:bg-grey-100">{{ __('client.menu.logout') }}</button>
        </form>
    </div>
</div>
