@php
    $levelLabel = fn (string $level) => \App\Enums\CapabilityLevel::from($level)->label();
    $confidenceLabel = fn ($confidence) => $confidence === null ? __('admin.common.none') : $confidence->label();
    $basisLine = fn (array $rule) => $rule['rule'].' · '.__('capabilities.basis.'.$rule['key'], $rule['params'] ?? []);
@endphp

{{-- Képességek of a diagnosis (NM-12). --}}
<div @class(['flex flex-col gap-6', 'max-w-7xl' => $main, 'max-w-5xl' => ! $main])>
    <x-ui.flash />

    @if ($main)
        <x-admin.main-diagnosis-tabs :diagnosis="$diagnosis" />
    @else
        <x-admin.page-header :title="__('capabilities.review.title').' · '.$diagnosis->name" :spaced="false">
            <x-slot:actions>
                <x-ui.button tone="outline" size="sm" :href="route('admin.diagnoses.show', $diagnosis)">{{ __('capabilities.review.back') }}</x-ui.button>
            </x-slot:actions>
        </x-admin.page-header>
    @endif

    <p class="max-w-3xl text-grey-700">{{ __('capabilities.review.lead') }}</p>

    {{-- The materials and where their measurement stands. --}}
    <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-3" aria-labelledby="capability-materials">
        <x-ui.section-header id="capability-materials" :title="__('capabilities.review.materials')" />
        @forelse ($materials as $material)
            <div wire:key="material-{{ $material->id }}" class="flex flex-wrap items-center justify-between gap-2">
                <span>
                    {{ $material->original_name ?? $material->context->label() }}
                    <x-ui.badge tone="muted">{{ $material->capability_status?->label() ?? __('capabilities.review.not_measured') }}</x-ui.badge>
                </span>
                <x-ui.button
                    tone="outline"
                    size="sm"
                    icon="refresh"
                    wire:click="rerun({{ $material->id }})"
                    wire:confirm="{{ __('capabilities.review.confirm_rerun') }}"
                    :data-confirm-label="__('capabilities.review.rerun')"
                >{{ __('capabilities.review.rerun') }}</x-ui.button>
            </div>
        @empty
            <p class="text-grey-700">{{ __('capabilities.review.no_materials') }}</p>
        @endforelse
    </x-ui.card>

    @if ($proposedCount > 0)
        <x-ui.notice tone="warning">{{ __('capabilities.review.proposed_notice', ['count' => $proposedCount]) }}</x-ui.notice>
    @endif

    {{-- Per level: the capabilities, their state and their findings. --}}
    @forelse ($groups as $level => $assessments)
        <section class="flex flex-col gap-4" aria-labelledby="level-{{ $level }}">
            <h2 id="level-{{ $level }}" class="text-lg font-semibold text-charcoal">{{ __('capabilities.review.level', ['level' => $levelLabel($level)]) }}</h2>

            @foreach ($assessments as $assessment)
                @php($rows = $findings->get($assessment->capability_id.'|'.$level, collect()))
                <x-ui.card as="article" bordered padding="lg" class="flex flex-col gap-4" wire:key="assessment-{{ $assessment->id }}" aria-labelledby="assessment-{{ $assessment->id }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex flex-col gap-1">
                            <h3 id="assessment-{{ $assessment->id }}" class="font-semibold">
                                <span class="font-mono text-sm">{{ $assessment->capability->code }}</span> · {{ $assessment->capability->name }}
                            </h3>
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.badge :tone="$assessment->state === \App\Enums\CapabilityState::Present ? 'green' : ($assessment->state === \App\Enums\CapabilityState::Absent ? 'coral' : 'muted')">{{ $assessment->state->label() }}</x-ui.badge>
                                <x-ui.badge>{{ __('capabilities.review.confidence_value', ['value' => $confidenceLabel($assessment->confidence)]) }}</x-ui.badge>
                                @if ($assessment->confidence === \App\Enums\CapabilityConfidence::Low)
                                    <x-ui.badge tone="muted">{{ __('capabilities.review.little_data') }}</x-ui.badge>
                                @endif
                                @if ($assessment->uneven)
                                    <x-ui.badge tone="yellow">{{ __('capabilities.review.uneven') }}</x-ui.badge>
                                @endif
                                @if ($assessment->capability->tempting)
                                    <x-ui.badge tone="yellow">{{ __('capabilities.review.tempting') }}</x-ui.badge>
                                @endif
                                @if ($assessment->blocker)
                                    <x-ui.badge tone="dark">{{ __('capabilities.review.blocked', ['code' => $assessment->blocker->code]) }}</x-ui.badge>
                                @endif
                                @if ($assessment->isApproved())
                                    <x-ui.badge tone="green">{{ __('capabilities.review.approved') }}</x-ui.badge>
                                @endif
                            </div>
                            @if ($assessment->isOverridden())
                                <p class="text-sm text-grey-700">
                                    {{ __('capabilities.review.automatic', ['state' => $assessment->automatic_state->label(), 'confidence' => $confidenceLabel($assessment->automatic_confidence)]) }}
                                    · {{ __('capabilities.review.override_reason', ['reason' => $assessment->override_reason]) }}
                                </p>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @unless ($assessment->isApproved())
                                <x-ui.button size="sm" wire:click="approve({{ $assessment->id }})">{{ __('capabilities.review.approve') }}</x-ui.button>
                            @endunless
                            <x-ui.button tone="outline" size="sm" wire:click="startOverride({{ $assessment->id }})">{{ __('capabilities.review.override') }}</x-ui.button>
                            @if ($assessment->isOverridden())
                                <x-ui.button tone="outline" size="sm" wire:click="resetAssessment({{ $assessment->id }})">{{ __('capabilities.review.reset') }}</x-ui.button>
                            @endif
                        </div>
                    </div>

                    @if ($overriding === $assessment->id)
                        <x-form.inline as="form" wire:submit="saveOverride">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <x-form.select name="overrideState" wire:model="overrideState" :label="__('capabilities.review.state')" required>
                                    @foreach (\App\Enums\CapabilityState::cases() as $state)
                                        <option value="{{ $state->value }}">{{ $state->label() }}</option>
                                    @endforeach
                                </x-form.select>
                                <x-form.select name="overrideConfidence" wire:model="overrideConfidence" :label="__('capabilities.review.confidence')">
                                    <option value="">{{ __('admin.common.none') }}</option>
                                    @foreach (\App\Enums\CapabilityConfidence::cases() as $confidence)
                                        <option value="{{ $confidence->value }}">{{ $confidence->label() }}</option>
                                    @endforeach
                                </x-form.select>
                            </div>
                            <x-form.textarea name="overrideReason" wire:model="overrideReason" :label="__('capabilities.review.reason')" :help="__('capabilities.review.reason_help')" rows="2" required />
                            <x-slot:actions>
                                <x-ui.button type="submit" size="sm">{{ __('admin.common.save') }}</x-ui.button>
                                <x-ui.button tone="outline" size="sm" wire:click="cancelOverride">{{ __('admin.common.cancel') }}</x-ui.button>
                            </x-slot:actions>
                        </x-form.inline>
                    @endif

                    {{-- Why the automatic value is what it is: the rule codes with the counts. --}}
                    <ul class="list-disc pl-5 text-sm text-grey-700">
                        @foreach ($assessment->automatic_basis['basis'] ?? [] as $rule)
                            <li>{{ $basisLine($rule) }}</li>
                        @endforeach
                    </ul>

                    {{-- Akadály: a missing precondition, not the team (6.7). --}}
                    <div class="flex flex-wrap items-end gap-2">
                        <div class="min-w-60">
                            <x-form.select :name="'blockers.'.$assessment->id" wire:model="blockers.{{ $assessment->id }}" :label="__('capabilities.review.blocker')">
                                <option value="">{{ __('capabilities.review.no_blocker') }}</option>
                                @foreach ($preconditionOptions as $precondition)
                                    <option value="{{ $precondition->id }}">{{ $precondition->label() }}</option>
                                @endforeach
                            </x-form.select>
                        </div>
                        <x-ui.button tone="outline" size="sm" wire:click="saveBlocker({{ $assessment->id }})">{{ __('admin.common.save') }}</x-ui.button>
                    </div>

                    {{-- The findings with their traces. --}}
                    @if ($rows->where('status', \App\Enums\FindingStatus::Proposed)->where('needs_first_approval', false)->isNotEmpty())
                        <div>
                            <x-ui.button tone="outline" size="sm" icon="check" wire:click="confirmCapability({{ $assessment->capability_id }}, '{{ $level }}')">{{ __('capabilities.review.confirm_all') }}</x-ui.button>
                        </div>
                    @endif

                    <ul class="flex flex-col gap-4">
                        @foreach ($rows as $finding)
                            <li wire:key="finding-{{ $finding->id }}" class="flex flex-col gap-2 border-t border-grey-250 pt-3">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div class="flex flex-col gap-1">
                                        <p class="font-medium">
                                            @if ($finding->pattern)
                                                <a href="{{ route('admin.patterns.show', $finding->pattern) }}" class="text-charcoal underline"><span class="font-mono text-sm">{{ $finding->pattern->code }}</span> · {{ $finding->pattern->name }}</a>
                                            @else
                                                {{ __('capabilities.review.interpretation') }}
                                            @endif
                                        </p>
                                        <div class="flex flex-wrap gap-1">
                                            @if ($finding->pattern?->subtype)
                                                <x-ui.badge tone="muted">{{ $finding->pattern->subtype->label() }}</x-ui.badge>
                                            @endif
                                            <x-ui.badge>{{ $finding->status->label() }}</x-ui.badge>
                                            <x-ui.badge tone="muted">{{ __('capabilities.review.machine_confidence', ['value' => $finding->machine_confidence->label()]) }}</x-ui.badge>
                                            @if ($finding->needs_first_approval && $finding->status === \App\Enums\FindingStatus::Proposed)
                                                <x-ui.badge tone="yellow">{{ __('capabilities.review.first_approval') }}</x-ui.badge>
                                            @endif
                                        </div>
                                        @if ($finding->reasoning)
                                            <p class="text-sm text-grey-700">{{ $finding->reasoning }}</p>
                                        @endif
                                    </div>
                                    @if ($finding->status === \App\Enums\FindingStatus::Proposed)
                                        <div class="flex flex-wrap gap-2">
                                            <x-ui.button size="sm" icon="check" wire:click="confirmFinding({{ $finding->id }})">{{ __('capabilities.review.confirm') }}</x-ui.button>
                                            <x-ui.button tone="danger" size="sm" wire:click="rejectFinding({{ $finding->id }})">{{ __('capabilities.review.reject') }}</x-ui.button>
                                        </div>
                                    @endif
                                </div>

                                <ul class="flex flex-col gap-2">
                                    @foreach ($finding->traces as $trace)
                                        <li wire:key="finding-{{ $finding->id }}-trace-{{ $trace->id }}" @class(['flex flex-col gap-1 rounded-field bg-grey-100 px-3 py-2', 'text-grey-600' => ! $trace->isUsable()])>
                                            <blockquote>„{{ $trace->quote }}”</blockquote>
                                            <p class="flex flex-wrap items-center gap-1 text-sm text-grey-700">
                                                <span>{{ collect([$trace->speaker, $trace->location, $trace->direction->label(), $trace->directness->label(), $trace->is_raw ? __('capabilities.review.raw') : __('capabilities.review.processed')])->filter()->implode(' · ') }}</span>
                                                @foreach ($trace->flags as $flag)
                                                    <x-ui.badge tone="muted">{{ __('capabilities.flags.'.$flag) }}</x-ui.badge>
                                                @endforeach
                                                @if ($trace->isBlocked())
                                                    <x-ui.badge tone="coral">{{ __('capabilities.review.blocked_by_detector') }}</x-ui.badge>
                                                @endif
                                            </p>
                                            @if ($trace->alternative_reading)
                                                <p class="text-sm text-grey-700">{{ __('capabilities.review.alternative', ['text' => $trace->alternative_reading]) }}</p>
                                            @endif
                                            <div>
                                                <x-ui.button tone="outline" size="sm" wire:click="toggleTrace({{ $trace->id }})">{{ $trace->excluded_at ? __('capabilities.review.include') : __('capabilities.review.exclude') }}</x-ui.button>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endforeach
        </section>
    @empty
        <x-ui.notice>{{ __('capabilities.review.empty') }}</x-ui.notice>
    @endforelse

    {{-- E1–E9 (6.7): not scored. --}}
    @if ($preconditionRows->isNotEmpty())
        <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-4" aria-labelledby="capability-preconditions">
            <x-ui.section-header id="capability-preconditions" :title="__('capabilities.review.preconditions')" :description="__('capabilities.review.preconditions_help')" />
            @foreach ($preconditionRows as $row)
                <div wire:key="precondition-{{ $row->id }}" class="flex flex-wrap items-end gap-3">
                    <div class="min-w-64 flex-1">
                        <x-form.select :name="'preconditionValues.'.$row->id" wire:model="preconditionValues.{{ $row->id }}" :label="$row->precondition->label()" :help="__('capabilities.review.precondition_automatic', ['value' => $row->automatic_value->label()])">
                            @foreach (\App\Enums\PreconditionValue::cases() as $value)
                                <option value="{{ $value->value }}">{{ $value->label() }}</option>
                            @endforeach
                        </x-form.select>
                    </div>
                    <div class="min-w-64 flex-1">
                        <x-form.input :name="'preconditionNotes.'.$row->id" wire:model="preconditionNotes.{{ $row->id }}" :label="__('capabilities.review.note')" />
                    </div>
                    <x-ui.button tone="outline" size="sm" wire:click="savePrecondition({{ $row->id }})">{{ $row->approved_at ? __('admin.common.save') : __('capabilities.review.approve') }}</x-ui.button>
                </div>
            @endforeach
        </x-ui.card>
    @endif

    {{-- Candidates: only the mégis adds patterns to the Sheet (NY-26). --}}
    @if ($candidates->isNotEmpty())
        <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-3" aria-labelledby="capability-candidates">
            <x-ui.section-header id="capability-candidates" :title="__('capabilities.review.candidates')" :description="__('capabilities.review.candidates_help')" />
            <ul class="flex flex-col gap-3">
                @foreach ($candidates as $trace)
                    <li wire:key="candidate-{{ $trace->id }}" class="flex flex-col gap-1">
                        <span><x-ui.badge tone="yellow">{{ $trace->candidate->label() }}</x-ui.badge></span>
                        <blockquote>„{{ $trace->quote }}”</blockquote>
                        @if ($trace->candidate_note)
                            <p class="text-sm text-grey-700">{{ $trace->candidate_note }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif
</div>
