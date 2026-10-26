{{-- A trace in the grey quote box (prototype .qt): source, role and date, never a name (18 · 18.2, 27 · 27.1). --}}
<figure class="flex flex-col gap-1 rounded-[18px] bg-grey-100 px-3.5 py-3">
    <blockquote class="text-sm leading-relaxed text-charcoal">„{{ $trace['quote'] }}”</blockquote>
    <figcaption class="text-xs text-grey-700">
        {{ __('report.trace.sources.'.$trace['source']) }}@if ($trace['role']) · {{ __('report.trace.roles.'.$trace['role']) }}@endif @if ($trace['date']) · {{ \Illuminate\Support\Carbon::parse($trace['date'])->format('m.d.') }}@endif
    </figcaption>
</figure>
