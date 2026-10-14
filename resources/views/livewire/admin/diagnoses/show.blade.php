@php
    $isAggregatable = $diagnosis->level->canAggregate();
    $aggregation = $diagnosis->aggregation;
    $decimalLabel = fn (?float $number) => $number === null ? '–' : number_format($number, 1, ',', '');
@endphp

<div class="flex max-w-6xl flex-col gap-6">
    <x-ui.flash />

    {{-- Header: the basic data and the status steps (04 · 4.3) --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex flex-col gap-2">
            <h1 class="text-2xl font-semibold text-charcoal">{{ $diagnosis->name }}</h1>
            <p class="text-grey-700">
                {{ $diagnosis->organization->name }} · {{ $diagnosis->subjectLabel() }} · {{ $diagnosis->type->name }} · {{ $diagnosis->dateLabel() }}
            </p>
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <x-admin.diagnosis-status :status="$diagnosis->status" />
                <span class="text-grey-700">{{ __('admin.diagnoses.creator') }}: {{ $diagnosis->creator_kind->label() }}@if ($diagnosis->creator) · {{ $diagnosis->creator->name }}@endif</span>
            </div>
            @if ($diagnosis->description)
                <p class="max-w-3xl whitespace-pre-line text-grey-700">{{ $diagnosis->description }}</p>
            @endif
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($isEditable)
                <x-ui.button tone="outline" size="sm" :href="route('admin.diagnoses.edit', $diagnosis)">{{ __('admin.diagnoses.edit_data') }}</x-ui.button>
            @endif
            @foreach ($diagnosis->status->nextStatuses() as $next)
                @php
                    [$label, $tone] = match (true) {
                        $next === \App\Enums\DiagnosisStatus::PendingApproval => [__('admin.diagnoses.to_pending'), 'dark'],
                        $next === \App\Enums\DiagnosisStatus::Published => [__('admin.diagnoses.to_published'), 'dark'],
                        $diagnosis->status === \App\Enums\DiagnosisStatus::Published => [__('admin.diagnoses.withdraw'), 'outline'],
                        default => [__('admin.diagnoses.to_draft'), 'outline'],
                    };
                @endphp
                @if ($diagnosis->status === \App\Enums\DiagnosisStatus::Published)
                    <x-ui.button :tone="$tone" size="sm" wire:click="changeStatus('{{ $next->value }}')" wire:confirm="{{ __('admin.diagnoses.confirm_withdraw') }}">{{ $label }}</x-ui.button>
                @else
                    <x-ui.button :tone="$tone" size="sm" wire:click="changeStatus('{{ $next->value }}')">{{ $label }}</x-ui.button>
                @endif
            @endforeach
        </div>
    </div>

    @unless ($isEditable)
        <p class="rounded-lg bg-grey-100 px-3 py-2 text-sm">{{ __('admin.diagnoses.published_readonly') }}</p>
    @endunless

    {{-- Aggregated data (04 · 4.5) --}}
    @if ($isAggregatable)
        <section class="flex flex-col gap-4 rounded-card border border-grey-250 bg-white p-6" aria-labelledby="aggregation-title">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="aggregation-title" class="text-lg font-semibold">{{ __('admin.diagnoses.aggregation.title') }}</h2>
                @if ($isEditable && ! $preview)
                    <x-ui.button tone="outline" size="sm" wire:click="previewAggregation">{{ __('admin.diagnoses.aggregation.load') }}</x-ui.button>
                @endif
            </div>

            @if ($preview)
                @include('livewire.admin.diagnoses.partials.aggregation-description', ['description' => $preview])

                <h3 class="font-semibold">{{ __('admin.diagnoses.aggregation.preview_title') }}</h3>
                @include('livewire.admin.diagnoses.partials.aggregation-preview', ['rows' => $preview['rows']])

                <div class="flex flex-wrap gap-3">
                    @if ($valueRows->isNotEmpty() || $aggregation)
                        <x-ui.button wire:click="loadAggregation" wire:confirm="{{ __('admin.diagnoses.aggregation.confirm_reload') }}">{{ __('admin.diagnoses.aggregation.confirm') }}</x-ui.button>
                    @else
                        <x-ui.button wire:click="loadAggregation">{{ __('admin.diagnoses.aggregation.confirm') }}</x-ui.button>
                    @endif
                    <x-ui.button tone="outline" wire:click="cancelAggregation">{{ __('admin.diagnoses.aggregation.cancel') }}</x-ui.button>
                </div>
            @elseif ($aggregation)
                @include('livewire.admin.diagnoses.partials.aggregation-description', ['description' => $aggregation])
            @else
                <p class="text-sm text-grey-600">{{ __('admin.diagnoses.aggregation.help') }}</p>
            @endif
        </section>
    @endif

    {{-- Values (04 · 4.4) --}}
    <section class="flex flex-col gap-3" aria-labelledby="values-title">
        <h2 id="values-title" class="text-lg font-semibold">{{ __('admin.diagnoses.values_block') }}</h2>

        @include('livewire.admin.diagnoses.partials.rows-table', ['rows' => $valueRows, 'removable' => true])

        @if ($isEditable)
            <form wire:submit="addValue" class="flex flex-wrap items-end gap-3 rounded-card border border-grey-250 bg-white p-4">
                <div class="min-w-64 flex-1">
                    <x-form.input
                        name="newValueName"
                        wire:model="newValueName"
                        list="available-values"
                        autocomplete="off"
                        :label="__('admin.diagnoses.add_value_label')"
                        :help="__('admin.diagnoses.add_value_help')"
                    />
                    <datalist id="available-values">
                        @foreach ($availableValues as $valueName)
                            <option value="{{ $valueName }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <x-ui.button type="submit" class="mb-7">{{ __('admin.diagnoses.add_value_button') }}</x-ui.button>
            </form>
        @endif
    </section>

    @if ($otherDetected->isNotEmpty())
        {{-- What matched nothing in the Értéktár, without a score (06 · 6.5) --}}
        <section class="flex flex-col gap-3" aria-labelledby="other-detected">
            <h2 id="other-detected" class="text-lg font-semibold">{{ __('admin.diagnoses.other_detected') }}</h2>
            <ul class="flex flex-col gap-2">
                @foreach ($otherDetected as $detections)
                    @php($detectedValue = $detections->first()->unmatchedValue)
                    <li class="rounded-card border border-grey-250 bg-white p-4">
                        <a href="{{ route('admin.values.edit', $detectedValue) }}" class="font-semibold text-charcoal">{{ $detectedValue->name }}</a>
                        <x-ui.badge class="ml-2">{{ $detectedValue->origin->label() }}</x-ui.badge>
                        <ul class="mt-2 flex flex-col gap-1 text-sm text-grey-700">
                            @foreach ($detections as $detection)
                                <li>„{{ $detection->quote }}”</li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($diagnosis->material)
        @include('livewire.admin.materials.partials.source', ['material' => $diagnosis->material, 'highlightSpeaker' => $diagnosis->speaker])
    @endif

    {{-- The brand base: four fixed rows (04 · 4.4) --}}
    <section class="flex flex-col gap-3" aria-labelledby="tree-title">
        <h2 id="tree-title" class="text-lg font-semibold">{{ __('admin.diagnoses.tree_block') }}</h2>

        @include('livewire.admin.diagnoses.partials.rows-table', ['rows' => $treeRows, 'removable' => false])
    </section>

    {{-- Status log (04 · 4.3) --}}
    <section class="flex flex-col gap-2" aria-labelledby="history-title">
        <h2 id="history-title" class="text-lg font-semibold">{{ __('admin.diagnoses.history') }}</h2>
        <ul class="flex flex-col gap-1 text-sm text-grey-700">
            @foreach ($diagnosis->transitions->sortByDesc('id') as $transition)
                <li>
                    {{ $transition->created_at->format('Y.m.d. H:i') }} ·
                    @if ($transition->from_status)
                        {{ $transition->from_status->label() }} → {{ $transition->to_status->label() }}
                    @else
                        {{ __('admin.diagnoses.created_status', ['status' => $transition->to_status->label()]) }}
                    @endif
                    · {{ $transition->creator?->name ?? __('admin.versions.system') }}
                </li>
            @endforeach
        </ul>
    </section>
</div>
