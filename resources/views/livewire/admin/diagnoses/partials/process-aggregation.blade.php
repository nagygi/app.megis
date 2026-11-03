{{-- How the point of a process diagnosis was calculated, saved with it (04 · 4.5, 13 · 13.5). --}}
<dl class="grid gap-3 rounded-field bg-paper p-4 text-sm md:grid-cols-[12rem_1fr]">
    <dt class="font-semibold">{{ __('admin.diagnoses.aggregation.source') }}</dt>
    <dd>
        {{ __('admin.diagnoses.process_aggregation.source', [
            'process' => $description['process'] ?? $diagnosis->process?->name ?? '–',
            'period' => array_key_exists('period', $description) ? ($description['period'] ?? __('admin.diagnoses.process_no_occasions')) : $diagnosis->dateLabel(),
            'count' => $description['occasions'] ?? 0,
        ]) }}
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
    {{-- The method as it was at the calculation, also if the algorithm changes later (04 · 4.5). --}}
    <dd>{{ $description['method'] ?? __('admin.diagnoses.process_aggregation.rule', ['minimum' => $description['minimum'] ?? 2]) }}</dd>

    <dt class="font-semibold">{{ __('admin.diagnoses.process_aggregation.overrides') }}</dt>
    <dd>
        @forelse ($description['overrides'] ?? [] as $entry)
            @php($date = \Illuminate\Support\Carbon::parse($entry['at'])->timezone(config('app.timezone'))->format('Y.m.d. H:i'))
            <span class="block">{{ $entry['action'] === 'reset'
                ? __('admin.diagnoses.process_aggregation.override_reset', ['target' => $entry['target'], 'name' => $entry['by'] ?? '–', 'date' => $date, 'automatic' => $entry['automatic']])
                : __('admin.diagnoses.process_aggregation.override_entry', ['target' => $entry['target'], 'name' => $entry['by'] ?? '–', 'date' => $date, 'automatic' => $entry['automatic'], 'value' => $entry['value'], 'reason' => $entry['reason']]) }}</span>
        @empty
            –
        @endforelse
    </dd>

    @isset($description['calculated_at'])
        <dt class="font-semibold">{{ __('admin.diagnoses.process_aggregation.calculated_at') }}</dt>
        <dd>{{ \Illuminate\Support\Carbon::parse($description['calculated_at'])->timezone(config('app.timezone'))->format('Y.m.d. H:i') }}</dd>
    @endisset
</dl>
