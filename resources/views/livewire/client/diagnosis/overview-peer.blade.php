{{-- T2 · Mi vár rád, T5 · the peer's home while it runs (25 · 25.3, 25.5). --}}
<div class="flex max-w-2xl flex-col gap-6">
    <x-ui.flash />

    @if ($withdrawn)
        <x-ui.notice>{{ __('diagnosis.peer_home.withdrawn') }}</x-ui.notice>
    @elseif ($current && $main->acceptsInput())
        {{-- T2: the lead lines are in the header (Overview::peerWelcomeHeader), as on V1. --}}
        <x-diagnosis.step-list :steps="$steps" :step-url="$stepUrl" />

        {{-- What comes later: the "+" of the running diagnosis. --}}
        <x-ui.card tone="paper" padding="sm" class="flex items-center gap-3 text-grey-700">
            <span class="grid size-7 shrink-0 place-items-center rounded-pill bg-grey-300 text-charcoal" aria-hidden="true">
                <x-megis.icon name="plus" />
            </span>
            <span class="flex-1">
                <span class="block font-semibold">{{ __('diagnosis.peer_add') }}</span>
                <span class="block text-sm">{{ __('diagnosis.peer_add_help') }}</span>
            </span>
        </x-ui.card>

        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button size="lg" icon-right="arrow-right" :href="$stepUrl($current->step)">
                {{ $steps->isStarted() ? __('diagnosis.continue') : __('diagnosis.start') }}
            </x-ui.button>
            <x-ui.button tone="ghost" :href="route('client.wisdom.show', [$main->diagnosis->organization, 'folyamat'])">{{ __('wisdom.more') }}</x-ui.button>
        </div>

        <x-diagnosis.end-picture :main="$main" peer />
    @else
        {{-- T5 --}}
        <section class="flex flex-col gap-3" aria-labelledby="brought-in">
            <h2 id="brought-in" class="text-lg font-semibold">{{ __('diagnosis.peer_home.brought_in') }}</h2>
            <x-ui.link-row :href="$stepUrl(\App\Enums\DiagnosisStep::OwnValues)" :title="__('enums.diagnosis_step.own_values')" :meta="$ownValues->implode(' · ')" />
            <x-ui.link-row
                :href="$stepUrl(\App\Enums\DiagnosisStep::Experience)"
                :title="__('enums.diagnosis_step.experience')"
                :meta="$experience ? __('diagnosis.peer_home.experience_at', ['date' => $experience->diagnosed_on->format('m.d.')]) : null"
            />
        </section>

        <x-ui.link-row
            :href="route('client.diagnosis.situations', [$main->diagnosis->organization, $main->diagnosis])"
            :title="__('diagnosis.peer_home.situations')"
            :meta="__('diagnosis.peer_home.situations_count', ['count' => $situationCount])"
        />

        @if ($takeawayReady)
            <x-ui.link-row tone="green" :href="route('client.diagnosis.takeaway', [$main->diagnosis->organization, $main->diagnosis])" :title="__('takeaway.home_row')" :meta="__('takeaway.home_row_help')" />
        @else
            {{-- Not there yet: the day it comes instead of the chevron. --}}
            <div class="flex items-center justify-between gap-3 rounded-card border border-dashed border-grey-350 px-5 py-4 text-grey-700">
                <span>
                    <span class="block font-semibold">{{ __('diagnosis.end.takeaway') }}</span>
                    <span class="block text-sm">{{ __('diagnosis.end.takeaway_help') }}</span>
                </span>
                <span class="text-sm tabular-nums">{{ $main->publishes_on->format('m.d.') }}</span>
            </div>
        @endif

        <x-ui.link-row :href="route('client.values', $main->diagnosis->organization)" :title="__('client.menu.value_library')" :meta="__('diagnosis.peer_home.library_help')" />
    @endif

    @foreach ($otherRoles as $leaderMain)
        <x-ui.link-row :href="route('client.diagnosis.show', [$main->diagnosis->organization, $leaderMain->diagnosis])" :title="__('diagnosis.peer_home.leader_link', ['subject' => $leaderMain->subjectName()])" />
    @endforeach
</div>
