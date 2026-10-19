{{-- V1 · Mi vár rád, V1·b · Innen folytatod (23 · 23.1, 23.2). --}}
<div class="flex max-w-2xl flex-col gap-6">
    <x-ui.flash />

    <div class="flex flex-col gap-1">
        @if ($main->stage->isBeforeRunning() && ($firstVisit || ! $steps->isStarted()))
            <p class="text-xl font-semibold">{{ trans_choice($main->isSnapshot() ? 'diagnosis.steps_lead_snapshot' : 'diagnosis.steps_lead', $steps->count(), ['count' => $steps->count()]) }}</p>
            <p class="text-grey-700">
                {{ $main->product_name }} ·
                {{ __('diagnosis.minutes_until', ['minutes' => $steps->totalMinutes()]) }}
                {{ $main->starts_on ? __('diagnosis.until', ['date' => $main->starts_on->format('m.d.')]) : __('diagnosis.date_open') }}
            </p>
        @elseif ($main->stage->isBeforeRunning())
            <p class="text-xl font-semibold">{{ __('diagnosis.continue_lead') }}</p>
            <p class="text-grey-700">{{ __('diagnosis.continue_count', ['done' => $steps->doneCount(), 'count' => $steps->count(), 'minutes' => $steps->remainingMinutes()]) }}</p>
        @elseif ($main->acceptsInput())
            <p class="text-xl font-semibold">{{ __('diagnosis.running_lead', ['date' => $main->publishes_on->format('m.d.')]) }}</p>
            <p class="text-grey-700">{{ __('diagnosis.running_help', ['date' => $main->readingStartsOn()->format('m.d.')]) }}</p>
        @else
            <p class="text-xl font-semibold">{{ __('diagnosis.reading_lead') }}</p>
            <p class="text-grey-700">{{ __('diagnosis.reading_help') }}</p>
        @endif
    </div>

    <x-diagnosis.step-list :steps="$steps" :step-url="$stepUrl" />

    @if ($main->stage->isBeforeRunning())
        <a href="{{ route('client.wisdom.show', [$main->diagnosis->organization, 'folyamat']) }}" class="self-start text-sm font-semibold">{{ __('wisdom.more') }}</a>
    @endif

    @if ($current && $main->acceptsInput())
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button tone="coral" size="lg" :href="$stepUrl($current->step)">
                {{ $steps->isStarted() && ! $firstVisit ? __('diagnosis.continue') : __('diagnosis.start') }}
            </x-ui.button>
        </div>
    @endif

    @if ($main->stage->isBeforeRunning())
        <x-diagnosis.end-picture :main="$main" />
    @endif

    @foreach ($otherRoles as $peerMain)
        <a href="{{ route('client.diagnosis.show', [$main->diagnosis->organization, $peerMain->diagnosis]) }}" class="text-sm font-semibold">
            {{ __('diagnosis.peer_link', ['subject' => $peerMain->subjectName()]) }}
        </a>
    @endforeach
</div>
