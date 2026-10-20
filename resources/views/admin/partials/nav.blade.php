{{-- Left menu of the admin; the app version sits at the bottom (NFR · 2, 01 · 1.5). --}}
@php
    $inbox = app(\App\Services\Inbox\InboxService::class);

    $groups = [
        [
            'label' => null,
            'items' => [
                ['route' => 'admin.inbox.index', 'active' => 'admin.inbox.*', 'label' => __('admin.nav.inbox'), 'count' => $inbox->openCount(auth()->user())],
            ],
        ],
        [
            'label' => __('admin.nav.organizations'),
            'items' => [
                ['route' => 'admin.organizations.index', 'active' => 'admin.organizations.*', 'label' => __('admin.nav.organizations')],
                ['route' => 'admin.clients.index', 'active' => 'admin.clients.*', 'label' => __('admin.nav.clients')],
            ],
        ],
        [
            'label' => __('admin.nav.diagnoses'),
            'items' => [
                ['route' => 'admin.diagnoses.index', 'active' => 'admin.diagnoses.*', 'label' => __('admin.nav.all_diagnoses')],
                ['route' => 'admin.diagnosis-types.index', 'active' => 'admin.diagnosis-types.*', 'label' => __('admin.nav.diagnosis_types')],
                ['route' => 'admin.support-questions.index', 'active' => 'admin.support-questions.*', 'label' => __('admin.nav.support_questions')],
            ],
        ],
        [
            'label' => __('admin.nav.measurements'),
            'items' => [
                ['route' => 'admin.measurements.index', 'active' => 'admin.measurements.*', 'label' => __('admin.nav.inbox'), 'count' => $inbox->openMeasurementCount(auth()->user())],
            ],
        ],
        [
            'label' => __('admin.nav.value_library'),
            'items' => [
                ['route' => 'admin.values.index', 'active' => 'admin.values.*', 'label' => __('admin.nav.values')],
                ['route' => 'admin.value-categories.index', 'active' => 'admin.value-categories.*', 'label' => __('admin.nav.value_categories')],
            ],
        ],
        [
            'label' => __('admin.nav.capability_library'),
            'items' => [
                ['route' => 'admin.capabilities.index', 'active' => ['admin.capabilities.index', 'admin.capabilities.create', 'admin.capabilities.edit', 'admin.capabilities.versions'], 'label' => __('admin.nav.capabilities')],
                ['route' => 'admin.capability-groups.index', 'active' => 'admin.capability-groups.*', 'label' => __('admin.nav.capability_groups')],
                ['route' => 'admin.capabilities.import', 'active' => 'admin.capabilities.import', 'label' => __('admin.nav.capability_import')],
            ],
        ],
        [
            'label' => __('admin.nav.settings'),
            'items' => [
                ['route' => 'admin.products.index', 'active' => 'admin.products.*', 'label' => __('admin.nav.products')],
                ['route' => 'admin.admins.index', 'active' => 'admin.admins.*', 'label' => __('admin.nav.admins')],
                ['route' => 'admin.settings.legal', 'active' => 'admin.settings.legal', 'label' => __('admin.nav.legal_documents')],
                ['route' => 'admin.settings.areas', 'active' => 'admin.settings.areas', 'label' => __('admin.nav.areas')],
            ],
        ],
    ];

    if (auth()->user()?->can('viewSystem')) {
        $groups[] = [
            'label' => __('admin.nav.system'),
            'items' => [
                ['route' => 'admin.system.index', 'active' => 'admin.system.index', 'label' => __('admin.nav.system_overview')],
                ['route' => 'admin.system.health', 'active' => 'admin.system.health', 'label' => __('admin.nav.system_health')],
                ['route' => 'admin.system.ai.usage', 'active' => 'admin.system.ai.usage', 'label' => __('admin.nav.ai_usage')],
                ['route' => 'admin.system.ai.configuration', 'active' => 'admin.system.ai.configuration', 'label' => __('admin.nav.ai_configuration')],
                ['route' => 'admin.system.ai.prompts', 'active' => 'admin.system.ai.prompts', 'label' => __('admin.nav.ai_prompts')],
            ],
        ];
    }

    $linkClasses = fn (bool $isActive): string => $isActive
        ? 'flex items-center justify-between gap-2 rounded-lg bg-charcoal px-3 py-2 font-semibold text-cream no-underline'
        : 'flex items-center justify-between gap-2 rounded-lg px-3 py-2 text-charcoal no-underline hover:bg-grey-100';
@endphp

<div class="flex min-h-full flex-col gap-6 p-4">
    <div class="flex items-center justify-between gap-2">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex px-3 pt-2 no-underline">
            <x-megis.logo class="h-9" />
        </a>
        <button type="button" class="press grid size-11 place-items-center rounded-pill hover:bg-grey-100 lg:hidden" @click="navOpen = false">
            <x-megis.icon name="close" class="size-5" :label="__('admin.menu_close')" />
        </button>
    </div>

    <nav aria-label="{{ __('admin.title') }}" class="flex flex-1 flex-col gap-6">
        @foreach ($groups as $group)
            @php($groupActive = collect($group['items'])->contains(fn ($item) => request()->routeIs(...(array) $item['active'])))
            <div
                @if ($group['label'])
                    x-data="{
                        key: 'admin-nav:{{ $group['items'][0]['route'] }}',
                        open: true,
                        init() {
                            try { if (localStorage.getItem(this.key) === 'closed') this.open = {{ $groupActive ? 'true' : 'false' }}; } catch (e) {}
                        },
                        toggle() {
                            this.open = ! this.open;
                            try { localStorage.setItem(this.key, this.open ? 'open' : 'closed'); } catch (e) {}
                        },
                    }"
                @endif
            >
                @if ($group['label'])
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-2 rounded-lg px-3 pb-1 text-left text-xs font-semibold uppercase tracking-wide text-grey-600"
                        aria-controls="admin-nav-group-{{ $loop->index }}"
                        :aria-expanded="open"
                        @click="toggle()"
                    >
                        <span>{{ $group['label'] }}</span>
                        <x-megis.icon name="chevron-down" class="transition-transform" ::class="open || '-rotate-90'" />
                    </button>
                @endif
                <ul
                    id="admin-nav-group-{{ $loop->index }}"
                    class="flex flex-col gap-1"
                    @if ($group['label']) x-show="open" @endif
                >
                    @foreach ($group['items'] as $item)
                        @php($isActive = request()->routeIs(...(array) $item['active']))
                        <li>
                            <a
                                href="{{ route($item['route']) }}"
                                class="{{ $linkClasses($isActive) }}"
                                @if ($isActive) aria-current="page" @endif
                            >
                                <span>{{ $item['label'] }}</span>
                                @if (($item['count'] ?? 0) > 0)
                                    <span @class([
                                        'rounded-pill px-2 text-xs font-semibold',
                                        'bg-cream text-charcoal' => $isActive,
                                        'bg-coral text-black' => ! $isActive,
                                    ])>{{ $item['count'] }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <p class="px-3 text-xs text-grey-600">{{ __('admin.version', ['version' => config('megis.version')]) }}</p>
</div>
