{{--
    Organization home (charcoal header, 15 · 15.2) before a diagnosis opens for
    the user (26 · 26.4): the way ahead, grey and not clickable. The leader's
    steps and the team's under them, or the peer's (HomePreview).
--}}
@php
    $peerPath = fn () => collect([
        __('client.home.path.invitation'),
        __('diagnosis.what_awaits'),
        ...array_map(fn ($state) => $state->step->label(), $preview['peerSteps']->steps),
        __('client.home.path.contribute'),
        __('client.home.path.takeaway'),
    ]);
@endphp

<x-layouts::client :title="__('client.home.title')" color="charcoal" :subtitle="$organization->name">
    <div class="flex max-w-2xl flex-col gap-6">
        <section class="flex flex-col gap-4">
            <x-megis.symbol name="tree-of-life" class="size-14 text-charcoal" />
            <h2 class="text-xl font-semibold">{{ __('client.home.greeting', ['name' => $user->first_name ?? $user->name]) }}</h2>
            <p>{{ $lead }}</p>
        </section>

        @if ($preview['role'] === 'leader')
            <section class="flex flex-col gap-3" aria-labelledby="leader-path">
                <div>
                    <h3 id="leader-path" class="text-lg font-semibold">{{ __('client.home.leader_path') }}</h3>
                    <p class="text-sm text-grey-700">
                        {{ __($preview['main']?->isSnapshot() ? 'diagnosis.steps_lead_snapshot' : 'diagnosis.steps_lead', ['count' => \App\Services\Report\HungarianNumber::word($preview['leaderSteps']->count())]) }}
                        {{ __('diagnosis.minutes_about', ['minutes' => $preview['leaderSteps']->totalMinutes()]) }}
                    </p>
                </div>
                <x-diagnosis.step-list :steps="$preview['leaderSteps']" :step-url="null" :open="false" />
            </section>

            @if ($preview['peerSteps'])
                <section class="flex flex-col gap-2" aria-labelledby="team-path">
                    <h3 id="team-path" class="text-lg font-semibold">
                        {{ $preview['team'] ? __('client.home.team_path_named', ['team' => $preview['team']]) : __('client.home.team_path') }}
                    </h3>
                    @if ($preview['members'] !== [])
                        <p>{{ implode(' · ', $preview['members']) }}</p>
                    @endif
                    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-grey-700" aria-label="{{ __('client.home.path.label') }}">
                        @foreach ($peerPath() as $label)
                            <li class="flex items-center gap-2">
                                @unless ($loop->first)
                                    <x-megis.icon name="arrow-right" class="size-3" />
                                @endunless
                                {{ $label }}
                            </li>
                        @endforeach
                    </ol>
                    <p class="text-sm text-grey-700">{{ __('client.home.team_path_help') }}</p>
                </section>
            @endif

            @if ($preview['main'])
                <x-diagnosis.end-picture :main="$preview['main']" />
            @endif
        @else
            <section class="flex flex-col gap-3" aria-labelledby="team-path">
                <div>
                    <h3 id="team-path" class="text-lg font-semibold">
                        {{ $preview['team'] ? __('client.home.team_path_named', ['team' => $preview['team']]) : __('client.home.team_path') }}
                    </h3>
                    <p class="text-sm text-grey-700">
                        {{ $preview['leaderName'] ? __('client.home.invited_soon', ['leader' => $preview['leaderName'], 'subject' => $preview['subject']]) : __('client.home.invited_later') }}
                    </p>
                </div>
                <x-diagnosis.step-list :steps="$preview['peerSteps']" :step-url="null" :open="false" />
                {{-- What comes later: adding while it runs, the Útravaló after publishing (25 · 25.5, 29). --}}
                <div class="flex items-center gap-3 rounded-card bg-grey-100 px-5 py-4">
                    <span class="grid size-7 shrink-0 place-items-center rounded-pill bg-grey-300 text-charcoal" aria-hidden="true">
                        <x-megis.icon name="plus" class="size-4" />
                    </span>
                    <span class="flex-1">
                        <span class="block font-semibold">{{ __('diagnosis.peer_add') }}</span>
                        <span class="block text-sm text-grey-700">{{ __('diagnosis.peer_add_help') }}</span>
                    </span>
                </div>
                <div class="rounded-card border border-dashed border-grey-350 px-5 py-4 text-grey-700">
                    <span class="block font-semibold">{{ __('client.home.path.takeaway') }}</span>
                    <span class="block text-sm">{{ __('client.home.takeaway_help') }}</span>
                </div>
            </section>
        @endif
    </div>
</x-layouts::client>
