{{--
    The header of a main diagnosis (17, 19 · 19.1): its name, stage and data,
    and its tabs (the settings, the Futás tab for the contact and the
    superadmin, the subdiagnoses), under the organization's header and the
    breadcrumbs. Every tab page starts with it, and the page draws it, so the
    stage and the tabs follow an action without a reload (Publikálom, the
    reading's approvals).
--}}
@props(['diagnosis'])

@php
    $main = $diagnosis->main;
    $tabs = array_values(array_filter([
        ['route' => 'admin.diagnoses.show', 'label' => __('admin.main_diagnoses.tabs.overview')],
        ['route' => 'admin.main-diagnoses.edit', 'label' => __('admin.main_diagnoses.tabs.settings')],
        auth()->user()?->can('run', $diagnosis) ? ['route' => 'admin.main-diagnoses.run', 'label' => __('admin.main_diagnoses.tabs.run')] : null,
        // The reading opens with its stage (20 · 20.1).
        $main && $main->reading_at !== null ? ['route' => 'admin.main-diagnoses.reading', 'label' => __('admin.main_diagnoses.tabs.reading')] : null,
        // Publikálás opens when everything is approved (20 · 20.5), and stays after publishing.
        $main && ($main->stage === \App\Enums\MainDiagnosisStage::Published || app(\App\Services\Diagnoses\ReadingService::class)->progress($main)['complete'])
            ? ['route' => 'admin.main-diagnoses.publish', 'label' => __('admin.main_diagnoses.tabs.publish')] : null,
        ['route' => 'admin.main-diagnoses.subdiagnoses', 'label' => __('admin.main_diagnoses.tabs.subdiagnoses')],
        // Képességmérés (NM-12).
        ['route' => 'admin.diagnoses.capabilities', 'label' => __('admin.main_diagnoses.tabs.capabilities')],
    ]));
    $days = $main?->dayCount();
@endphp

<div {{ $attributes->class(['flex flex-col gap-3']) }}>
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-semibold text-charcoal">{{ $diagnosis->name }}</h1>
        @if ($main)
            <x-ui.badge tone="dark">{{ $main->stage->label() }}</x-ui.badge>
        @endif
    </div>

    {{-- The organization is in the header above. --}}
    @if ($main)
        <p class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-field bg-yellow-pale px-3 py-2 text-sm">
            @if ($diagnosis->team)
                <span class="font-semibold">{{ $diagnosis->team->name }}</span>
            @endif
            <span>{{ $main->product_name }}</span>
            @if ($days)
                <span>{{ __('admin.run.day_count', $days) }}</span>
            @endif
            <span>{{ __('admin.run.contact', ['name' => $diagnosis->organization->contactAdmin?->name ?? '–']) }}</span>
        </p>
    @endif

    <x-ui.tabs
        :label="$diagnosis->name"
        :tabs="array_map(fn (array $tab) => ['href' => route($tab['route'], $diagnosis), 'label' => $tab['label']], $tabs)"
    />
</div>
