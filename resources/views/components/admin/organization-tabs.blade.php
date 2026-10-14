{{--
    Tabs of an organization page (02 · 2.3, 08 · 8.5, 11 · 11.6, 17 · 17.8).
--}}
@props(['organization'])

@php
    $tabs = [
        ['route' => 'admin.organizations.edit', 'label' => __('admin.organizations.tabs.data')],
    ];

    if (! $organization->isIndividual()) {
        $tabs[] = ['route' => 'admin.organizations.teams', 'label' => __('admin.organizations.tabs.teams')];
    }

    $tabs[] = ['route' => 'admin.organizations.members', 'label' => __('admin.organizations.tabs.members')];
    $tabs[] = ['route' => 'admin.organizations.values', 'label' => __('admin.organizations.tabs.values')];
    $tabs[] = ['route' => 'admin.organizations.processes', 'label' => __('admin.organizations.tabs.processes')];
    $tabs[] = ['route' => 'admin.organizations.diagnoses', 'label' => __('admin.organizations.tabs.diagnoses')];
@endphp

<ul class="flex gap-1 overflow-x-auto">
    @foreach ($tabs as $tab)
        @php($isActive = request()->routeIs($tab['route']) || request()->routeIs($tab['route'].'.*'))
        <li>
            <a
                href="{{ route($tab['route'], $organization) }}"
                @class([
                    'block whitespace-nowrap rounded-t-lg px-4 py-2 text-sm font-semibold no-underline',
                    'bg-grey-100 text-charcoal' => $isActive,
                    'text-cream hover:bg-white/10' => ! $isActive,
                ])
                @if ($isActive) aria-current="page" @endif
            >{{ $tab['label'] }}</a>
        </li>
    @endforeach
</ul>
