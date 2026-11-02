{{--
    Tabs of an organization page (02 · 2.3, 08 · 8.5, 11 · 11.6, 17 · 17.8), in
    the green header of the six tab pages. The pages under a tab (a process'
    occasions, a diagnosis) show the way back in the breadcrumbs instead.
--}}
@props(['organization'])

@php
    $tabs = [
        ['route' => 'admin.organizations.edit', 'label' => __('admin.organizations.tabs.data')],
        ['route' => 'admin.organizations.members', 'label' => __('admin.organizations.tabs.members')],
    ];

    if (! $organization->isIndividual()) {
        $tabs[] = ['route' => 'admin.organizations.teams', 'label' => __('admin.organizations.tabs.teams')];
    }

    $tabs[] = ['route' => 'admin.organizations.values', 'label' => __('admin.organizations.tabs.values')];
    $tabs[] = ['route' => 'admin.organizations.processes', 'label' => __('admin.organizations.tabs.processes')];
    $tabs[] = ['route' => 'admin.organizations.diagnoses', 'label' => __('admin.organizations.tabs.diagnoses')];

    $tabs = array_map(fn (array $tab) => ['href' => route($tab['route'], $organization), 'label' => $tab['label']], $tabs);
@endphp

<x-ui.tabs :tabs="$tabs" :label="$organization->name" variant="folder" {{ $attributes }} />
