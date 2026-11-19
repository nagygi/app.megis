{{--
    The header of a main diagnosis (17, 19 · 19.1): its name, stage and data,
    the stage line with "Most mi jön?" under it (StageGuide), and its tabs:
    the steps of the process in their order (Áttekintés, Beállítás, Futás,
    Olvasat, Publikálás), then its data. A tab not open yet stays in its
    place, locked, and tells when it opens: the Futás tab is the contact's and
    the superadmin's, Olvasat opens with the reading, Publikálás in the
    reading stage. Every tab page starts with it, and the page draws it, so
    the stage and the tabs follow an action without a reload.
--}}
@props(['diagnosis'])

@php
    $main = $diagnosis->main;
    $guide = app(\App\Services\MainDiagnoses\StageGuide::class);
    $canRun = (bool) auth()->user()?->can('run', $diagnosis);
    $readingOpen = $main->reading_at !== null;
    $publishOpen = $main->published_at !== null || in_array($main->stage, [\App\Enums\MainDiagnosisStage::Reading, \App\Enums\MainDiagnosisStage::Published], true);
    $tabs = array_values(array_filter([
        ['route' => 'admin.diagnoses.show', 'label' => __('admin.main_diagnoses.tabs.overview')],
        ['route' => 'admin.main-diagnoses.edit', 'label' => __('admin.main_diagnoses.tabs.settings')],
        ['route' => 'admin.main-diagnoses.run', 'label' => __('admin.main_diagnoses.tabs.run'), 'disabled' => ! $canRun, 'hint' => __('admin.main_diagnoses.tab_locked.run')],
        ['route' => 'admin.main-diagnoses.reading', 'label' => __('admin.main_diagnoses.tabs.reading'), 'disabled' => ! $readingOpen, 'hint' => __('admin.main_diagnoses.tab_locked.reading')],
        ['route' => 'admin.main-diagnoses.publish', 'label' => __('admin.main_diagnoses.tabs.publish'), 'disabled' => ! $publishOpen, 'hint' => __('admin.main_diagnoses.tab_locked.publish')],
        ['route' => 'admin.main-diagnoses.subdiagnoses', 'label' => __('admin.main_diagnoses.tabs.subdiagnoses'), 'group_start' => true],
        // Ügyfélmérés, when the diagnosis has clients (35 · 35.8).
        $main->externalClients()->exists() ? ['route' => 'admin.main-diagnoses.external-clients', 'label' => __('admin.main_diagnoses.tabs.external_clients')] : null,
        // Képességmérés (NM-12).
        ['route' => 'admin.diagnoses.capabilities', 'label' => __('admin.main_diagnoses.tabs.capabilities')],
    ]));
    $days = in_array($main->stage, [\App\Enums\MainDiagnosisStage::Published, \App\Enums\MainDiagnosisStage::Closed], true) ? null : $main->dayCount();
@endphp

<div {{ $attributes->class(['flex flex-col gap-4']) }}>
    <div class="flex flex-col gap-1">
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-semibold text-charcoal">{{ $diagnosis->name }}</h1>
            <x-ui.badge tone="dark">{{ $main->stage->label() }}</x-ui.badge>
        </div>

        {{-- The organization is in the header above. --}}
        <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-grey-700">
            @if ($diagnosis->team)
                <span class="font-semibold text-charcoal">{{ $diagnosis->team->name }}</span>
                <span aria-hidden="true">·</span>
            @endif
            <span>{{ $main->product_name }}</span>
            @if ($days && $days['day'] > 0)
                <span aria-hidden="true">·</span>
                <span>{{ __('admin.run.day_count', $days) }}</span>
            @endif
            <span aria-hidden="true">·</span>
            <span>{{ __('admin.run.contact', ['name' => $diagnosis->organization->contactAdmin?->name ?? '–']) }}</span>
        </p>
    </div>

    <x-admin.stage-stepper :steps="$guide->steps($main)" />

    <x-admin.next-step :guide="$guide->next($main, auth()->user())" />

    <x-ui.tabs
        :label="$diagnosis->name"
        :tabs="array_map(fn (array $tab) => ['href' => route($tab['route'], $diagnosis), 'label' => $tab['label'], 'disabled' => $tab['disabled'] ?? false, 'hint' => $tab['hint'] ?? null, 'group_start' => $tab['group_start'] ?? false], $tabs)"
    />
</div>
