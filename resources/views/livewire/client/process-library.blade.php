{{-- Tárak → Folyamatok (11 · 11.5; prototype M4): one row a routine, divided by a line. No adding by hand here. --}}
<div class="flex max-w-2xl flex-col gap-6">
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
</div>
