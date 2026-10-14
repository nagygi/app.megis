{{-- How the aggregated data was (or will be) calculated, saved with the diagnosis (04 · 4.5). --}}
<dl class="grid gap-3 rounded-xl bg-paper p-4 text-sm md:grid-cols-[12rem_1fr]">
    <dt class="font-semibold">{{ __('admin.diagnoses.aggregation.source') }}</dt>
    <dd>
        {{ __('admin.diagnoses.aggregation.source_text', [
            'subject' => $description['subject'],
            'from' => \Illuminate\Support\Carbon::parse($description['window']['from'])->format('Y.m.d.'),
            'to' => \Illuminate\Support\Carbon::parse($description['window']['to'])->format('Y.m.d.'),
            'usable' => $description['usable_count'],
        ]) }}
        @if ($description['low_coverage'])
            <span class="mt-1 block font-semibold text-charcoal" role="note">
                {{ __('admin.diagnoses.aggregation.low_coverage', ['usable' => $description['usable_count'], 'active' => $description['active_count']]) }}
            </span>
        @endif
    </dd>

    @if ($description['sources'] !== [])
        <dt class="font-semibold">{{ __('admin.diagnoses.aggregation.sources') }}</dt>
        <dd>
            <ul class="flex flex-col gap-0.5">
                @foreach ($description['sources'] as $source)
                    <li>
                        <a href="{{ route('admin.diagnoses.show', $source['diagnosis_id']) }}" class="text-charcoal">{{ $source['diagnosis_name'] }}</a>
                        · {{ $source['user_name'] }} · {{ $source['date'] }}
                        @isset($source['metric'])
                            · {{ __('admin.diagnoses.aggregation.metric.'.$source['metric']) }}
                        @endisset
                    </li>
                @endforeach
            </ul>
        </dd>
    @endif

    @if ($description['excluded'] !== [])
        <dt class="font-semibold">{{ __('admin.diagnoses.aggregation.excluded_title') }}</dt>
        <dd>
            <ul class="flex flex-col gap-0.5">
                @foreach ($description['excluded'] as $excluded)
                    <li>{{ $excluded['user_name'] }} · {{ $excluded['reason_label'] }}</li>
                @endforeach
            </ul>
        </dd>
    @endif

    <dt class="font-semibold">{{ __('admin.diagnoses.aggregation.calculation') }}</dt>
    <dd>{{ $description['calculation'] }}</dd>

    <dt class="font-semibold">{{ __('admin.diagnoses.aggregation.thresholds') }}</dt>
    <dd>{{ $description['thresholds'] }}</dd>

    @isset($description['loaded_at'])
        <dt class="font-semibold">{{ __('admin.diagnoses.aggregation.loaded_by') }}</dt>
        <dd>{{ __('admin.diagnoses.aggregation.loaded_by_text', [
            'name' => $description['loaded_by'] ?? __('admin.versions.system'),
            'date' => \Illuminate\Support\Carbon::parse($description['loaded_at'])->timezone(config('app.timezone'))->format('Y.m.d. H:i'),
        ]) }}</dd>
    @endisset
</dl>
