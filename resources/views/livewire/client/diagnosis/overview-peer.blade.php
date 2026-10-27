{{-- T2 · Mi vár rád, T5 · the peer's home while it runs (25 · 25.3, 25.5). --}}
<div class="flex max-w-2xl flex-col gap-6">
    <x-ui.flash />

    @if ($withdrawn)
        <x-ui.notice>{{ __('diagnosis.peer_home.withdrawn') }}</x-ui.notice>
    @elseif ($current && $main->acceptsInput())
        {{-- T2: the lead lines are in the header (Overview::peerWelcomeHeader), as on V1. --}}
        <x-diagnosis.step-list :steps="$steps" :step-url="$stepUrl" />

        {{-- What comes later, the third step of the line (prototype T2): the "+" of the running diagnosis. --}}
        <div class="-mt-4 flex items-center gap-3 rounded-card bg-grey-100 px-5 py-4 text-charcoal">
            <span class="grid size-7 shrink-0 place-items-center rounded-pill bg-grey-300 text-grey-700" aria-hidden="true">
                <x-megis.icon name="plus" class="size-4" />
            </span>
            <span class="flex-1">
                <span class="block font-semibold">{{ __('diagnosis.peer_add') }}</span>
                <span class="block text-sm text-grey-700">{{ __('diagnosis.peer_add_help') }}</span>
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button size="lg" icon-right="arrow-right" :href="$stepUrl($current->step)">
                {{ $steps->isStarted() ? __('diagnosis.continue') : __('diagnosis.start') }}
            </x-ui.button>
            <x-ui.button tone="ghost" :href="route('client.wisdom.show', [$main->diagnosis->organization, 'folyamat'])">{{ __('wisdom.more') }}</x-ui.button>
        </div>

        <x-diagnosis.end-picture :main="$main" peer />
    @else
        {{-- T5: the week bar, what the peer brought in, the Útravaló (prototype T5) --}}
        <ol class="flex gap-1" aria-label="{{ __('diagnosis.home.'.$window['unit'].'_of', ['current' => $window['current'], 'count' => $window['count']]) }}">
            @foreach ($window['segments'] as $segment)
                <li @class([
                    'h-2 flex-1 rounded-pill',
                    'bg-charcoal' => $segment === 'done',
                    'bg-coral' => $segment === 'now',
                    'bg-grey-200' => $segment === 'later',
                ])></li>
            @endforeach
        </ol>

        <section class="flex flex-col gap-3" aria-labelledby="brought-in">
            <h2 id="brought-in" class="text-lg font-semibold">{{ __('diagnosis.peer_home.brought_in') }}</h2>
            <x-ui.link-row :href="$stepUrl(\App\Enums\DiagnosisStep::OwnValues)" :title="__('enums.diagnosis_step.own_values')" :meta="$ownValues->implode(' · ')" />
            <x-ui.link-row
                :href="$stepUrl(\App\Enums\DiagnosisStep::Experience)"
                :title="__('enums.diagnosis_step.experience')"
                :meta="$experience ? __('diagnosis.peer_home.experience_at', ['date' => $experience->diagnosed_on->format('m.d.'), 'count' => \App\Services\Report\HungarianNumber::word($valueCount)]) : null"
            />
            <x-ui.link-row
                :href="route('client.diagnosis.situations', [$main->diagnosis->organization, $main->diagnosis])"
                :title="__('diagnosis.peer_home.situations')"
                :meta="__('diagnosis.peer_home.situations_count', $situations).($situations['total'] > 0 ? ' · '.($situations['missing'] > 0 ? __('diagnosis.peer_home.situations_missing', ['count' => $situations['missing']]) : __('diagnosis.peer_home.situations_all_lived')) : '')"
            >
                <x-slot:trailing><x-ui.badge tone="neutral">{{ $situations['total'] }}</x-ui.badge></x-slot:trailing>
            </x-ui.link-row>
        </section>

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

        {{-- The Coach comes later (NM-18), its place is kept (prototype T5). --}}
        <div class="flex items-center justify-between gap-3 rounded-card border border-dashed border-grey-350 px-5 py-4 text-grey-700" aria-disabled="true">
            <span>
                <span class="block font-semibold">{{ __('diagnosis.peer_home.coach') }}</span>
                <span class="block text-sm">{{ __('diagnosis.peer_home.coach_help') }}</span>
            </span>
            <span class="text-sm">{{ __('report.soon') }}</span>
        </div>

        <x-ui.link-row :href="route('client.values', $main->diagnosis->organization)" :title="__('client.menu.value_library')" :meta="__('diagnosis.peer_home.library_help', ['count' => \App\Services\Report\HungarianNumber::word($valueCount)])" />
    @endif

    @foreach ($otherRoles as $leaderMain)
        <x-ui.link-row :href="route('client.diagnosis.show', [$main->diagnosis->organization, $leaderMain->diagnosis])" :title="__('diagnosis.peer_home.leader_link', ['subject' => $leaderMain->subjectName()])" />
    @endforeach
</div>
