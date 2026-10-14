{{-- How the point of a process diagnosis was calculated, saved with it (13 · 13.5). --}}
<dl class="grid gap-3 rounded-xl bg-paper p-4 text-sm md:grid-cols-[12rem_1fr]">
    <dt class="font-semibold">{{ __('admin.diagnoses.aggregation.source') }}</dt>
    <dd>
        {{ __('admin.diagnoses.process_aggregation.source', ['process' => $diagnosis->process?->name ?? '–', 'period' => $diagnosis->dateLabel(), 'count' => $description['occasions'] ?? 0]) }}
        @if (($description['used'] ?? []) !== [])
            <span class="block text-grey-700">{{ collect($description['used'])->map(fn ($used) => \Illuminate\Support\Carbon::parse($used['date'])->format('Y.m.d.').' ('.trans_choice('admin.diagnoses.process_aggregation.materials', $used['materials'], ['count' => $used['materials']]).')')->implode(' · ') }}</span>
        @endif
    </dd>

    <dt class="font-semibold">{{ __('admin.diagnoses.process_aggregation.skipped') }}</dt>
    <dd>
        @forelse ($description['skipped'] ?? [] as $skipped)
            <span class="block">{{ \Illuminate\Support\Carbon::parse($skipped['date'])->format('Y.m.d.') }} · {{ __('admin.diagnoses.process_aggregation.reasons.'.$skipped['reason']) }}</span>
        @empty
            –
        @endforelse
    </dd>

    <dt class="font-semibold">{{ __('admin.diagnoses.process_aggregation.calculation') }}</dt>
    <dd>{{ __('admin.diagnoses.process_aggregation.rule', ['minimum' => $description['minimum'] ?? 2]) }}</dd>

    <dt class="font-semibold">{{ __('admin.diagnoses.process_aggregation.overrides') }}</dt>
    <dd>
        @php($overridden = $valueRows->whereNotNull('overridden_at'))
        @if ($diagnosis->score_overridden_at === null && $overridden->isEmpty())
            –
        @else
            @if ($diagnosis->score_overridden_at)
                <span class="block">{{ __('admin.diagnoses.process_point') }} · {{ __('admin.diagnoses.override.by', ['name' => $diagnosis->scoreOverrider?->name ?? '–', 'date' => $diagnosis->score_overridden_at->format('Y.m.d. H:i')]) }}</span>
            @endif
            @foreach ($overridden as $row)
                <span class="block">{{ $row->label() }} · {{ __('admin.diagnoses.override.by', ['name' => $row->overrider?->name ?? '–', 'date' => $row->overridden_at->format('Y.m.d. H:i')]) }}</span>
            @endforeach
        @endif
    </dd>

    @isset($description['calculated_at'])
        <dt class="font-semibold">{{ __('admin.diagnoses.process_aggregation.calculated_at') }}</dt>
        <dd>{{ \Illuminate\Support\Carbon::parse($description['calculated_at'])->timezone(config('app.timezone'))->format('Y.m.d. H:i') }}</dd>
    @endisset
</dl>
