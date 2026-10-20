{{-- Olvasat (A3, 20): measuring points · evidence · what the leader reads. --}}
@php
    $stateTone = ['pending' => 'muted', 'approved' => 'green', 'overridden' => 'yellow'];
    $decimal = fn ($number) => $number === null ? '–' : number_format((float) $number, 1, ',', '');
@endphp

<div class="flex max-w-7xl flex-col gap-6">
    <x-admin.main-diagnosis-tabs :diagnosis="$diagnosis" />

    <x-ui.flash />

    @unless ($isOpen)
        <p class="rounded-field bg-grey-100 px-3 py-2 text-sm">{{ __('admin.reading.not_started') }}</p>
    @endunless

    @if ($feedback)
        {{-- The feedback being built in (22 · 22.2, 28 · 28.1) --}}
        <section class="flex flex-col gap-2 rounded-card border-2 border-yellow bg-yellow-pale p-4" aria-labelledby="feedback-title">
            <h2 id="feedback-title" class="font-semibold">{{ __('admin.reading.feedback.title', ['chapter' => __('report.chapters.'.$feedback->chapter), 'answer' => __('report.feedback.answers.'.$feedback->answer)]) }}</h2>
            <p>„{{ $feedback->text }}” <span class="text-sm text-grey-700">· {{ $feedback->user?->name }} · {{ $feedback->created_at->format('Y.m.d.') }}</span></p>
            @if ($feedback->incorporated_at)
                <p class="text-sm">{{ __('admin.reading.feedback.done', ['date' => $feedback->incorporated_at->format('Y.m.d.')]) }}</p>
            @else
                <p class="text-sm text-grey-700">{{ __('admin.reading.feedback.help') }}</p>
                <x-ui.button size="sm" class="self-start" wire:click="incorporateFeedback">{{ __('admin.reading.feedback.mark') }}</x-ui.button>
            @endif
        </section>
    @endif

    <p class="flex flex-wrap items-center gap-3 text-sm" aria-live="polite">
        <strong class="text-lg">{{ __('admin.reading.counter', ['done' => $progress['rows'], 'total' => $progress['rows_total']]) }}</strong>
        <span>{{ __('admin.reading.claims_counter', ['done' => $progress['claims'], 'total' => $progress['claims_total']]) }}</span>
        <span>{{ __('admin.reading.texts_counter', ['done' => $progress['texts'], 'total' => $progress['texts_total']]) }}</span>
        @if ($progress['complete'])
            <x-ui.badge tone="green">{{ __('admin.reading.complete') }}</x-ui.badge>
        @endif
        @if (($main->calculation['drafts']['status'] ?? null) === 'failed')
            <x-ui.badge tone="coral">{{ __('admin.reading.drafts_failed') }}</x-ui.badge>
        @endif
    </p>

    <div class="grid gap-4 lg:grid-cols-[17rem_minmax(0,1fr)_19rem]">
        {{-- Mérőpontok --}}
        <nav class="flex flex-col gap-4" aria-label="{{ __('admin.reading.points') }}">
            @foreach ($blocks as $block)
                <section class="flex flex-col gap-1" aria-labelledby="block-{{ $block['kind']->value }}">
                    <div class="flex items-center justify-between gap-2">
                        <h2 id="block-{{ $block['kind']->value }}" class="text-sm font-semibold uppercase tracking-wide text-grey-700">{{ __('admin.reading.blocks.'.$block['kind']->value) }}</h2>
                        @if ($isOpen && ! in_array($block['kind'], [\App\Enums\DiagnosisRowKind::Area, \App\Enums\DiagnosisRowKind::TreeElement], true))
                            <button type="button" wire:click="approveBlock('{{ $block['kind']->value }}')" wire:confirm="{{ __('admin.reading.confirm_block') }}" class="text-xs font-semibold underline">{{ __('admin.reading.approve_block') }}</button>
                        @endif
                    </div>
                    <ul class="flex flex-col gap-1">
                        @foreach ($block['rows'] as $row)
                            <li wire:key="point-{{ $row->id }}">
                                <button
                                    type="button"
                                    wire:click="selectRow({{ $row->id }})"
                                    @class([
                                        'flex w-full items-center justify-between gap-2 rounded-field px-3 py-2 text-left text-sm',
                                        'bg-charcoal text-cream' => $selected?->id === $row->id,
                                        'bg-white hover:bg-paper' => $selected?->id !== $row->id,
                                    ])
                                    @if ($selected?->id === $row->id) aria-current="true" @endif
                                >
                                    <span class="min-w-0 truncate">{{ $row->label() }}</span>
                                    <span class="flex shrink-0 items-center gap-1.5">
                                        <span class="font-semibold tabular-nums">{{ $row->scoreLabel() ?: '·' }}</span>
                                        <x-ui.badge :tone="$selected?->id === $row->id ? 'muted' : $stateTone[$row->reviewState()]">{{ $selected?->id === $row->id ? __('admin.reading.states.now') : __('admin.reading.states.'.$row->reviewState()) }}</x-ui.badge>
                                    </span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </nav>

        {{-- Bizonyíték --}}
        <section class="flex min-w-0 flex-col gap-4 rounded-card border border-grey-250 bg-white p-5" aria-labelledby="evidence-title">
            @if ($selected)
                @php($stats = $selected->aggregation_stats ?? [])
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 id="evidence-title" class="text-xl font-semibold">{{ $selected->label() }}</h2>
                        <p class="text-sm text-grey-700">
                            {{ __('admin.diagnoses.score') }}: <strong>{{ $selected->scoreLabel() ?: '–' }}</strong>
                            @if ($selected->overridden_at)
                                · {{ __('admin.diagnoses.override.automatic', ['value' => $decimal($selected->automatic_score)]) }}
                            @endif
                            @if (array_key_exists('n', $stats))
                                · n = {{ $stats['n'] }}
                            @endif
                            @if ($selected->approved_at)
                                · {{ __('admin.reading.approved_by', ['name' => $selected->approver?->name ?? '–', 'date' => $selected->approved_at->format('Y.m.d. H:i')]) }}
                            @endif
                        </p>
                        @if (isset($stats['leader']) || isset($stats['peers']))
                            <p class="text-sm">{{ __('admin.reading.experience_line', ['leader' => $decimal($stats['leader'] ?? null), 'team' => $decimal($selected->experience), 'situations' => $decimal($stats['situations'] ?? null)]) }}</p>
                        @endif
                        @if ($selected->override_reason)
                            <p class="mt-1 text-sm text-grey-700">{{ __('admin.diagnoses.override.reason') }}: {{ $selected->override_reason }}</p>
                        @endif
                    </div>

                    @if ($isOpen)
                        <div class="flex flex-wrap gap-2">
                            @if ($selected->approved_at)
                                <x-ui.button tone="outline" size="sm" wire:click="reopen">{{ __('admin.reading.reopen') }}</x-ui.button>
                            @elseif (! $byHand)
                                <x-ui.button size="sm" wire:click="approve">{{ __('admin.reading.approve') }}</x-ui.button>
                            @endif
                            @unless ($byHand)
                                <x-ui.button tone="outline" size="sm" wire:click="startOverride">{{ __('admin.diagnoses.override.edit') }}</x-ui.button>
                            @endunless
                            <x-ui.button tone="outline" size="sm" wire:click="markInsufficient">{{ __('admin.reading.insufficient') }}</x-ui.button>
                        </div>
                    @endif
                </div>

                @if ($isOpen && $overriding)
                    <form wire:submit="saveOverride" class="grid gap-3 rounded-field bg-paper p-4 md:grid-cols-2">
                        <x-form.input name="overrideScore" wire:model="overrideScore" :label="__('admin.diagnoses.score')" :help="__('admin.diagnoses.process_point_help')" required />
                        <x-form.textarea name="overrideReason" wire:model="overrideReason" :label="__('admin.diagnoses.override.reason')" :help="__('admin.reading.reason_visible')" rows="2" required />
                        <div class="flex gap-2 md:col-span-2">
                            <x-ui.button type="submit" size="sm">{{ __('admin.common.save') }}</x-ui.button>
                            <x-ui.button tone="outline" size="sm" wire:click="$set('overriding', false)">{{ __('admin.common.cancel') }}</x-ui.button>
                        </div>
                    </form>
                @endif

                @if ($isOpen && $byHand)
                    <form wire:submit="saveHandScore" class="flex flex-wrap items-end gap-3 rounded-field bg-paper p-4">
                        <x-form.select name="handScore" wire:model="handScore" :label="__('admin.reading.hand_score')" :help="__('admin.reading.hand_score_help')">
                            <option value="">–</option>
                            @foreach (range(1, 6) as $number)
                                <option value="{{ $number }}">{{ $number }}</option>
                            @endforeach
                            <option value="{{ \App\Services\Diagnoses\DiagnosisService::NO_TRACE }}">{{ __('admin.diagnoses.score_no_trace') }}</option>
                        </x-form.select>
                        <x-ui.button type="submit" size="sm" class="mb-7">{{ __('admin.reading.score_and_approve') }}</x-ui.button>
                    </form>
                @endif

                <div class="flex flex-col gap-2">
                    <h3 class="font-semibold">{{ __('admin.reading.traces.title', ['count' => $traces->whereNull('excluded_at')->count()]) }}</h3>
                    @forelse ($traces as $trace)
                        <article wire:key="trace-{{ $trace->id }}" @class(['flex flex-col gap-1 rounded-field border p-3 text-sm', 'border-grey-250 bg-grey-100 opacity-60' => $trace->excluded_at, 'border-yellow bg-yellow-pale' => $trace->highlighted_at, 'border-grey-250' => ! $trace->excluded_at && ! $trace->highlighted_at])>
                            <p>„{{ $trace->quote }}”</p>
                            <p class="text-xs text-grey-700">
                                {{ __('admin.reading.traces.sources.'.$trace->source_type) }}
                                · {{ $trace->role ? __('admin.reading.traces.roles.'.$trace->role) : '–' }}
                                @if ($trace->user_id) · {{ $userNames->get($trace->user_id) }} @endif
                                · {{ $trace->recorded_on?->format('Y.m.d.') ?? '–' }}
                                @if ($trace->material)
                                    · <a href="{{ route('admin.materials.show', $trace->material) }}">{{ __('admin.reading.traces.open_material') }}</a>
                                @endif
                                @if ($trace->created_by) · {{ __('admin.reading.traces.by_hand') }} @endif
                            </p>
                            @if ($isOpen)
                                <p class="flex gap-3 text-xs">
                                    <button type="button" wire:click="toggleTrace({{ $trace->id }})" class="underline">{{ $trace->excluded_at ? __('admin.reading.traces.include') : __('admin.reading.traces.exclude') }}</button>
                                    @unless ($trace->highlighted_at || $trace->excluded_at)
                                        <button type="button" wire:click="highlightTrace({{ $trace->id }})" class="underline">{{ __('admin.reading.traces.highlight') }}</button>
                                    @endunless
                                </p>
                            @endif
                        </article>
                    @empty
                        <p class="text-sm text-grey-600">{{ __('admin.reading.traces.empty') }}</p>
                    @endforelse
                </div>

                @if ($occasions->isNotEmpty())
                    <div class="flex flex-col gap-1 text-sm">
                        <h3 class="font-semibold">{{ __('admin.occasions.title') }}</h3>
                        @foreach ($occasions as $entry)
                            <p>{{ $entry['occasion']->occurred_at->format('Y.m.d.') }} · {{ $decimal($entry['point']) }} · {{ trans_choice('admin.diagnoses.process_aggregation.materials', $entry['materials']->count(), ['count' => $entry['materials']->count()]) }}</p>
                        @endforeach
                    </div>
                @endif

                @if ($isOpen && $byHand)
                    {{-- Evidence for an area or a tree element, from the diagnosis' traces (20 · 20.2) --}}
                    <div class="flex flex-col gap-2 rounded-field bg-paper p-4">
                        <h3 class="font-semibold">{{ __('admin.reading.traces.pick') }}</h3>
                        <div class="flex flex-wrap items-end gap-2">
                            <x-form.select name="traceFilter.block" wire:model.live="traceFilter.block" :label="__('admin.reading.traces.block')">
                                <option value="">{{ __('admin.common.all') }}</option>
                                <option value="value">{{ __('admin.reading.blocks.value') }}</option>
                                <option value="process">{{ __('admin.reading.blocks.process') }}</option>
                            </x-form.select>
                            <x-form.select name="traceFilter.source" wire:model.live="traceFilter.source" :label="__('admin.reading.traces.source')">
                                <option value="">{{ __('admin.common.all') }}</option>
                                @foreach (\App\Models\EvidenceTrace::SOURCES as $source)
                                    <option value="{{ $source }}">{{ __('admin.reading.traces.sources.'.$source) }}</option>
                                @endforeach
                            </x-form.select>
                            <x-form.input name="traceFilter.word" wire:model.live.debounce.300ms="traceFilter.word" :label="__('admin.reading.traces.word')" />
                        </div>
                        <ul class="flex flex-col gap-1 text-sm">
                            @forelse ($candidates as $candidate)
                                <li wire:key="candidate-{{ $candidate->id }}" class="flex items-start justify-between gap-3">
                                    <span>„{{ \Illuminate\Support\Str::limit($candidate->quote, 160) }}” <span class="text-xs text-grey-700">· {{ $candidate->row->label() }}</span></span>
                                    <button type="button" wire:click="useTrace({{ $candidate->id }})" class="shrink-0 text-xs font-semibold underline">{{ __('admin.reading.traces.use') }}</button>
                                </li>
                            @empty
                                <li class="text-grey-600">{{ __('admin.common.empty') }}</li>
                            @endforelse
                        </ul>
                    </div>
                @endif

                @if ($isOpen)
                    <details class="rounded-field border border-grey-250 p-3 text-sm">
                        <summary class="cursor-pointer font-semibold">{{ __('admin.reading.traces.add') }}</summary>
                        <form wire:submit="addTrace" class="mt-3 grid gap-3 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <x-form.textarea name="trace.quote" wire:model="trace.quote" :label="__('admin.reading.traces.quote')" rows="2" required />
                            </div>
                            <x-form.select name="trace.material_id" wire:model="trace.material_id" :label="__('admin.reading.traces.material')">
                                <option value="">–</option>
                                @foreach ($materials as $material)
                                    <option value="{{ $material->id }}">{{ $material->is_situation ? __('processes.occasions.situation') : ($material->original_name ?? $material->source->label()) }} · {{ $material->created_at->format('m.d.') }}</option>
                                @endforeach
                            </x-form.select>
                            <x-form.input name="trace.recorded_on" type="date" wire:model="trace.recorded_on" :label="__('admin.reading.traces.date')" />
                            <x-form.select name="trace.role" wire:model="trace.role" :label="__('admin.reading.traces.role')">
                                @foreach (\App\Models\EvidenceTrace::ROLES as $role)
                                    <option value="{{ $role }}">{{ __('admin.reading.traces.roles.'.$role) }}</option>
                                @endforeach
                            </x-form.select>
                            <x-form.select name="trace.source_type" wire:model="trace.source_type" :label="__('admin.reading.traces.source')">
                                @foreach (\App\Models\EvidenceTrace::SOURCES as $source)
                                    <option value="{{ $source }}">{{ __('admin.reading.traces.sources.'.$source) }}</option>
                                @endforeach
                            </x-form.select>
                            <div class="md:col-span-2">
                                <x-ui.button type="submit" tone="outline" size="sm">{{ __('admin.reading.traces.add_button') }}</x-ui.button>
                            </div>
                        </form>
                    </details>
                @endif
            @else
                <p id="evidence-title" class="text-grey-600">{{ __('admin.reading.choose_row') }}</p>
            @endif
        </section>

        {{-- Amit a vezető olvas --}}
        <aside class="flex flex-col gap-3 rounded-card bg-paper p-5" aria-labelledby="leader-reads">
            <h2 id="leader-reads" class="font-semibold">{{ __('admin.reading.leader_reads') }}</h2>
            @if ($selected)
                <p class="text-lg font-semibold">{{ $selected->label() }} · {{ $selected->scoreLabel() ?: '–' }}</p>
                @if (! in_array($selected->kind, [\App\Enums\DiagnosisRowKind::Area, \App\Enums\DiagnosisRowKind::Process], true))
                    @if ($showsExperience && $selected->gap() !== null)
                        <p>{{ __('admin.reading.gap_sentence', ['experience' => $decimal($selected->experience), 'score' => $decimal($selected->score)]) }}</p>
                    @elseif (! $showsExperience)
                        <p class="text-grey-600">{{ trans_choice('diagnosis.home.missing_peers', max(1, $missingPeers), ['count' => max(1, $missingPeers)]) }}</p>
                    @endif
                @endif
                @forelse ($rowClaims as $rowClaim)
                    <div wire:key="leader-claim-{{ $rowClaim->id }}" class="flex flex-col gap-1 rounded-field bg-white p-3 text-sm">
                        <x-ui.badge :tone="$rowClaim->isApproved() ? 'green' : 'muted'">{{ __('admin.reading.claims.kinds.'.($main->isSnapshot() && $rowClaim->kind === 'crack' ? 'breakpoint' : $rowClaim->kind)) }}</x-ui.badge>
                        <p>{{ $rowClaim->text }}</p>
                        <p class="text-xs text-grey-600">{{ $rowClaim->isApproved() ? __('admin.reading.claims.marker') : __('admin.reading.claims.marker_draft') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-grey-600">{{ __('admin.reading.claims.none_for_row') }}</p>
                @endforelse
            @endif
        </aside>
    </div>

    {{-- Állítások (20 · 20.3) --}}
    <section class="flex flex-col gap-3" aria-labelledby="claims-title">
        <div class="flex items-center justify-between gap-3">
            <h2 id="claims-title" class="text-lg font-semibold">{{ __('admin.reading.claims.title') }}</h2>
            @if ($isOpen)
                <x-ui.button tone="outline" size="sm" wire:click="newClaim">{{ __('admin.reading.claims.new') }}</x-ui.button>
            @endif
        </div>

        @if ($editingClaim)
            <form wire:submit="saveClaim" class="grid gap-3 rounded-card border border-grey-250 bg-white p-4 md:grid-cols-3">
                <x-form.select name="claim.diagnosis_row_id" wire:model.live="claim.diagnosis_row_id" :label="__('admin.reading.claims.row')">
                    <option value="">–</option>
                    @foreach ($rows as $row)
                        <option value="{{ $row->id }}">{{ __('admin.reading.blocks.'.$row->kind->value) }} · {{ $row->label() }}</option>
                    @endforeach
                </x-form.select>
                <x-form.select name="claim.kind" wire:model="claim.kind" :label="__('admin.reading.claims.kind')">
                    @foreach (\App\Models\DiagnosisClaim::KINDS as $kind)
                        <option value="{{ $kind }}">{{ __('admin.reading.claims.kinds.'.($main->isSnapshot() && $kind === 'crack' ? 'breakpoint' : $kind)) }}</option>
                    @endforeach
                </x-form.select>
                <div class="md:col-span-3">
                    <x-form.textarea name="claim.text" wire:model="claim.text" :label="__('admin.reading.claims.text')" :help="__('admin.reading.max_length', ['max' => \App\Models\DiagnosisText::DEFAULT_MAX_LENGTH])" rows="2" maxlength="{{ \App\Models\DiagnosisText::DEFAULT_MAX_LENGTH }}" required />
                </div>
                <fieldset class="flex flex-col gap-1 text-sm md:col-span-3">
                    <legend class="font-semibold">{{ __('admin.reading.claims.traces') }}</legend>
                    @forelse ($claimTraces as $claimTrace)
                        <label class="flex items-start gap-2">
                            <input type="checkbox" value="{{ $claimTrace->id }}" wire:model="claim.trace_ids" class="mt-1 size-4 accent-charcoal">
                            <span>„{{ \Illuminate\Support\Str::limit($claimTrace->quote, 200) }}”</span>
                        </label>
                    @empty
                        <p class="text-grey-600">{{ __('admin.reading.traces.empty') }}</p>
                    @endforelse
                    <x-form.error name="claim.trace_ids" id="claim-trace-ids" />
                </fieldset>
                <div class="flex gap-2 md:col-span-3">
                    <x-ui.button type="submit" size="sm">{{ __('admin.common.save') }}</x-ui.button>
                    <x-ui.button tone="outline" size="sm" wire:click="$set('editingClaim', null)">{{ __('admin.common.cancel') }}</x-ui.button>
                </div>
            </form>
        @endif

        <x-admin.table>
            <x-slot:head>
                <x-admin.th>{{ __('admin.reading.claims.kind') }}</x-admin.th>
                <x-admin.th>{{ __('admin.reading.claims.row') }}</x-admin.th>
                <x-admin.th>{{ __('admin.reading.claims.text') }}</x-admin.th>
                <x-admin.th>{{ __('admin.reading.claims.traces') }}</x-admin.th>
                <x-admin.th><span class="sr-only">{{ __('admin.common.actions') }}</span></x-admin.th>
            </x-slot:head>
            @forelse ($claims as $item)
                <tr wire:key="claim-{{ $item->id }}">
                    <td class="px-3 py-2"><x-ui.badge :tone="$item->isApproved() ? 'green' : 'muted'">{{ __('admin.reading.claims.kinds.'.($main->isSnapshot() && $item->kind === 'crack' ? 'breakpoint' : $item->kind)) }}</x-ui.badge></td>
                    <td class="px-3 py-2">{{ $item->row->label() }}</td>
                    <td class="px-3 py-2">{{ $item->text }}</td>
                    <td class="px-3 py-2">{{ count($item->trace_ids ?? []) }}</td>
                    <td class="px-3 py-2 text-right">
                        @if ($isOpen)
                            <div class="flex justify-end gap-2">
                                @unless ($item->isApproved())
                                    <x-ui.button size="sm" wire:click="approveClaim({{ $item->id }})">{{ __('admin.reading.approve') }}</x-ui.button>
                                @endunless
                                <x-ui.button tone="outline" size="sm" wire:click="editClaim({{ $item->id }})">{{ __('admin.common.edit') }}</x-ui.button>
                                <x-ui.button tone="outline" size="sm" wire:click="deleteClaim({{ $item->id }})" wire:confirm="{{ __('admin.reading.claims.confirm_delete') }}">{{ __('admin.common.delete') }}</x-ui.button>
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-3 py-6 text-center text-grey-600">{{ __('admin.reading.claims.empty') }}</td></tr>
            @endforelse
        </x-admin.table>
    </section>

    {{-- A legnagyobb rés és az olvasat szövegei (20 · 20.4) --}}
    <section class="flex flex-col gap-4" aria-labelledby="texts-title">
        <h2 id="texts-title" class="text-lg font-semibold">{{ __('admin.reading.texts.title') }}</h2>

        <div class="flex flex-wrap items-end gap-3 rounded-card border border-grey-250 bg-white p-4">
            <x-form.select name="gapRowId" wire:model="gapRowId" :label="__('admin.diagnoses.calculation.biggest_gap')" :help="$showsExperience ? ($gapRow ? __('admin.reading.gap_now', ['label' => $gapRow->label()]) : null) : __('admin.reading.gap_below_threshold')">
                <option value="">–</option>
                @foreach ($gapCandidates as $candidate)
                    <option value="{{ $candidate->id }}">{{ $candidate->label() }}@if ($showsExperience && $candidate->gapLabel() !== '') ({{ $candidate->gapLabel() }})@endif</option>
                @endforeach
            </x-form.select>
            @if ($isOpen)
                <x-ui.button size="sm" class="mb-7" wire:click="approveGap">{{ $main->biggest_gap_approved_at ? __('admin.reading.approved') : __('admin.reading.approve') }}</x-ui.button>
            @endif
        </div>

        @foreach ($requiredTexts as $key)
            @php($model = $textModels->get($key))
            <form wire:key="text-{{ $key }}" wire:submit="saveText('{{ $key }}')" class="flex flex-col gap-3 rounded-card border border-grey-250 bg-white p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="font-semibold">{{ __('admin.reading.texts.keys.'.$key) }}</h3>
                    @if ($model)
                        <x-ui.badge :tone="$model->isApproved() ? 'green' : 'muted'">{{ $model->isApproved() ? __('admin.reading.states.approved') : ($model->ai_call_id ? __('admin.reading.texts.ai_draft') : __('admin.reading.states.pending')) }}</x-ui.badge>
                    @endif
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="text-{{ $key }}" class="text-sm font-medium">{{ __('admin.reading.texts.keys.'.$key) }}</label>
                    <div wire:ignore x-data="markdownEditor({ max: {{ \App\Models\DiagnosisText::maxLength($key) }} })">
                        <textarea id="text-{{ $key }}" x-ref="input" wire:model="texts.{{ $key }}" rows="3" aria-describedby="text-{{ $key }}-help" class="w-full rounded-field border border-grey-350 bg-white px-3.5 py-2.5 text-base"></textarea>
                    </div>
                    <p id="text-{{ $key }}-help" class="text-sm text-grey-600">{{ __('admin.reading.max_length', ['max' => \App\Models\DiagnosisText::maxLength($key)]) }}</p>
                    <x-form.error :name="'texts.'.$key" :id="'texts-'.$key" />
                </div>
                @if ($key === 'recommendation')
                    <div class="grid gap-3 md:grid-cols-3">
                        <x-form.select name="recommendation.row_id" wire:model="recommendation.row_id" :label="__('admin.reading.texts.recommendation_row')">
                            <option value="">–</option>
                            @foreach ($rows as $row)
                                <option value="{{ $row->id }}">{{ $row->label() }}</option>
                            @endforeach
                        </x-form.select>
                        <x-form.input name="recommendation.from" type="number" min="1" max="6" wire:model="recommendation.from" :label="__('admin.reading.texts.from')" />
                        <x-form.input name="recommendation.to" type="number" min="1" max="6" wire:model="recommendation.to" :label="__('admin.reading.texts.to')" />
                    </div>
                @endif
                @if ($isOpen)
                    <div class="flex gap-2">
                        <x-ui.button type="submit" tone="outline" size="sm">{{ __('admin.common.save') }}</x-ui.button>
                        @if ($model && ! $model->isApproved())
                            <x-ui.button size="sm" wire:click="approveText('{{ $key }}')">{{ __('admin.reading.approve') }}</x-ui.button>
                        @endif
                    </div>
                @endif
            </form>
        @endforeach
    </section>

    {{-- Napló (20 · 20.5) --}}
    <section class="flex flex-col gap-2" aria-labelledby="log-title">
        <h2 id="log-title" class="text-lg font-semibold">{{ __('admin.reading.log') }}</h2>
        <ul class="flex flex-col gap-1 text-sm text-grey-700">
            @forelse ($events as $event)
                <li>
                    {{ $event->created_at->format('Y.m.d. H:i') }} · {{ $event->actor?->name ?? __('admin.versions.system') }} · {{ __('admin.reading.actions.'.$event->action) }}
                    @if ($event->subject instanceof \App\Models\DiagnosisRow) · {{ $event->subject->label() }} @endif
                    @if (isset($event->payload['from']) || isset($event->payload['to'])) · {{ $event->payload['from'] ?? '–' }} → {{ $event->payload['to'] ?? '–' }} @endif
                    @if (isset($event->payload['reason'])) · {{ $event->payload['reason'] }} @endif
                </li>
            @empty
                <li>{{ __('admin.common.empty') }}</li>
            @endforelse
        </ul>
    </section>
</div>
