{{--
    The customer menu (15 · 15.3, prototype M1): cream on charcoal, one level
    with groups that open in place. Menu items appear only once their feature
    exists, the user may use it and it has content; the diagnosis tickets add
    them. At the bottom: the organization row (15 · 15.4), Profil, Kijelentkezés.
    The layout passes the current organization and the user's organizations.
--}}

@php
    $route = request()->route();
    $routeDiagnosis = $route?->parameter('diagnosis');
    $routeDiagnosisId = $routeDiagnosis instanceof \Illuminate\Database\Eloquent\Model ? $routeDiagnosis->getKey() : $routeDiagnosis;

    if ($currentOrganization) {
        // Riportok and Fejezetek after publishing (27 · 27.12); the published standalone diagnoses too (04 · 4.6).
        $reportService = app(\App\Services\Report\ReportService::class);
        $reports = $reportService->reportsFor($user, $currentOrganization);
        $latestReport = $reports->first();
        $standaloneReports = $reportService->standaloneReportsFor($user, $currentOrganization);
        $showsChapters = $latestReport !== null && ! $latestReport->isSnapshot();

        // A report's own pages; the internal working and the brand base belong to Fejezetek.
        $onReport = fn ($diagnosisId) => request()->routeIs('client.report.*')
            && ! request()->routeIs('client.report.appendix')
            && (string) $routeDiagnosisId === (string) $diagnosisId;
        $internalActive = $showsChapters && request()->routeIs('client.report.internal') && $onReport($latestReport->diagnosis_id);
        $brandActive = $showsChapters && request()->routeIs('client.report.chapter', 'client.report.item')
            && $route->parameter('chapter') === \App\Services\Report\ReportService::SLUGS['brand'] && $onReport($latestReport->diagnosis_id);

        // Tárak / Folyamatok for the leaders (11 · 11.5, 15 · 15.3); without it the Értéktár stands alone.
        $showsProcesses = \App\Services\Processes\ProcessService::canManageLibrary($user, $currentOrganization);
        $valueCount = $currentOrganization->valueSet()->withCount('items')->first()?->items_count ?? 0;
        $valuesActive = request()->routeIs('client.values');
        $processesActive = request()->routeIs('client.processes*');
        // Képességtár once a report the user reads has approved capabilities (M3, NM-12).
        $showsCapabilities = $reports->isNotEmpty() && \App\Models\CapabilityAssessment::query()
            ->whereIn('diagnosis_id', $reports->pluck('diagnosis_id'))
            ->whereNotNull('approved_at')
            ->whereIn('state', [\App\Enums\CapabilityState::Present, \App\Enums\CapabilityState::Absent])
            ->exists();
        $capabilitiesActive = request()->routeIs('client.capabilities');

        if ($showsProcesses) {
            $routines = \App\Models\Process::query()
                ->whereBelongsTo($currentOrganization)
                ->whereNull('parent_id')
                ->where('kind', '!=', \App\Enums\ProcessKind::OneOff);
            $routineCount = (clone $routines)->count();
            $calendarCount = $routines->where('source', \App\Enums\ProcessSource::Calendar)->count();
        }

        $reportRowActive = fn ($diagnosisId) => $onReport($diagnosisId) && ! $internalActive && ! $brandActive;
        // The group holding the page opens; Riportok also when none does, as in the prototype.
        $chaptersOpen = $internalActive || $brandActive;
        $librariesOpen = ($showsProcesses || $showsCapabilities) && ($valuesActive || $processesActive || $capabilitiesActive);
        $reportsOpen = ! $chaptersOpen && ! $librariesOpen;

        $reportsMeta = collect([
            ...$reports->map(fn ($main) => collect([$main->product_name, $main->published_at?->format('m.d.')])->filter()->implode(' · ')),
            ...$standaloneReports->map(fn ($diagnosis) => $diagnosis->name),
        ])->take(2)->implode(' · ');
    }
@endphp

