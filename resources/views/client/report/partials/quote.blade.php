{{-- A trace in the grey quote box: source, role and date, never a name (18 · 18.2, 27 · 27.1). --}}
<figure class="flex flex-col gap-1 rounded-card bg-grey-100 p-4">
    <blockquote class="text-charcoal">„{{ $trace['quote'] }}”</blockquote>
    <figcaption class="text-xs text-grey-700">
        {{ __('admin.reading.traces.sources.'.$trace['source']) }}
        @if ($trace['role']) · {{ __('admin.reading.traces.roles.'.$trace['role']) }} @endif
        @if ($trace['date']) · {{ \Illuminate\Support\Carbon::parse($trace['date'])->format('Y.m.d.') }} @endif
    </figcaption>
</figure>
