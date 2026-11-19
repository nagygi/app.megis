{{--
    "Most mi jön?" under the stage line (StageGuide::next): whose turn it is,
    what happens now, the progress, what holds it up, and the link to the tab
    and place of the next action. The admin's own turn gets the coral frame.
--}}
@props(['guide'])

@php
    $turnTones = ['admin' => 'coral', 'leader' => 'yellow', 'peers' => 'yellow', 'done' => 'green', 'closed' => 'muted'];
    $issueTones = ['warning' => 'bg-yellow-light text-charcoal', 'error' => 'bg-coral-light text-black'];
    $isMine = $guide['turn'] === 'admin';
@endphp

<x-ui.card
    as="section"
    :bordered="! $isMine"
    aria-labelledby="next-step-title"
    {{ $attributes->class(['flex flex-col gap-3', 'border-2 border-coral' => $isMine]) }}
>
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-sm font-medium text-grey-700">{{ __('admin.main_diagnoses.guide.now') }}</span>
        <x-ui.badge :tone="$turnTones[$guide['turn']]" dot>{{ __('admin.main_diagnoses.guide.turn.'.$guide['turn']) }}</x-ui.badge>
    </div>

    <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-3">
        <div class="flex min-w-0 flex-1 basis-80 flex-col gap-1">
            <h2 id="next-step-title" class="text-lg font-semibold text-charcoal">{{ $guide['title'] }}</h2>
            <p class="text-sm text-grey-700">{{ $guide['body'] }}</p>
        </div>
        @if ($guide['cta'])
            <x-ui.button :href="$guide['cta']['href']" :tone="$isMine ? 'dark' : 'outline'" icon-right="chevron-right">{{ $guide['cta']['label'] }}</x-ui.button>
        @endif
    </div>

    @if ($guide['progress'])
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <div class="h-2 min-w-32 flex-1 rounded-pill bg-grey-200" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $guide['progress']['total'] }}" aria-valuenow="{{ $guide['progress']['done'] }}" aria-label="{{ $guide['progress']['label'] }}">
                <div class="h-2 rounded-pill bg-green" style="width: {{ $guide['progress']['total'] > 0 ? round($guide['progress']['done'] / $guide['progress']['total'] * 100) : 0 }}%"></div>
            </div>
            <span class="text-sm font-medium text-charcoal">{{ $guide['progress']['label'] }}</span>
        </div>
    @endif

    @if ($guide['issues'] !== [])
        <ul class="flex flex-col gap-2">
            @foreach ($guide['issues'] as $issue)
                <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 rounded-field px-3 py-2 text-sm {{ $issueTones[$issue['tone']] }}">
                    <span>{{ $issue['text'] }}</span>
                    @if ($issue['href'])
                        <a href="{{ $issue['href'] }}" class="inline-flex min-h-8 items-center gap-1 font-semibold">{{ __('admin.main_diagnoses.guide.open') }} <x-megis.icon name="chevron-right" /></a>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if ($guide['note'])
        <p class="text-sm text-grey-700">{{ $guide['note'] }}</p>
    @endif
</x-ui.card>
