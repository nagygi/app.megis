{{-- V6·f · A folyamataid (11 · 11.4). --}}
<div class="flex max-w-2xl flex-col gap-6">
    <div class="flex flex-col gap-1">
        <p class="text-xl font-semibold">{{ trans_choice('processes.summary.lead', $measuredCount, ['count' => $measuredCount]) }}</p>
        @if ($limit !== null)
            <p class="text-sm text-grey-700">{{ __('processes.summary.limit', ['max' => $limit]) }}</p>
        @endif
    </div>

    @if ($inCalendar->isNotEmpty())
        <section class="flex flex-col gap-2" aria-labelledby="summary-measured">
            <h2 id="summary-measured" class="font-semibold">{{ __('processes.summary.measured') }}</h2>
            <ul class="flex flex-col gap-2">
                @foreach ($inCalendar as $process)
                    <li>
                        <a href="{{ $sheetUrl($process) }}" class="flex items-center justify-between gap-3 rounded-card bg-white px-4 py-3 text-charcoal no-underline hover:bg-paper">
                            <span>
                                <span class="block font-semibold">{{ $process->name }}</span>
                                <span class="block text-sm text-grey-700">
                                    {{ collect([
                                        $process->rhythm_label ?? $process->rhythm?->label(),
                                        $process->headcount ? __('processes.headcount', ['count' => $process->headcount]) : null,
                                        $process->responsible?->name,
                                        $process->children_count ? __('processes.list.subs', ['count' => $process->children_count]) : null,
                                    ])->filter()->implode(' · ') }}
                                </span>
                            </span>
                            <x-megis.icon name="chevron-right" class="size-4 shrink-0" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($notInCalendar->isNotEmpty())
        <section class="flex flex-col gap-2" aria-labelledby="summary-not-in-calendar">
            <h2 id="summary-not-in-calendar" class="font-semibold text-coral-ink">{{ __('processes.summary.not_in_calendar') }}</h2>
            <ul class="flex flex-col gap-2">
                @foreach ($notInCalendar as $process)
                    <li>
                        <a href="{{ $sheetUrl($process) }}" class="flex items-center justify-between gap-3 rounded-card bg-white px-4 py-3 text-charcoal no-underline hover:bg-paper">
                            <span>
                                <span class="block font-semibold">{{ $process->name }}</span>
                                <span class="block text-sm text-grey-700">
                                    {{ collect([$process->rhythm?->label(), $process->confidence->label(), $process->responsible?->name ?? __('processes.summary.no_responsible')])->filter()->implode(' · ') }}
                                </span>
                            </span>
                            <x-megis.icon name="chevron-right" class="size-4 shrink-0" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($excluded->isNotEmpty() || $otherCount > 0)
        <section class="flex flex-col gap-2" aria-labelledby="summary-excluded">
            <h2 id="summary-excluded" class="font-semibold">{{ __('processes.summary.excluded') }}</h2>
            <ul class="flex flex-col gap-2">
                @foreach ($excluded as $process)
                    <li>
                        <a href="{{ $sheetUrl($process) }}" class="flex items-center justify-between gap-3 rounded-card bg-white px-4 py-3 text-charcoal no-underline hover:bg-paper">
                            <span><span class="font-semibold">{{ $process->name }}</span> · <span class="text-sm">{{ $process->kind->label() }}</span></span>
                            <x-megis.icon name="chevron-right" class="size-4 shrink-0" />
                        </a>
                    </li>
                @endforeach
            </ul>
            @if ($otherCount > 0)
                <p class="text-sm text-grey-700">{{ __('processes.summary.other_count', ['count' => $otherCount]) }}</p>
            @endif
        </section>
    @endif

    @if (filled($organization->process_recommendation))
        <aside class="flex flex-col gap-1 rounded-card bg-yellow-pale p-5" aria-labelledby="recommendation">
            <h2 id="recommendation" class="text-sm font-semibold uppercase">{{ __('processes.summary.recommendation') }}</h2>
            <p>{{ $organization->process_recommendation }}</p>
        </aside>
    @endif

    @if ($editable)
        <div class="flex flex-wrap items-center gap-4">
            <x-ui.button tone="coral" size="lg" wire:click="next">{{ __('diagnosis.next') }}</x-ui.button>
            <a href="{{ route('client.diagnosis.process-list', [$organization, $diagnosis]) }}" class="font-semibold">{{ __('processes.summary.back_to_list') }}</a>
        </div>
    @endif
</div>
