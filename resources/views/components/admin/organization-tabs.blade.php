{{--
    Tabs of an organization page (02 · 2.3, 08 · 8.5, 11 · 11.6, 17 · 17.8), in
    the green header. The diagnosis pages light up Diagnózisok, the occasions
    of a process Folyamatok.
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
    $tabs[] = ['route' => 'admin.organizations.processes', 'label' => __('admin.organizations.tabs.processes'), 'also' => ['admin.organizations.process-occasions']];
    $tabs[] = ['route' => 'admin.organizations.diagnoses', 'label' => __('admin.organizations.tabs.diagnoses'), 'also' => ['admin.diagnoses.show', 'admin.main-diagnoses.*']];

    $tabs = array_map(fn (array $tab) => [
        'href' => route($tab['route'], $organization),
        'label' => $tab['label'],
        'active' => request()->routeIs($tab['route'], $tab['route'].'.*', ...($tab['also'] ?? [])),
    ], $tabs);
@endphp

<x-ui.tabs :tabs="$tabs" :label="$organization->name" variant="folder" {{ $attributes }} />
