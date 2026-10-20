{{--
    V1 · Mi vár rád, V1·b · Innen folytatod (23 · 23.1, 23.2). As in the
    prototype, the lead lines are in the header (Overview::leaderWelcomeHeader);
    under the steps "Kezdjük" and "Bővebben" side by side, then the end.
--}}
<div class="flex max-w-2xl flex-col gap-6">
    <x-ui.flash />

    @unless ($main->stage->isBeforeRunning())
        <div class="flex flex-col gap-1">
            @if ($main->acceptsInput())
                <h2 class="text-xl font-semibold">{{ __('diagnosis.running_lead', ['date' => $main->publishes_on->format('m.d.')]) }}</h2>
                <p class="text-grey-700">{{ __('diagnosis.running_help', ['date' => $main->readingStartsOn()->format('m.d.')]) }}</p>
            @else
                <h2 class="text-xl font-semibold">{{ __('diagnosis.reading_lead') }}</h2>
                <p class="text-grey-700">{{ __('diagnosis.reading_help') }}</p>
            @endif
        </div>
    @endunless

    <x-diagnosis.step-list :steps="$steps" :step-url="$stepUrl" />

    @if (($current && $main->acceptsInput()) || $main->stage->isBeforeRunning())
        <div class="flex flex-wrap items-center gap-3">
            @if ($current && $main->acceptsInput())
                <x-ui.button size="lg" icon-right="arrow-right" :href="$stepUrl($current->step)">
                    {{ $steps->hasOwnProgress() && ! $firstVisit ? __('diagnosis.continue') : __('diagnosis.start') }}
                </x-ui.button>
            @endif

            @if ($main->stage->isBeforeRunning())
                <x-ui.button tone="ghost" :href="route('client.wisdom.show', [$main->diagnosis->organization, 'folyamat'])">{{ __('wisdom.more') }}</x-ui.button>
            @endif
        </div>
    @endif

    @if ($main->stage->isBeforeRunning())
        <x-diagnosis.end-picture :main="$main" />
    @endif

    @foreach ($otherRoles as $peerMain)
        <x-ui.link-row :href="route('client.diagnosis.show', [$main->diagnosis->organization, $peerMain->diagnosis])" :title="__('diagnosis.peer_link', ['subject' => $peerMain->subjectName()])" />
    @endforeach
</div>
