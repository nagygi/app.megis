{{-- V6·f · A folyamataid (11 · 11.4). --}}
<div class="flex max-w-2xl flex-col gap-6">
    {{-- The count and the product's limit are in the header (prototype V6·f). --}}

    @if ($inCalendar->isNotEmpty())
        <section class="flex flex-col gap-2" aria-labelledby="summary-measured">
            <h3 id="summary-measured" class="text-lg font-semibold">{{ __('processes.summary.measured') }}</h3>
            <ul class="flex flex-col gap-2">
                @foreach ($inCalendar as $process)
                    <li>
                        <x-ui.link-row
                            :href="$sheetUrl($process)"
                            :title="$process->name"
                            :meta="collect([
                                $process->rhythm_label ?? $process->rhythm?->label(),
                                $process->headcount ? __('processes.headcount', ['count' => $process->headcount]) : null,
                                $process->responsible?->name,
                                $process->children_count ? __('processes.list.subs', ['count' => $process->children_count]) : null,
                            ])->filter()->implode(' · ')"
                        />
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($notInCalendar->isNotEmpty())
        <section class="flex flex-col gap-2" aria-labelledby="summary-not-in-calendar">
            <h3 id="summary-not-in-calendar" class="text-lg font-semibold text-coral-ink">{{ __('processes.summary.not_in_calendar') }}</h3>
            <ul class="flex flex-col gap-2">
                @foreach ($notInCalendar as $process)
                    <li>
                        <x-ui.link-row
                            :href="$sheetUrl($process)"
                            :title="$process->name"
                            :meta="collect([$process->rhythm?->label(), $process->confidence->label(), $process->responsible?->name ?? __('processes.summary.no_responsible')])->filter()->implode(' · ')"
                        />
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($excluded->isNotEmpty() || $otherCount > 0)
        <section class="flex flex-col gap-2" aria-labelledby="summary-excluded">
            <h3 id="summary-excluded" class="text-lg font-semibold">{{ __('processes.summary.excluded') }}</h3>
            <ul class="flex flex-col gap-2">
                @foreach ($excluded as $process)
                    <li>
                        <x-ui.link-row :href="$sheetUrl($process)" :title="$process->name" :meta="$process->kind->label()" />
                    </li>
                @endforeach
            </ul>
            @if ($otherCount > 0)
                <p class="text-sm text-grey-700">{{ __('processes.summary.other_count', ['count' => $otherCount]) }}</p>
            @endif
        </section>
    @endif

    @if (filled($organization->process_recommendation))
        <x-ui.notice :title="__('processes.summary.recommendation')">{{ $organization->process_recommendation }}</x-ui.notice>
    @endif

    @if ($editable)
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button size="lg" icon-right="arrow-right" wire:click="next">{{ $this->nextLabel(\App\Enums\DiagnosisStep::Processes) }}</x-ui.button>
            <x-ui.button tone="outline" icon="plus" :href="route('client.diagnosis.process-list', [$organization, $diagnosis])">{{ __('processes.summary.add') }}</x-ui.button>
            <x-ui.button tone="ghost" :href="route('client.diagnosis.show', [$organization, $diagnosis])">{{ __('processes.summary.later') }}</x-ui.button>
        </div>
    @endif
</div>
