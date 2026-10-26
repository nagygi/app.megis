{{-- Tárak → Folyamatok (11 · 11.5; prototype M4): one row a routine, divided by a line. --}}
<div class="flex max-w-2xl flex-col gap-6" @if ($dictations !== []) wire:poll.2s="pollDictations" @endif>
    <x-ui.flash />

    @if ($processes->isEmpty())
        <x-ui.empty-state symbol="spiral">{{ __('processes.library.empty') }}</x-ui.empty-state>
    @else
        <ul class="flex flex-col divide-y divide-grey-200">
            @foreach ($processes as $process)
                @php
                    $isMeasured = $process->kind === \App\Enums\ProcessKind::Routine && $process->isAccepted();
                    $score = $scores->get(mb_strtolower(trim($process->name)));
                    $isScored = ($score['score_state'] ?? null) === 'scored';
                @endphp
                <li wire:key="library-process-{{ $process->id }}">
                    {{-- Outside the calendar the line is coral: a state, not an error (M4). --}}
                    <x-ui.list-row
                        :href="route('client.processes.show', [$organization, $process])"
                        :title="$process->name"
                        :meta="collect([
                            $process->rhythm?->label() ?? $process->rhythm_label,
                            $process->responsible?->name,
                            $process->headcount ? __('processes.headcount', ['count' => $process->headcount]) : ($process->participants_count ? __('processes.headcount', ['count' => $process->participants_count]) : null),
                            $process->isInCalendar() ? __('processes.library.in_calendar') : __('processes.library.not_in_calendar'),
                            $isMeasured ? __('processes.library.measured') : __('processes.library.not_measured'),
                        ])->filter()->implode(' · ')"
                        :meta-tone="$process->isInCalendar() ? null : 'coral'"
                        :score="$score ? \App\Services\Report\ReportText::score($score) : null"
                        :score-low="$isScored && $score['score'] <= 2"
                    />
                </li>
            @endforeach
        </ul>
    @endif

    @if ($adding)
        <x-ui.card as="form" wire:submit="add" class="flex flex-col gap-4">
            <x-form.input name="name" wire:model="name" :label="__('processes.list.fields.name')" required />
            <x-form.select name="rhythm" wire:model="rhythm" :label="__('processes.list.fields.rhythm')">
                <option value="">–</option>
                @foreach (\App\Enums\ProcessRhythm::cases() as $rhythmOption)
                    <option value="{{ $rhythmOption->value }}">{{ $rhythmOption->label() }}</option>
                @endforeach
            </x-form.select>
            <x-form.long-text
                name="description"
                wire:model="description"
                :label="__('processes.sheet.description')"
                rows="3"
                :max="\App\Livewire\Client\ProcessSheet::DESCRIPTION_MAX"
                :pending="isset($dictations['description'])"
                :tools="$canDictate"
            />
            <div class="flex flex-wrap gap-2">
                <x-ui.button type="submit" icon="plus" data-focus-errors>{{ __('processes.list.add') }}</x-ui.button>
                <x-ui.button tone="outline" wire:click="$set('adding', false)">{{ __('diagnosis.cancel') }}</x-ui.button>
            </div>
        </x-ui.card>
    @else
        <div>
            <x-ui.button tone="outline" size="sm" icon="plus" wire:click="$set('adding', true)">{{ __('processes.library.add') }}</x-ui.button>
        </div>
    @endif
</div>
