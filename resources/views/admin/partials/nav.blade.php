{{-- Left menu of the admin; the app version sits at the bottom (NFR · 2, 01 · 1.5). --}}
@php
    $groups = [
        [
            'label' => null,
            'items' => [
                ['route' => 'admin.inbox.index', 'active' => 'admin.inbox.*', 'label' => __('admin.nav.inbox'), 'count' => app(\App\Services\Inbox\InboxService::class)->openCount()],
                ['route' => 'admin.organizations.index', 'active' => 'admin.organizations.*', 'label' => __('admin.nav.organizations')],
                ['route' => 'admin.clients.index', 'active' => 'admin.clients.*', 'label' => __('admin.nav.clients')],
                ['route' => 'admin.products.index', 'active' => 'admin.products.*', 'label' => __('admin.nav.products')],
                ['route' => 'admin.admins.index', 'active' => 'admin.admins.*', 'label' => __('admin.nav.admins')],
            ],
        ],
        [
            'label' => __('admin.nav.diagnoses'),
            'items' => [
                ['route' => 'admin.diagnoses.index', 'active' => 'admin.diagnoses.*', 'label' => __('admin.nav.all_diagnoses')],
                ['route' => 'admin.diagnosis-types.index', 'active' => 'admin.diagnosis-types.*', 'label' => __('admin.nav.diagnosis_types')],
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
            'label' => __('admin.nav.settings'),
            'items' => [
                ['route' => 'admin.settings.legal', 'active' => 'admin.settings.legal', 'label' => __('admin.nav.legal_documents')],
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
    <a href="{{ route('admin.dashboard') }}" class="inline-flex px-3 pt-2">
        <x-megis.logo class="h-9" />
    </a>

    <nav aria-label="{{ __('admin.title') }}" class="flex flex-1 flex-col gap-6">
        @foreach ($groups as $group)
            <div>
                @if ($group['label'])
                    <p class="px-3 pb-1 text-xs font-semibold uppercase tracking-wide text-grey-600">{{ $group['label'] }}</p>
                @endif
                <ul class="flex flex-col gap-1">
                    @foreach ($group['items'] as $item)
                        @php($isActive = request()->routeIs($item['active']))
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
