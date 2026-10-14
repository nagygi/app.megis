{{--
    Tabs of an organization page (02 · 2.3). Tabs whose ticket is not built yet
    (Értékek 08, Folyamatok 11, Diagnózisok 17) are added by those tickets.
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
