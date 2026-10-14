{{-- T2 · Mi vár rád, T5 · the peer's home while it runs (25 · 25.3, 25.5). --}}
<div class="flex max-w-2xl flex-col gap-6">
    @if ($withdrawn)
        <p class="rounded-card bg-grey-100 p-4">{{ __('diagnosis.peer_home.withdrawn') }}</p>
    @elseif ($current)
        {{-- T2 --}}
        <div class="flex flex-col gap-1">
            <p class="text-xl font-semibold">{{ __('diagnosis.peer_steps_lead') }}</p>
            <p class="text-grey-700">{{ __('diagnosis.minutes_about', ['minutes' => $steps->totalMinutes()]) }}</p>
        </div>

        <x-diagnosis.step-list :steps="$steps" :step-url="$stepUrl" />

        <div class="flex items-center gap-3 rounded-card bg-grey-100 px-4 py-3 text-grey-700">
            <span class="grid size-7 shrink-0 place-items-center rounded-pill bg-grey-300 text-xs font-bold" aria-hidden="true">+</span>
            <span class="flex-1">
                <span class="block font-semibold">{{ __('diagnosis.peer_add') }}</span>
                <span class="block text-sm">{{ __('diagnosis.peer_add_help') }}</span>
            </span>
        </div>

        @if ($main->acceptsInput())
            <div>
                <x-ui.button tone="coral" size="lg" :href="$stepUrl($current->step)">{{ $steps->isStarted() ? __('diagnosis.continue') : __('diagnosis.start') }}</x-ui.button>
            </div>
        @endif

        <x-diagnosis.end-picture :main="$main" peer />
    @else
        {{-- T5 --}}
        <section class="flex flex-col gap-3" aria-labelledby="brought-in">
            <h2 id="brought-in" class="text-lg font-semibold">{{ __('diagnosis.peer_home.brought_in') }}</h2>
            <a href="{{ $stepUrl(\App\Enums\DiagnosisStep::OwnValues) }}" class="flex items-center justify-between gap-3 rounded-card bg-white px-4 py-3 text-charcoal no-underline hover:bg-paper">
                <span>
                    <span class="block font-semibold">{{ __('enums.diagnosis_step.own_values') }}</span>
                    <span class="block text-sm text-grey-700">{{ $ownValues->implode(' · ') }}</span>
                </span>
                <x-megis.icon name="chevron-right" class="size-4" />
            </a>
            <a href="{{ $stepUrl(\App\Enums\DiagnosisStep::Experience) }}" class="flex items-center justify-between gap-3 rounded-card bg-white px-4 py-3 text-charcoal no-underline hover:bg-paper">
                <span>
                    <span class="block font-semibold">{{ __('enums.diagnosis_step.experience') }}</span>
                    @if ($experience)
                        <span class="block text-sm text-grey-700">{{ __('diagnosis.peer_home.experience_at', ['date' => $experience->diagnosed_on->format('m.d.')]) }}</span>
                    @endif
                </span>
                <x-megis.icon name="chevron-right" class="size-4" />
            </a>
        </section>

        <a href="{{ route('client.diagnosis.situations', [$main->diagnosis->organization, $main->diagnosis]) }}" class="flex items-center justify-between gap-3 rounded-card bg-white px-4 py-3 text-charcoal no-underline hover:bg-paper">
            <span>
                <span class="block font-semibold">{{ __('diagnosis.peer_home.situations') }}</span>
                <span class="block text-sm text-grey-700">{{ __('diagnosis.peer_home.situations_count', ['count' => $situationCount]) }}</span>
            </span>
            <x-megis.icon name="chevron-right" class="size-4" />
        </a>

        <div class="flex items-center justify-between gap-3 rounded-card border border-dashed border-grey-350 px-4 py-3 text-grey-700">
            <span>
                <span class="block font-semibold">{{ __('diagnosis.end.takeaway') }}</span>
                <span class="block text-sm">{{ __('diagnosis.end.takeaway_help') }}</span>
            </span>
            <span class="text-sm">{{ $main->publishes_on->format('m.d.') }}</span>
        </div>

        <a href="{{ route('client.values', $main->diagnosis->organization) }}" class="flex items-center justify-between gap-3 rounded-card bg-white px-4 py-3 text-charcoal no-underline hover:bg-paper">
            <span>
                <span class="block font-semibold">{{ __('client.menu.value_library') }}</span>
                <span class="block text-sm text-grey-700">{{ __('diagnosis.peer_home.library_help') }}</span>
            </span>
            <x-megis.icon name="chevron-right" class="size-4" />
        </a>
    @endif

    @foreach ($otherRoles as $leaderMain)
        <a href="{{ route('client.diagnosis.show', [$main->diagnosis->organization, $leaderMain->diagnosis]) }}" class="text-sm font-semibold">
            {{ __('diagnosis.peer_home.leader_link', ['subject' => $leaderMain->subjectName()]) }}
        </a>
    @endforeach
</div>
