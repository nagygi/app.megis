{{--
    The customer menu (15 · 15.3). Menu items appear only once their feature
    exists, the user may use it and it has content; the diagnosis tickets add
    them. At the bottom: the organization row (15 · 15.4), Profil, Kijelentkezés.
    The layout passes the current organization and the user's organizations.
--}}

<div class="flex min-h-full flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        @if ($currentOrganization)
            <a href="{{ route('client.home', $currentOrganization) }}" class="flex no-underline">
                <x-megis.logo class="h-8" />
                <span class="sr-only">{{ __('client.menu.home') }}</span>
            </a>
        @else
            <x-megis.logo class="h-8" />
        @endif
        <button type="button" class="press -mr-2 grid size-11 place-items-center rounded-pill hover:bg-grey-100 desktop:hidden" @click="menuOpen = false">
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

                // Tárak / Folyamatok for the leaders (11 · 11.5, 15 · 15.3).
                if (\App\Services\Processes\ProcessService::canManageLibrary($user, $currentOrganization)) {
                    $menuItems[] = ['route' => 'client.processes', 'active' => 'client.processes*', 'label' => __('client.menu.processes')];
                }
            @endphp
            @php
                // Riportok and Fejezetek after publishing (27 · 27.12).
                $reports = app(\App\Services\Report\ReportService::class)->reportsFor($user, $currentOrganization);
                $latestReport = $reports->first();
            @endphp
            @php($isHome = request()->routeIs('client.home'))
            <ul class="mb-4 flex flex-col gap-1">
                <li>
                    <a
                        href="{{ route('client.home', $currentOrganization) }}"
                        @class([
                            'block rounded-field px-4 py-2 no-underline',
                            'bg-charcoal font-semibold text-cream' => $isHome,
                            'text-charcoal hover:bg-grey-100' => ! $isHome,
                        ])
                        @if ($isHome) aria-current="page" @endif
                    >{{ __('client.menu.home') }}</a>
                </li>
            </ul>
            @if ($reports->isNotEmpty())
                <p class="px-4 pb-1 text-xs font-semibold uppercase tracking-wide text-grey-600">{{ __('report.menu.reports') }}</p>
                <ul class="mb-4 flex flex-col gap-1">
                    @foreach ($reports as $reportMain)
                        <li>
                            <a href="{{ route('client.report.show', [$currentOrganization, $reportMain->diagnosis]) }}" class="block rounded-field px-4 py-2 text-charcoal no-underline hover:bg-grey-100">
                                {{ __('report.menu.report_row', ['product' => $reportMain->product_name, 'date' => $reportMain->published_at?->format('m.d.') ?? '–', 'percent' => $reportMain->calculation['ring'] ?? '–']) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
                <ul class="mb-4 flex flex-col gap-1">
                    <li><a href="{{ route('client.report.appendix', [$currentOrganization, $latestReport->diagnosis]) }}" class="block rounded-field px-4 py-2 text-charcoal no-underline hover:bg-grey-100">{{ __('report.appendix_link') }}</a></li>
                </ul>
                @unless ($latestReport->isSnapshot())
                    <p class="px-4 pb-1 text-xs font-semibold uppercase tracking-wide text-grey-600">{{ __('report.menu.chapters') }}</p>
                    <ul class="mb-4 flex flex-col gap-1">
                        <li><a href="{{ route('client.report.internal', [$currentOrganization, $latestReport->diagnosis]) }}" class="block rounded-field px-4 py-2 text-charcoal no-underline hover:bg-grey-100">{{ __('report.menu.internal') }}</a></li>
                        <li><a href="{{ route('client.report.chapter', [$currentOrganization, $latestReport->diagnosis, 'markad-alapja']) }}" class="block rounded-field px-4 py-2 text-charcoal no-underline hover:bg-grey-100">{{ __('report.menu.brand') }}</a></li>
                    </ul>
                @endunless
            @endif
            @php($menuItems[] = ['route' => 'client.wisdom.index', 'active' => 'client.wisdom.*', 'label' => __('wisdom.menu')])
            <ul class="flex flex-col gap-1">
                @foreach ($menuItems as $item)
                    @php($isActive = request()->routeIs($item['active']))
                    <li>
                        <a
                            href="{{ route($item['route'], $currentOrganization) }}"
                            @class([
                                'block rounded-field px-4 py-2 no-underline',
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
                                    class="block rounded-field px-4 py-2 text-charcoal no-underline hover:bg-grey-100"
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

            <a href="{{ route('client.profile', $currentOrganization) }}" class="rounded-field px-4 py-2 text-charcoal no-underline hover:bg-grey-100">{{ __('client.menu.profile') }}</a>
        @endif

        <form method="POST" action="{{ route('client.logout') }}">
            @csrf
            <button type="submit" class="w-full rounded-field px-4 py-2 text-left hover:bg-grey-100">{{ __('client.menu.logout') }}</button>
        </form>
    </div>
</div>
