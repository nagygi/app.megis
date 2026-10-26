@php
    // A main diagnosis is calculated from its subdiagnoses (17 · 17.4, 18), not loaded.
    $isAggregatable = $diagnosis->level->canAggregate() && $diagnosis->main === null;
    $aggregation = $diagnosis->aggregation;
    $decimalLabel = fn (?float $number) => $number === null ? '–' : number_format($number, 1, ',', '');
@endphp

{{-- A main diagnosis' tab pages keep the wide frame; any other diagnosis is a detail page. --}}
<div @class(['flex flex-col gap-6', 'max-w-7xl' => $main, 'max-w-5xl' => ! $main])>
    <x-ui.flash />

    {{-- A main diagnosis starts with its title and tabs, like its other tab pages (17). --}}
    @if ($main)
        <x-admin.main-diagnosis-tabs :diagnosis="$diagnosis" />
    @endif

    {{-- Header: the basic data and the status steps (04 · 4.3) --}}
    <div class="flex flex-col gap-2">
        @unless ($main)
            <x-admin.page-header :title="$diagnosis->name" :spaced="false">
                <x-slot:badges>
                    <x-admin.diagnosis-status :status="$diagnosis->status" />
                </x-slot:badges>
                <x-slot:actions>
                    @if ($isEditable && $canChangeStatus)
                        <x-ui.button tone="outline" size="sm" :href="route('admin.diagnoses.edit', $diagnosis)">{{ __('admin.diagnoses.edit_data') }}</x-ui.button>
                    @endif
                    {{-- Képességmérés of the material the diagnosis was built from (NM-12). --}}
                    @if ($diagnosis->material_id !== null)
                        <x-ui.button tone="outline" size="sm" :href="route('admin.diagnoses.capabilities', $diagnosis)">{{ __('capabilities.review.title') }}</x-ui.button>
                    @endif
                    {{-- The user's report, clickable, before publishing (4.6). --}}
                    @if ($canChangeStatus)
                        <x-ui.button tone="outline" size="sm" :href="route('admin.diagnoses.preview', $diagnosis)">{{ __('admin.diagnoses.preview.button') }}</x-ui.button>
                    @endif
                    @foreach ($canChangeStatus ? $diagnosis->status->nextStatuses() : [] as $next)
                        @php
                            [$label, $tone] = match (true) {
                                $next === \App\Enums\DiagnosisStatus::PendingApproval => [__('admin.diagnoses.to_pending'), 'dark'],
                                $next === \App\Enums\DiagnosisStatus::Published => [__('admin.diagnoses.to_published'), 'dark'],
                                $diagnosis->status === \App\Enums\DiagnosisStatus::Published => [__('admin.diagnoses.withdraw'), 'danger'],
                                default => [__('admin.diagnoses.to_draft'), 'outline'],
                            };
                        @endphp
                        @if ($diagnosis->status === \App\Enums\DiagnosisStatus::Published)
                            {{-- Withdrawing hides it from the users until it is published again. --}}
                            <x-ui.button :tone="$tone" size="sm" wire:click="changeStatus('{{ $next->value }}')" wire:confirm="{{ __('admin.diagnoses.confirm_withdraw') }}" data-confirm-tone="danger" :data-confirm-label="$label">{{ $label }}</x-ui.button>
                        @else
                            <x-ui.button :tone="$tone" size="sm" wire:click="changeStatus('{{ $next->value }}')">{{ $label }}</x-ui.button>
                        @endif
                    @endforeach
                </x-slot:actions>
            </x-admin.page-header>
        @endunless
        <p class="text-grey-700">
            {{ $diagnosis->organization->name }} · {{ $diagnosis->subjectLabel() }} · {{ $diagnosis->type->name }} · {{ $diagnosis->dateLabel() }}
        </p>
        <div class="flex flex-wrap items-center gap-2 text-sm">
            @if ($main)
                <x-admin.diagnosis-status :status="$diagnosis->status" />
            @endif
            <span class="text-grey-700">{{ __('admin.diagnoses.creator') }}: {{ $diagnosis->creator_kind->label() }}@if ($diagnosis->creator) · {{ $diagnosis->creator->name }}@endif</span>
        </div>
        @if ($diagnosis->description)
            <p class="max-w-3xl whitespace-pre-line text-grey-700">{{ $diagnosis->description }}</p>
        @endif
    </div>

    @if ($main)
        {{-- The main diagnosis: stage, leader, time window (17) --}}
        <x-ui.card as="section" bordered padding="lg" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" aria-label="{{ __('admin.main_diagnoses.summary') }}">
            <div>
                <p class="text-sm text-grey-700">{{ __('admin.main_diagnoses.stage') }}</p>
                <p class="font-semibold">{{ $main->stage->label() }}</p>
            </div>
            <div>
                <p class="text-sm text-grey-700">{{ __('admin.main_diagnoses.product') }}</p>
                <p class="font-semibold">{{ $main->product_name }} · {{ $main->subject->label() }}</p>
            </div>
            <div>
                <p class="text-sm text-grey-700">{{ $main->subject === \App\Enums\MainDiagnosisSubject::Person ? __('admin.main_diagnoses.person') : __('admin.main_diagnoses.leader') }}</p>
                <p class="font-semibold">{{ $main->leader->name }}</p>
                @if ($main->hasPeers())
                    <p class="text-sm text-grey-700">{{ trans_choice('admin.main_diagnoses.preview_peers', $main->participants->where('role', \App\Enums\ParticipantRole::Peer)->whereNull('excluded_at')->count(), ['count' => $main->participants->where('role', \App\Enums\ParticipantRole::Peer)->whereNull('excluded_at')->count()]) }}</p>
                @endif
            </div>
            <div>
                <p class="text-sm text-grey-700">{{ __('admin.main_diagnoses.window') }}</p>
                {{-- A date range, written like the run's time window (admin.run.window_label). --}}
                <p class="font-semibold">{{ $main->starts_on?->format('Y.m.d.') ?? __('diagnosis.date_open') }} – {{ $main->publishes_on->format('Y.m.d.') }}</p>
                <p class="text-sm text-grey-700">{{ __('admin.main_diagnoses.reading_short', ['date' => $main->readingStartsOn()->format('Y.m.d.')]) }}</p>
            </div>
        </x-ui.card>
    @elseif ($diagnosis->parent)
        <x-ui.notice>
            {{ __('admin.main_diagnoses.part_of') }}
            <a href="{{ route('admin.diagnoses.show', $diagnosis->parent) }}" class="font-semibold">{{ $diagnosis->parent->name }}</a>
            · {{ __('admin.main_diagnoses.status_follows') }}
            @if ($diagnosis->excluded_at)
                · <strong>{{ __('admin.main_diagnoses.excluded') }}</strong>
            @endif
        </x-ui.notice>
    @endif

    @unless ($isEditable)
        <x-ui.notice>{{ __('admin.diagnoses.published_readonly') }}</x-ui.notice>
    @endunless

    @if ($main && $main->calculation)
        {{-- The ring, the step, the points and the biggest gap (18 · 18.3, 18.4) --}}
        @php($calculation = $main->calculation)
        <x-ui.card as="section" bordered padding="lg" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5" aria-labelledby="calculation-title">
            <h2 id="calculation-title" class="sr-only">{{ __('admin.diagnoses.calculation.title') }}</h2>
            <div>
                <p class="text-sm text-grey-700">{{ __('admin.diagnoses.calculation.ring') }}</p>
                <p class="text-2xl font-semibold">{{ $calculation['ring'] === null ? '–' : $calculation['ring'].'%' }}</p>
            </div>
            <div>
                <p class="text-sm text-grey-700">{{ __('admin.diagnoses.calculation.step') }}</p>
                <p class="font-semibold">{{ $calculation['step'] === null ? '–' : __('admin.diagnoses.calculation.steps.'.$calculation['step']) }}</p>
            </div>
            <div>
                <p class="text-sm text-grey-700">{{ __('admin.diagnoses.calculation.points') }}</p>
                <p class="font-semibold">{{ $calculation['points'] ?? 0 }} / {{ $calculation['max'] ?? 0 }}</p>
                <p class="text-sm text-grey-700">{{ __('admin.diagnoses.calculation.measuring_points', ['count' => $calculation['measuring_points'] ?? 0]) }}</p>
            </div>
            <div>
                <p class="text-sm text-grey-700">{{ __('admin.diagnoses.calculation.biggest_gap') }}</p>
                <p class="font-semibold">
                    @if ($calculation['biggest_gap'])
                        {{ $calculation['biggest_gap']['label'] }} · {{ __('admin.diagnoses.calculation.gap_text', ['experience' => number_format((float) $calculation['biggest_gap']['experience'], 1, ',', ''), 'score' => number_format((float) $calculation['biggest_gap']['score'], 1, ',', '')]) }}
                    @else
                        –
                    @endif
                </p>
            </div>
            <div>
                <p class="text-sm text-grey-700">{{ __('admin.diagnoses.calculation.threshold') }}</p>
                <p class="font-semibold">{{ $calculation['threshold_met'] ? __('admin.diagnoses.calculation.threshold_met') : __('admin.diagnoses.calculation.threshold_open', ['count' => $calculation['peers_answered']]) }}</p>
                <p class="text-sm text-grey-700">{{ __('admin.diagnoses.calculation.calculated_at', ['date' => $main->calculated_at->format('Y.m.d. H:i')]) }}</p>
            </div>
        </x-ui.card>
    @endif

    @if ($diagnosis->level === \App\Enums\DiagnosisLevel::Process)
        {{-- The point of the process and how it was calculated (13 · 13.1, 13.3, 13.5) --}}
        @php($processAggregation = $diagnosis->aggregation ?? [])
        <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-4" aria-labelledby="process-point-title">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h2 id="process-point-title" class="text-lg font-semibold">{{ __('admin.diagnoses.process_point') }} · {{ $diagnosis->process?->name }}</h2>
                    <p class="text-3xl font-semibold">{{ $diagnosis->scoreLabel() ?: '–' }}</p>
                    <p class="flex flex-wrap gap-1">
                        @if ($diagnosis->score_state === \App\Enums\ScoreState::InsufficientData)
                            <x-ui.badge tone="coral">{{ __('admin.diagnoses.process_few', ['count' => $processAggregation['occasions'] ?? 0]) }}</x-ui.badge>
                        @endif
                        @if ($processAggregation['single_occasion'] ?? false)
                            <x-ui.badge tone="yellow">{{ __('admin.diagnoses.process_single') }}</x-ui.badge>
                        @endif
                        @if ($diagnosis->score_overridden_at)
                            <x-ui.badge tone="yellow">{{ __('admin.diagnoses.override.badge') }}</x-ui.badge>
                        @endif
                    </p>
                    @if ($diagnosis->score_overridden_at)
                        <p class="text-sm text-grey-700">
                            {{ __('admin.diagnoses.override.by', ['name' => $diagnosis->scoreOverrider?->name ?? '–', 'date' => $diagnosis->score_overridden_at->format('Y.m.d. H:i')]) }} · {{ $diagnosis->score_override_reason }}
                            · {{ __('admin.diagnoses.override.automatic', ['value' => $diagnosis->automatic_score === null ? '–' : number_format($diagnosis->automatic_score, 1, ',', '')]) }}
                        </p>
                    @endif
                    @isset($processAggregation['effectiveness'])
                        <p class="text-sm text-grey-700">{{ __('admin.diagnoses.effectiveness_summary', [
                            'occasions' => $processAggregation['occasions'] ?? 0,
                            'yes' => $processAggregation['effectiveness']['yes'] ?? 0,
                            'partial' => $processAggregation['effectiveness']['partial'] ?? 0,
                            'no' => $processAggregation['effectiveness']['no'] ?? 0,
                        ]) }}</p>
                    @endisset
                    @if ($newOccasions > 0)
                        <p class="text-sm font-semibold">{{ __('admin.diagnoses.new_occasions', ['count' => $newOccasions]) }}</p>
                    @endif
                </div>
                @if ($isEditable && $isCalculated && ! $editingScore)
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button tone="outline" size="sm" wire:click="editScore">{{ __('admin.diagnoses.override.edit') }}</x-ui.button>
                        @if ($diagnosis->score_overridden_at)
                            {{-- The manual value and its reason are lost. --}}
                            <x-ui.button tone="danger" size="sm" wire:click="resetScore" wire:confirm="{{ __('admin.diagnoses.override.confirm_reset') }}" data-confirm-tone="danger" :data-confirm-label="__('admin.diagnoses.override.reset')">{{ __('admin.diagnoses.override.reset') }}</x-ui.button>
                        @endif
                    </div>
                @endif
            </div>

            @if ($editingScore)
                <form wire:submit="saveScore" class="grid gap-4 md:grid-cols-2">
                    <x-form.input name="scoreInput" wire:model="scoreInput" :label="__('admin.diagnoses.process_point')" :help="__('admin.diagnoses.process_point_help')" required />
                    <x-form.textarea name="scoreReason" wire:model="scoreReason" :label="__('admin.diagnoses.override.reason')" :help="__('admin.diagnoses.override.reason_help')" rows="2" required />
                    <div class="flex flex-wrap gap-3 md:col-span-2">
                        <x-ui.button type="submit" size="sm">{{ __('admin.common.save') }}</x-ui.button>
                        <x-ui.button tone="outline" size="sm" wire:click="$set('editingScore', false)">{{ __('admin.common.cancel') }}</x-ui.button>
                    </div>
                </form>
            @endif

            @if ($processAggregation !== [])
                @include('livewire.admin.diagnoses.partials.process-aggregation', ['description' => $processAggregation])
            @endif
        </x-ui.card>
    @endif

    {{-- Aggregated data (04 · 4.5) --}}
    @if ($isAggregatable)
        <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-4" aria-labelledby="aggregation-title">
            <x-ui.section-header :title="__('admin.diagnoses.aggregation.title')" id="aggregation-title">
                <x-slot:actions>
                    @if ($isEditable && ! $preview)
                        <x-ui.button tone="outline" size="sm" wire:click="previewAggregation">{{ __('admin.diagnoses.aggregation.load') }}</x-ui.button>
                    @endif
                </x-slot:actions>
            </x-ui.section-header>

            @if ($preview)
                @include('livewire.admin.diagnoses.partials.aggregation-description', ['description' => $preview])

                <h3 class="font-semibold">{{ __('admin.diagnoses.aggregation.preview_title') }}</h3>
                @include('livewire.admin.diagnoses.partials.aggregation-preview', ['rows' => $preview['rows']])

                <div class="flex flex-wrap gap-3">
                    @if ($valueRows->isNotEmpty() || $aggregation)
                        {{-- Reloading writes over every row, the manual changes too. --}}
                        <x-ui.button wire:click="loadAggregation" wire:confirm="{{ __('admin.diagnoses.aggregation.confirm_reload') }}" data-confirm-tone="danger" :data-confirm-label="__('admin.diagnoses.aggregation.confirm')">{{ __('admin.diagnoses.aggregation.confirm') }}</x-ui.button>
                    @else
                        <x-ui.button wire:click="loadAggregation">{{ __('admin.diagnoses.aggregation.confirm') }}</x-ui.button>
                    @endif
                    <x-ui.button tone="outline" wire:click="cancelAggregation">{{ __('admin.diagnoses.aggregation.cancel') }}</x-ui.button>
                </div>
            @elseif ($aggregation)
                @include('livewire.admin.diagnoses.partials.aggregation-description', ['description' => $aggregation])
            @else
                <p class="text-sm text-grey-700">{{ __('admin.diagnoses.aggregation.help') }}</p>
            @endif
        </x-ui.card>
    @endif

    {{-- Values (04 · 4.4) --}}
    <section class="flex flex-col gap-3" aria-labelledby="values-title">
        <h2 id="values-title" class="text-lg font-semibold">{{ __('admin.diagnoses.values_block') }}</h2>

        @include('livewire.admin.diagnoses.partials.rows-table', ['rows' => $valueRows, 'removable' => true])

        @if ($isEditable && ! $isCalculated)
            <x-ui.card as="form" bordered padding="sm" wire:submit="addValue">
                <x-form.inline>
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
                    <x-slot:actions>
                        <x-ui.button type="submit" tone="outline" icon="plus">{{ __('admin.diagnoses.add_value_button') }}</x-ui.button>
                    </x-slot:actions>
                </x-form.inline>
            </x-ui.card>
        @endif
    </section>

    @if ($otherDetected->isNotEmpty())
        {{-- What matched nothing in the Értéktár, without a score (06 · 6.5) --}}
        <section class="flex flex-col gap-3" aria-labelledby="other-detected">
            <h2 id="other-detected" class="text-lg font-semibold">{{ __('admin.diagnoses.other_detected') }}</h2>
            <ul class="flex flex-col gap-2">
                @foreach ($otherDetected as $detections)
                    @php($detectedValue = $detections->first()->unmatchedValue)
                    <x-ui.card as="li" bordered padding="sm">
                        <a href="{{ route('admin.values.edit', $detectedValue) }}" class="font-semibold text-charcoal">{{ $detectedValue->name }}</a>
                        <x-ui.badge class="ml-2">{{ $detectedValue->origin->label() }}</x-ui.badge>
                        <ul class="mt-2 flex flex-col gap-1 text-sm text-grey-700">
                            @foreach ($detections as $detection)
                                <li>„{{ $detection->quote }}”</li>
                            @endforeach
                        </ul>
                    </x-ui.card>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($diagnosis->material)
        @include('livewire.admin.materials.partials.source', ['material' => $diagnosis->material, 'highlightSpeaker' => $diagnosis->speaker])
    @endif

    @if ($processRows->isNotEmpty())
        {{-- Folyamatok: the point of each process diagnosis (13 · 13.3, 18 · 18.1) --}}
        <section class="flex flex-col gap-3" aria-labelledby="processes-title">
            <h2 id="processes-title" class="text-lg font-semibold">{{ __('admin.diagnoses.processes_block') }}</h2>
            @include('livewire.admin.diagnoses.partials.rows-table', ['rows' => $processRows, 'removable' => false, 'title' => __('admin.diagnoses.processes_block')])
            <ul class="-ml-1.5 flex flex-wrap gap-x-3">
                @foreach ($processRows as $processRow)
                    @isset($processRow->aggregation_stats['process_diagnosis_id'])
                        <li><x-ui.button tone="ghost" size="sm" :href="route('admin.diagnoses.show', $processRow->aggregation_stats['process_diagnosis_id'])">{{ __('admin.diagnoses.open_process', ['name' => $processRow->label()]) }}</x-ui.button></li>
                    @endisset
                @endforeach
            </ul>
        </section>
    @endif

    @if ($areaRows->isNotEmpty())
        {{-- Területek: scored by hand in the reading, Teljes only (18 · 18.1) --}}
        <section class="flex flex-col gap-3" aria-labelledby="areas-title">
            <h2 id="areas-title" class="text-lg font-semibold">{{ __('admin.diagnoses.areas_block') }}</h2>
            @include('livewire.admin.diagnoses.partials.rows-table', ['rows' => $areaRows, 'removable' => false, 'title' => __('admin.diagnoses.areas_block')])
        </section>
    @endif

    @if ($occasions->isNotEmpty())
        {{-- The occasions of a process diagnosis (13 · 13.5) --}}
        <section class="flex flex-col gap-3" aria-labelledby="occasions-title">
            <h2 id="occasions-title" class="text-lg font-semibold">{{ __('admin.occasions.title') }}</h2>
            <x-admin.table>
                <x-slot:head>
                    <x-admin.th>{{ __('admin.occasions.date') }}</x-admin.th>
                    <x-admin.th>{{ __('admin.occasions.materials') }}</x-admin.th>
                    @foreach ($valueRows as $valueRow)
                        <x-admin.th>{{ $valueRow->label() }}</x-admin.th>
                    @endforeach
                    <x-admin.th>{{ __('admin.diagnoses.occasion_point') }}</x-admin.th>
                    <x-admin.th>{{ __('processes.occasions.effective') }}</x-admin.th>
                </x-slot:head>
                @foreach ($occasions as $entry)
                    <tr wire:key="occasion-{{ $entry['occasion']->id }}" class="align-top">
                        <td class="px-3 py-2">
                            <span class="font-semibold">{{ $entry['occasion']->occurred_at->format('Y.m.d.') }}</span>
                            @if ($entry['occasion']->process_id !== $diagnosis->process_id)
                                <span class="block text-xs text-grey-700">{{ $entry['occasion']->process->name }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <ul class="flex flex-col gap-0.5">
                                @foreach ($entry['materials'] as $material)
                                    <li><a href="{{ route('admin.materials.show', $material) }}">{{ $material->is_situation ? __('processes.occasions.situation') : ($material->original_name ?? $material->source->label()) }}</a> · {{ $material->user?->name ?? $material->creator?->name ?? '–' }}</li>
                                @endforeach
                            </ul>
                            @if ($entry['quotes'] !== [])
                                <ul class="mt-2 flex flex-col gap-1 text-xs text-grey-700">
                                    @foreach (array_slice($entry['quotes'], 0, 3) as $quote)
                                        <li>„{{ $quote }}”</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                        @foreach ($valueRows as $valueRow)
                            <td class="px-3 py-2">{{ ($entry['scores'][$valueRow->value_id] ?? null) === null ? '–' : number_format($entry['scores'][$valueRow->value_id], 1, ',', '') }}</td>
                        @endforeach
                        <td class="px-3 py-2 font-semibold">{{ $entry['point'] === null ? '–' : number_format($entry['point'], 1, ',', '') }}</td>
                        <td class="px-3 py-2 text-sm">
                            {{ collect($entry['effectiveness'])->filter()->map(fn ($count, $answer) => __('processes.occasions.effectiveness.'.$answer).' '.$count)->implode(' · ') ?: '–' }}
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>
        </section>
    @endif

    @if ($diagnosis->level !== \App\Enums\DiagnosisLevel::Process)
        {{-- The brand base: four fixed rows (04 · 4.4) --}}
        <section class="flex flex-col gap-3" aria-labelledby="tree-title">
            <h2 id="tree-title" class="text-lg font-semibold">{{ __('admin.diagnoses.tree_block') }}</h2>

            @include('livewire.admin.diagnoses.partials.rows-table', ['rows' => $treeRows, 'removable' => false])
        </section>
    @endif

    @if ($canChangeStatus)
        {{-- A mégis javaslata: the end of the user's report (4.6) --}}
        @php($recommendationMax = \App\Models\DiagnosisText::maxLength('recommendation'))
        <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-3" aria-labelledby="recommendation-title">
            <div class="flex flex-col gap-1">
                <h2 id="recommendation-title" class="text-lg font-semibold">{{ __('report.recommendation') }}</h2>
                <p class="text-sm text-grey-700">{{ __('admin.diagnoses.recommendation.help') }}</p>
            </div>
            @if ($isEditable)
                <form wire:submit="saveRecommendation" class="flex flex-col gap-3">
                    <div class="flex flex-col gap-1.5">
                        <label for="recommendation" class="text-sm font-medium">{{ __('report.recommendation') }}</label>
                        {{-- EasyMDE takes over the raw textarea (x-ref="input"), so it stays raw rather than an x-form.textarea. --}}
                        <div wire:ignore x-data="markdownEditor({ max: {{ $recommendationMax }}, heading: true, labels: @js(__('admin.markdown_editor')) })">
                            <textarea id="recommendation" x-ref="input" wire:model="recommendation" rows="4" aria-describedby="recommendation-help" @error('recommendation') aria-invalid="true" @enderror class="field w-full"></textarea>
                        </div>
                        <p id="recommendation-help" class="text-sm text-grey-700">{{ __('admin.reading.max_length', ['max' => $recommendationMax]) }}</p>
                        <x-form.error name="recommendation" id="recommendation" />
                    </div>
                    <x-ui.button type="submit" size="sm" class="self-start">{{ __('admin.common.save') }}</x-ui.button>
                </form>
            @elseif (filled($recommendation))
                <div class="prose-megis">{!! \Illuminate\Support\Str::markdown($recommendation, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}</div>
            @else
                <p class="text-grey-700">–</p>
            @endif
        </x-ui.card>
    @endif

    {{-- Status log (04 · 4.3) --}}
    <section class="flex flex-col gap-2" aria-labelledby="history-title">
        <h2 id="history-title" class="text-lg font-semibold">{{ __('admin.diagnoses.history') }}</h2>
        <ul class="flex flex-col gap-1 text-sm text-grey-700">
            @foreach ($diagnosis->transitions->sortByDesc('id') as $transition)
                <li>
                    {{ $transition->created_at->format('Y.m.d. H:i') }} ·
                    @if ($transition->from_stage && $transition->to_stage && $transition->from_stage !== $transition->to_stage)
                        {{-- A main diagnosis moves in stages; its status changes only with some of them (17 · 17.5). --}}
                        {{ $transition->from_stage->label() }} <x-megis.icon name="arrow-right" class="align-middle" :label="__('admin.diagnoses.changed_to')" /> {{ $transition->to_stage->label() }}
                        @if ($transition->from_status !== $transition->to_status)
                            ({{ $transition->from_status?->label() }} <x-megis.icon name="arrow-right" class="align-middle" :label="__('admin.diagnoses.changed_to')" /> {{ $transition->to_status->label() }})
                        @endif
                    @elseif ($transition->from_status)
                        {{ $transition->from_status->label() }} <x-megis.icon name="arrow-right" class="align-middle" :label="__('admin.diagnoses.changed_to')" /> {{ $transition->to_status->label() }}
                    @else
                        {{ __('admin.diagnoses.created_status', ['status' => $transition->to_status->label()]) }}
                    @endif
                    · {{ $transition->creator?->name ?? __('admin.versions.system') }}
                    @if ($transition->reason)
                        · „{{ $transition->reason }}”
                    @endif
                </li>
            @endforeach
        </ul>
    </section>
</div>
