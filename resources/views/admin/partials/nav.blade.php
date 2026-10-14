{{-- Left menu of the admin; the app version sits at the bottom (NFR · 2, 01 · 1.5). --}}
@php
    $items = [
        ['route' => 'admin.organizations.index', 'active' => 'admin.organizations.*', 'label' => __('admin.nav.organizations')],
        ['route' => 'admin.clients.index', 'active' => 'admin.clients.*', 'label' => __('admin.nav.clients')],
        ['route' => 'admin.products.index', 'active' => 'admin.products.*', 'label' => __('admin.nav.products')],
        ['route' => 'admin.admins.index', 'active' => 'admin.admins.*', 'label' => __('admin.nav.admins')],
    ];

    $linkClasses = fn (bool $isActive): string => $isActive
        ? 'block rounded-lg bg-charcoal px-3 py-2 font-semibold text-cream no-underline'
        : 'block rounded-lg px-3 py-2 text-charcoal no-underline hover:bg-grey-100';
@endphp

<div class="flex min-h-full flex-col gap-6 p-4">
    <a href="{{ route('admin.dashboard') }}" class="inline-flex px-3 pt-2">
        <x-megis.logo class="h-9" />
    </a>

    <nav aria-label="{{ __('admin.title') }}" class="flex flex-1 flex-col gap-6">
        <ul class="flex flex-col gap-1">
            @foreach ($items as $item)
                <li>
                    <a
                        href="{{ route($item['route']) }}"
                        class="{{ $linkClasses(request()->routeIs($item['active'])) }}"
                        @if (request()->routeIs($item['active'])) aria-current="page" @endif
                    >{{ $item['label'] }}</a>
                </li>
            @endforeach
        </ul>

        <div>
            <p class="px-3 pb-1 text-xs font-semibold uppercase tracking-wide text-grey-600">{{ __('admin.nav.settings') }}</p>
            <ul class="flex flex-col gap-1">
                <li>
                    <a
                        href="{{ route('admin.settings.legal') }}"
                        class="{{ $linkClasses(request()->routeIs('admin.settings.legal')) }}"
                        @if (request()->routeIs('admin.settings.legal')) aria-current="page" @endif
                    >{{ __('admin.nav.legal_documents') }}</a>
                </li>
            </ul>
        </div>

        @can('viewSystem')
            <div>
                <p class="px-3 pb-1 text-xs font-semibold uppercase tracking-wide text-grey-600">{{ __('admin.nav.system') }}</p>
                <ul class="flex flex-col gap-1">
                    <li>
                        <a
                            href="{{ route('admin.system.index') }}"
                            class="{{ $linkClasses(request()->routeIs('admin.system.index')) }}"
                            @if (request()->routeIs('admin.system.index')) aria-current="page" @endif
                        >{{ __('admin.nav.system_overview') }}</a>
                    </li>
                    <li>
                        <a
                            href="{{ route('admin.system.health') }}"
                            class="{{ $linkClasses(request()->routeIs('admin.system.health')) }}"
                            @if (request()->routeIs('admin.system.health')) aria-current="page" @endif
                        >{{ __('admin.nav.system_health') }}</a>
                    </li>
                </ul>
            </div>
        @endcan
    </nav>

    <p class="px-3 text-xs text-grey-600">{{ __('admin.version', ['version' => config('megis.version')]) }}</p>
</div>
