{{-- V6·e · the data sheet of a process (11 · 11.3); every change saves at once. --}}
<div class="flex max-w-2xl flex-col gap-6">
    <x-ui.flash />

    <p class="text-grey-700">
        {{ collect([
            $process->rhythm_label ?? $process->rhythm?->label(),
            $process->duration_minutes ? __('processes.sheet.duration', ['minutes' => $process->duration_minutes]) : null,
            __('processes.sheet.occasions', ['count' => $occasionCount]),
        ])->filter()->implode(' · ') }}
        @if ($process->parent) · {{ __('processes.list.sub') }}: {{ $process->parent->name }} @endif
    </p>

    @if ($process->renames->isNotEmpty())
        <p class="text-sm text-grey-700">{{ __('processes.list.renamed_from', ['name' => $process->renames->first()->old_name]) }}</p>
    @endif

    {{-- Felelős --}}
    <x-ui.card as="section" class="flex flex-col gap-3" aria-labelledby="responsible-title">
        <h2 id="responsible-title" class="text-lg font-semibold">{{ __('processes.sheet.responsible') }}</h2>
        @if ($editable)
            <x-form.select name="responsibleId" wire:model.live="responsibleId" :label="__('processes.sheet.responsible')">
                <option value="">{{ __('processes.sheet.responsible_none') }}</option>
                @foreach ($members as $member)
                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                @endforeach
            </x-form.select>
        @else
            <p>{{ $process->responsible?->name ?? __('processes.sheet.responsible_none') }}</p>
        @endif
    </x-ui.card>

    {{-- Résztvevők --}}
    <x-ui.card as="section" class="flex flex-col gap-3" aria-labelledby="participants-title">
        <h2 id="participants-title" class="text-lg font-semibold">{{ __('processes.sheet.participants') }} · {{ $process->participants->count() }}</h2>
        <p class="text-sm text-grey-700">{{ __('processes.sheet.participants_help') }}</p>
        <ul class="flex flex-col">
            @foreach ($process->participants as $participant)
                <li wire:key="participant-{{ $participant->id }}" class="flex flex-wrap items-center justify-between gap-2 border-b border-grey-200 py-1 last:border-b-0">
                    <span>
                        {{ $participant->displayName() }}
                        @if ($participant->isExternal()) <x-ui.badge tone="muted">{{ __('processes.sheet.external') }}</x-ui.badge> @endif
                    </span>
                    @if ($editable)
                        <x-ui.icon-button icon="close" class="-mr-2" :label="__('processes.sheet.remove_participant', ['name' => $participant->displayName()])" wire:click="removeParticipant({{ $participant->id }})" />
                    @endif
                </li>
            @endforeach
        </ul>
        @if ($editable)
            <x-form.inline as="form" wire:submit="addParticipant">
                <x-form.select name="newParticipantId" wire:model="newParticipantId" :label="__('processes.sheet.add_participant')">
                    <option value="">{{ __('processes.sheet.pick_user') }}</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                    @endforeach
                </x-form.select>
                <x-slot:actions>
                    <x-ui.button type="submit" tone="outline" icon="plus">{{ __('processes.list.add') }}</x-ui.button>
                </x-slot:actions>
            </x-form.inline>
        @endif
    </x-ui.card>

    {{-- Csapat(ok) --}}
    @if ($teams->isNotEmpty())
        <x-ui.card as="fieldset" class="flex flex-col gap-2" :disabled="! $editable">
            <legend class="float-left mb-2 text-lg font-semibold">{{ __('processes.sheet.teams') }}</legend>
            <div class="clear-left flex flex-col gap-2">
                @foreach ($teams as $team)
                    <x-form.checkbox name="teamIds" :id="'team-'.$team->id" :value="$team->id" wire:model.live="teamIds" :label="$team->name" />
                @endforeach
            </div>
        </x-ui.card>
    @endif

    {{-- Saját mérőszám --}}
    <x-ui.card as="section" class="flex flex-col gap-3" aria-labelledby="metrics-title">
        <h2 id="metrics-title" class="text-lg font-semibold">{{ __('processes.sheet.metrics') }}</h2>
        <p class="text-sm text-grey-700">{{ __('processes.sheet.metrics_help') }}</p>

        @foreach ($process->metrics as $metricRow)
            @php($last = $metricRow->values->first())
            <div wire:key="metric-{{ $metricRow->id }}" class="flex flex-col gap-2 border-t border-grey-200 pt-3">
                <p class="font-semibold">
                    {{ $metricRow->name }}@if ($metricRow->unit) ({{ $metricRow->unit }})@endif
                    <span class="text-sm font-normal text-grey-700">· {{ $metricRow->rhythm->label() }}@if ($metricRow->target) · {{ __('processes.sheet.metric_fields.target') }}: {{ $metricRow->target }}@endif</span>
                </p>
                @if ($metricRow->description)
                    <p class="text-sm">{{ $metricRow->description }}</p>
                @endif
                <p class="text-sm">
                    {{ $last ? __('processes.sheet.last_value', ['value' => $last->valueLabel().($metricRow->unit ? ' '.$metricRow->unit : ''), 'date' => $last->measured_on->format('Y.m.d.')]) : __('processes.sheet.no_values') }}
                </p>
                @if ($metricRow->values->count() > 1)
                    <details class="group text-sm">
                        <summary class="inline-flex min-h-10 cursor-pointer list-none items-center gap-1 font-semibold [&::-webkit-details-marker]:hidden">
                            {{ __('processes.sheet.earlier') }}
                            <x-megis.icon name="chevron-down" class="transition-transform duration-200 group-open:rotate-180" />
                        </summary>
                        <ul class="flex flex-col gap-0.5">
                            @foreach ($metricRow->values->slice(1) as $earlier)
                                <li class="tabular-nums">{{ $earlier->measured_on->format('Y.m.d.') }} · {{ $earlier->valueLabel() }}</li>
                            @endforeach
                        </ul>
                    </details>
                @endif
                @if ($canRecord)
                    @if ($recordingMetricId === $metricRow->id)
                        <x-form.inline as="form" wire:submit="recordValue">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <x-form.input name="metricValue" type="number" step="any" wire:model="metricValue" :label="__('processes.sheet.value')" />
                                <x-form.input name="metricDate" type="date" wire:model="metricDate" :label="__('processes.sheet.measured_on')" />
                            </div>
                            <x-slot:actions>
                                <x-ui.button type="submit">{{ __('diagnosis.save') }}</x-ui.button>
                            </x-slot:actions>
                        </x-form.inline>
                    @else
                        <x-ui.button tone="ghost" size="sm" icon="plus" class="-ml-1.5 self-start" wire:click="openRecord({{ $metricRow->id }})">{{ __('processes.sheet.record_value') }}</x-ui.button>
                    @endif
                @endif
            </div>
        @endforeach

        @if ($editable)
            <form wire:submit="addMetric" class="flex flex-col gap-3 border-t border-grey-200 pt-3">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-form.input name="metric.name" wire:model="metric.name" :label="__('processes.sheet.metric_fields.name')" required />
                    <x-form.input name="metric.unit" wire:model="metric.unit" :label="__('processes.sheet.metric_fields.unit')" />
                    <x-form.select name="metric.rhythm" wire:model="metric.rhythm" :label="__('processes.sheet.metric_fields.rhythm')" required>
                        @foreach (\App\Enums\MetricRhythm::cases() as $rhythmOption)
                            <option value="{{ $rhythmOption->value }}">{{ $rhythmOption->label() }}</option>
                        @endforeach
                    </x-form.select>
                    <x-form.input name="metric.target" wire:model="metric.target" :label="__('processes.sheet.metric_fields.target')" />
                </div>
                <x-form.textarea name="metric.description" wire:model="metric.description" :label="__('processes.sheet.metric_fields.description')" rows="2" />
                <x-ui.button type="submit" tone="outline" icon="plus" class="self-start" data-focus-errors>{{ __('processes.sheet.add_metric') }}</x-ui.button>
            </form>
        @endif
    </x-ui.card>

    {{-- Everything is saved on the way: one way back. --}}
    <div class="flex flex-wrap items-center gap-4">
        <x-ui.button size="lg" icon="chevron-left" :href="$backUrl">{{ __('processes.sheet.back') }}</x-ui.button>
        <x-diagnosis.saved />
    </div>
</div>
