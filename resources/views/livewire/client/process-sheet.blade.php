{{-- V6·e · the data sheet of a process (11 · 11.3). --}}
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
        <p class="text-sm text-grey-600">{{ __('processes.list.renamed_from', ['name' => $process->renames->first()->old_name]) }}</p>
    @endif

    {{-- Felelős --}}
    <section class="flex flex-col gap-3 rounded-card bg-white p-5" aria-labelledby="responsible-title">
        <h2 id="responsible-title" class="font-semibold">{{ __('processes.sheet.responsible') }}</h2>
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
    </section>

    {{-- Résztvevők --}}
    <section class="flex flex-col gap-3 rounded-card bg-white p-5" aria-labelledby="participants-title">
        <h2 id="participants-title" class="font-semibold">{{ __('processes.sheet.participants') }} · {{ $process->participants->count() }}</h2>
        <p class="text-sm text-grey-700">{{ __('processes.sheet.participants_help') }}</p>
        <ul class="flex flex-col gap-1">
            @foreach ($process->participants as $participant)
                <li wire:key="participant-{{ $participant->id }}" class="flex flex-wrap items-center justify-between gap-2">
                    <span>
                        {{ $participant->displayName() }}
                        @if ($participant->isExternal()) <x-ui.badge tone="muted">{{ __('processes.sheet.external') }}</x-ui.badge> @endif
                    </span>
                    @if ($editable)
                        <button type="button" wire:click="removeParticipant({{ $participant->id }})" class="rounded-pill p-1 hover:bg-grey-100">
                            <x-megis.icon name="close" class="size-4" :label="__('processes.sheet.remove_participant', ['name' => $participant->displayName()])" />
                        </button>
                    @endif
                </li>
            @endforeach
        </ul>
        @if ($editable)
            <form wire:submit="addParticipant" class="flex flex-wrap items-end gap-2">
                <div class="min-w-56 flex-1">
                    <x-form.select name="newParticipantId" wire:model="newParticipantId" :label="__('processes.sheet.add_participant')">
                        <option value="">{{ __('processes.sheet.pick_user') }}</option>
                        @foreach ($members as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </x-form.select>
                </div>
                <x-ui.button type="submit" tone="outline" size="sm">{{ __('processes.list.add') }}</x-ui.button>
            </form>
        @endif
    </section>

    {{-- Csapat(ok) --}}
    @if ($teams->isNotEmpty())
        <fieldset class="flex flex-col gap-2 rounded-card bg-white p-5" @disabled(! $editable)>
            <legend class="font-semibold">{{ __('processes.sheet.teams') }}</legend>
            @foreach ($teams as $team)
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" value="{{ $team->id }}" wire:model.live="teamIds" class="size-4 accent-charcoal">
                    {{ $team->name }}
                </label>
            @endforeach
        </fieldset>
    @endif

    {{-- Saját mérőszám --}}
    <section class="flex flex-col gap-3 rounded-card bg-white p-5" aria-labelledby="metrics-title">
        <h2 id="metrics-title" class="font-semibold">{{ __('processes.sheet.metrics') }}</h2>
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
                    <details class="text-sm">
                        <summary class="cursor-pointer font-semibold">{{ __('processes.sheet.earlier') }}</summary>
                        <ul class="mt-1 flex flex-col gap-0.5">
                            @foreach ($metricRow->values->slice(1) as $earlier)
                                <li>{{ $earlier->measured_on->format('Y.m.d.') }} · {{ $earlier->valueLabel() }}</li>
                            @endforeach
                        </ul>
                    </details>
                @endif
                @if ($canRecord)
                    @if ($recordingMetricId === $metricRow->id)
                        <form wire:submit="recordValue" class="flex flex-wrap items-end gap-2">
                            <x-form.input name="metricValue" type="number" step="any" wire:model="metricValue" :label="__('processes.sheet.value')" />
                            <x-form.input name="metricDate" type="date" wire:model="metricDate" :label="__('processes.sheet.measured_on')" />
                            <x-ui.button type="submit" size="sm">{{ __('diagnosis.save') }}</x-ui.button>
                        </form>
                    @else
                        <button type="button" wire:click="openRecord({{ $metricRow->id }})" class="self-start text-sm font-semibold underline">{{ __('processes.sheet.record_value') }}</button>
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
                <x-ui.button type="submit" tone="outline" size="sm" class="self-start">{{ __('processes.sheet.add_metric') }}</x-ui.button>
            </form>
        @endif
    </section>

    <div class="flex flex-wrap items-center gap-4">
        <x-ui.button tone="coral" size="lg" :href="$backUrl">{{ __('processes.sheet.done') }}</x-ui.button>
        <a href="{{ $backUrl }}" class="font-semibold">{{ __('processes.sheet.back') }}</a>
        <x-diagnosis.saved />
    </div>
</div>