<div class="flex min-h-full flex-col">
    <div class="flex items-center justify-between px-6 pt-3">
        @if ($currentOrganization)
            <a href="{{ route('client.home', $currentOrganization) }}" class="flex min-h-11 items-center no-underline">
                <x-megis.logo variant="yellow-light" class="h-7" />
                <span class="sr-only">{{ __('client.menu.home') }}</span>
            </a>
        @else
            <span class="flex min-h-11 items-center">
                <x-megis.logo variant="yellow-light" class="h-7" />
            </span>
        @endif
        {{-- The circle stays 36px; the button around it is the 44px target. --}}
        <button type="button" class="press -mr-1 grid size-11 place-items-center rounded-pill desktop:hidden" @click="menuOpen = false">
            <span class="grid size-9 place-items-center rounded-pill border-[1.5px] border-grey-700">
                <x-megis.icon name="close" :label="__('client.menu.close')" />
            </span>
        </button>
    </div>

    <p class="px-6 pt-5 text-xl leading-tight font-extrabold uppercase [text-wrap:balance]">{{ __('client.menu.heading') }}</p>

    <nav class="flex-1 px-6 pt-3 pb-6" aria-label="{{ __('client.menu.title') }}">
        @if ($currentOrganization)
            <ul class="flex flex-col">
                @if ($reports->isNotEmpty() || $standaloneReports->isNotEmpty())
                    <x-client.menu-group :title="__('report.menu.reports')" :meta="$reportsMeta" symbol="growth-rings" :open="$reportsOpen">
                        @foreach ($reports as $reportMain)
                            <x-client.menu-link
                                nested
                                :href="route('client.report.show', [$currentOrganization, $reportMain->diagnosis])"
                                :title="$reportMain->product_name"
                                :meta="__('report.menu.report_meta', ['date' => $reportMain->published_at?->format('m.d.') ?? '–', 'percent' => $reportMain->calculation['ring'] ?? '–'])"
                                :active="$reportRowActive($reportMain->diagnosis_id)"
                            />
                        @endforeach
                        @foreach ($standaloneReports as $standaloneReport)
                            <x-client.menu-link
                                nested
                                :href="route('client.report.show', [$currentOrganization, $standaloneReport])"
                                :title="__('report.menu.standalone_row', ['name' => $standaloneReport->name])"
                                :meta="$standaloneReport->diagnosed_on?->format('m.d.')"
                                :active="$reportRowActive($standaloneReport->id)"
                            />
                        @endforeach
                    </x-client.menu-group>
                @endif

                @if ($showsChapters)
                    <x-client.menu-group :title="__('report.menu.chapters')" :meta="__('report.menu.internal').' · '.__('report.menu.brand')" symbol="root" :open="$chaptersOpen">
                        <x-client.menu-link nested :href="route('client.report.internal', [$currentOrganization, $latestReport->diagnosis])" :title="__('report.menu.internal')" :meta="__('report.menu.internal_meta')" :active="$internalActive" />
                        <x-client.menu-link nested :href="route('client.report.chapter', [$currentOrganization, $latestReport->diagnosis, \App\Services\Report\ReportService::SLUGS['brand']])" :title="__('report.menu.brand')" :meta="__('report.menu.brand_meta')" :active="$brandActive" />
                    </x-client.menu-group>
                @endif

                @php($valuesMeta = $valueCount > 0 ? __('client.menu.value_library_meta', ['count' => $valueCount]) : __('client.menu.value_library_browse'))
                @if ($showsProcesses || $showsCapabilities)
                    <x-client.menu-group
                        :title="__('client.menu.libraries')"
                        :meta="collect([__('client.menu.value_library'), $showsCapabilities ? __('client.menu.capabilities') : null, $showsProcesses ? __('client.menu.processes') : null])->filter()->implode(' · ')"
                        symbol="fruit"
                        :open="$librariesOpen"
                    >
                        <x-client.menu-link nested :href="route('client.values', $currentOrganization)" :title="__('client.menu.value_library')" :meta="$valuesMeta" :active="$valuesActive" />
                        @if ($showsCapabilities)
                            <x-client.menu-link nested :href="route('client.capabilities', $currentOrganization)" :title="__('client.menu.capabilities')" :meta="__('client.menu.capabilities_meta')" :active="$capabilitiesActive" />
                        @endif
                        @if ($showsProcesses)
                            <x-client.menu-link nested :href="route('client.processes', $currentOrganization)" :title="__('client.menu.processes')" :meta="__('client.menu.processes_meta', ['count' => $routineCount, 'calendar' => $calendarCount])" :active="$processesActive" />
                        @endif
                    </x-client.menu-group>
                @else
                    <x-client.menu-link :href="route('client.values', $currentOrganization)" :title="__('client.menu.value_library')" :meta="$valuesMeta" symbol="fruit" :active="$valuesActive" />
                @endif

                <x-client.menu-link :href="route('client.mirror.record', $currentOrganization)" :title="__('client.menu.mirror')" :meta="__('client.menu.mirror_meta')" symbol="tree-of-life" :active="request()->routeIs('client.mirror.*')" />
                <x-client.menu-link :href="route('client.wisdom.index', $currentOrganization)" :title="__('wisdom.menu')" :meta="__('client.menu.wisdom_meta')" symbol="growth-rings" :active="request()->routeIs('client.wisdom.*')" />

                @if ($reports->isNotEmpty())
                    <x-client.menu-link
                        :href="route('client.report.appendix', [$currentOrganization, $latestReport->diagnosis])"
                        :title="__('report.appendix_link')"
                        :meta="__('report.menu.appendix_meta')"
                        symbol="soil"
                        symbol-class="text-coral"
                        :active="request()->routeIs('client.report.appendix') && (string) $routeDiagnosisId === (string) $latestReport->diagnosis_id"
                    />
                @endif
            </ul>
        @endif
    </nav>

    <div class="flex flex-col gap-1 border-t border-grey-700 px-6 pt-3 pb-5" @if ($currentOrganization && $organizations->count() > 1) x-data="{ open: false }" @endif>
        @if ($currentOrganization)
            @php($organizationMeta = $latestReport ? collect([$latestReport->product_name, $latestReport->published_at?->format('m.d.')])->filter()->implode(' · ') : null)
            <div class="flex items-center justify-between gap-3">
                @if ($organizations->count() > 1)
                    <button
                        type="button"
                        class="press flex min-h-11 min-w-0 flex-1 items-center gap-2 text-left hover:text-yellow"
                        :aria-expanded="open"
                        aria-controls="organization-switcher"
                        @click="open = ! open"
                    >
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold">{{ $currentOrganization->name }}</span>
                            <span class="block text-xs text-grey-400">{{ $organizationMeta ?? __('client.menu.switch_organization') }}</span>
                            @if ($organizationMeta)
                                <span class="sr-only">{{ __('client.menu.switch_organization') }}</span>
                            @endif
                        </span>
                        <x-megis.icon name="chevron-down" class="shrink-0 text-grey-400 transition-transform duration-200" ::class="open && 'rotate-180'" />
                    </button>
                @else
                    <div class="min-w-0 flex-1 py-1.5">
                        <span class="block truncate text-sm font-semibold">{{ $currentOrganization->name }}</span>
                        @if ($organizationMeta)
                            <span class="block text-xs text-grey-400">{{ $organizationMeta }}</span>
                        @endif
                    </div>
                @endif

                <a
                    href="{{ route('client.profile', $currentOrganization) }}"
                    class="press inline-flex min-h-11 shrink-0 items-center gap-1 text-sm font-semibold text-yellow no-underline"
                    @if (request()->routeIs('client.profile')) aria-current="page" @endif
                >
                    {{ __('client.menu.profile') }}
                    <x-megis.icon name="chevron-right" />
                </a>
            </div>

            @if ($organizations->count() > 1)
                <ul id="organization-switcher" x-show="open" x-cloak class="flex flex-col pb-1">
                    @foreach ($organizations as $organization)
                        <li>
                            <a
                                href="{{ route('client.home', $organization) }}"
                                @class([
                                    'press flex min-h-11 items-center text-sm no-underline [color:inherit] hover:text-yellow',
                                    'font-semibold text-yellow' => $organization->is($currentOrganization),
                                ])
                                @if ($organization->is($currentOrganization)) aria-current="true" @endif
                            >{{ $organization->name }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        @endif

        <form method="POST" action="{{ route('client.logout') }}">
            @csrf
            <button type="submit" class="press min-h-11 text-left text-sm text-grey-400 hover:text-cream">{{ __('client.menu.logout') }}</button>
        </form>
    </div>
</div>
