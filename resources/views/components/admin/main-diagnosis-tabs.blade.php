{{--
    The header and the tabs of a main diagnosis (17, 19 · 19.1): the diagnosis,
    its settings (A1), the Futás tab for the contact and the superadmin, its
    subdiagnoses (17 · 17.4). Every tab page starts with it, so the title, the
    stage and the tabs stay put while switching; the layout's header carries
    the organization's colour on all of them.
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

    @if ($main)
        <p class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-field bg-yellow-pale px-3 py-2 text-sm">
            <span class="font-semibold">{{ $diagnosis->organization->name }}</span>
            @if ($diagnosis->team)
                <span>{{ $diagnosis->team->name }}</span>
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
        :tabs="array_map(fn (array $tab) => ['href' => route($tab['route'], $diagnosis), 'label' => $tab['label'], 'active' => request()->routeIs($tab['route'])], $tabs)"
    />
</div>
