{{--
    Organization home (charcoal header, 15 · 15.2) before a diagnosis opens for
    the user (26 · 26.4): the way ahead, grey and not clickable. The leader's
    steps and the team's under them, or the peer's (HomePreview). When a
    diagnosis is being set up, its start day too. An invited member's way
    starts with the Értéktükör: where they stand, and the button to it; once
    it is done, that there is nothing more to do for now.
--}}
@php
    $peerPath = fn () => collect([
        __('client.home.path.invitation'),
        __('diagnosis.what_awaits'),
        ...array_map(fn ($state) => $state->step->label(), $preview['peerSteps']->steps),
        __('client.home.path.contribute'),
        __('client.home.path.takeaway'),
    ]);
    $upcoming = $preview['upcoming'];
    $startsOn = $upcoming?->starts_on?->format('m.d.');
    $startsLabel = $upcoming ? ($startsOn ?? __('diagnosis.date_open')) : null;
    $mirrorSession = $firstStep['session'] ?? null;
    $mirrorHead = $firstStep === null ? null : [
        'title' => __('client.home.mirror_step.title'),
        'minutes' => intdiv((int) config('megis.materials.recording_max_seconds'), 60),
        'done' => $firstStep['state'] === 'done',
        'lead' => $firstStep['state'] === 'todo' ? __('client.home.mirror_step.lead') : null,
        'action' => match ($firstStep['state']) {
            'todo' => ['label' => __('client.home.mirror_step.start'), 'href' => route('client.mirror.record', $organization), 'tone' => 'dark'],
            'open' => ['label' => __('client.home.mirror_step.continue'), 'href' => route('client.mirror.result', [$organization, $mirrorSession]), 'tone' => 'dark'],
            default => ['label' => __('client.home.mirror_step.open'), 'href' => route('client.mirror.result', [$organization, $mirrorSession]), 'tone' => 'outline'],
        },
    ];
@endphp

<x-layouts::client :title="__('client.home.title')" color="charcoal" :subtitle="$organization->name">
    <div class="flex max-w-2xl flex-col gap-6">
        <section class="flex flex-col gap-4">
            <x-megis.symbol name="tree-of-life" class="size-14 text-charcoal" />
            <h2 class="text-xl font-semibold">{{ __('client.home.greeting', ['name' => $user->first_name ?? $user->name]) }}</h2>
            @if ($later)
                {{-- "Nem most" on T1: the invitation waits (25 · 25.2). --}}
                <p>{{ __('client.home.later', ['leader' => $later->leader->name, 'subject' => $later->subjectName()]) }}</p>
                <x-ui.button class="self-start" icon-right="arrow-right" :href="route('client.diagnosis.show', [$organization, $later->diagnosis])">{{ __('client.home.later_continue') }}</x-ui.button>
            @else
                <p>{{ $lead }}</p>
            @endif
        </section>

        @if (($firstStep['state'] ?? null) === 'done')
            <x-client.mirror-waiting>
                <a href="{{ route('client.sample-report', $organization) }}" class="mt-1 self-start font-semibold">{{ __('sample_report.link') }}</a>
            </x-client.mirror-waiting>
        @endif

        @unless ($later)
            @if ($preview['role'] === 'leader')
                <section class="flex flex-col gap-4" aria-labelledby="leader-path">
                    <div>
                        <h3 id="leader-path" class="text-lg font-semibold">{{ __('client.home.leader_path') }}</h3>
                        <p class="text-sm text-grey-700">
                            {{ __($preview['main']?->isSnapshot() ? 'diagnosis.steps_lead_snapshot' : 'diagnosis.steps_lead', ['count' => \App\Services\Report\HungarianNumber::word($preview['leaderSteps']->count())]) }}
                            {{ __('diagnosis.minutes_about', ['minutes' => $preview['leaderSteps']->totalMinutes()]) }}@if ($startsLabel) · {{ __('client.home.starts', ['date' => $startsLabel]) }}@endif
                        </p>
                    </div>
                    <x-diagnosis.step-list :steps="$preview['leaderSteps']" :step-url="null" :open="false" :head="$mirrorHead" />
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
                        <p class="text-sm text-grey-700">{{ $startsOn ? __('client.home.team_path_help_dated', ['date' => $startsOn]) : __('client.home.team_path_help') }}</p>
                    </section>
                @endif

                @if ($preview['main'])
                    <x-diagnosis.end-picture :main="$preview['main']" />
                @endif
            @else
                <section class="flex flex-col gap-4" aria-labelledby="team-path">
                    <div>
                        <h3 id="team-path" class="text-lg font-semibold">
                            {{ $preview['team'] ? __('client.home.team_path_named', ['team' => $preview['team']]) : __('client.home.team_path') }}
                        </h3>
                        <p class="text-sm text-grey-700">
                            {{ $preview['leaderName'] ? __('client.home.invited_soon', ['leader' => $preview['leaderName'], 'subject' => $preview['subject']]) : __('client.home.invited_later') }}
                            @if ($startsLabel)
                                {{ __('client.home.invited_starts', ['date' => $startsLabel]) }}
                            @endif
                        </p>
                    </div>
                    {{-- What comes later: adding while it runs, then the day the diagnosis is done, end the line (prototype T2, 25 · 25.3); the Útravaló after publishing (25 · 25.5, 29). --}}
                    <x-diagnosis.step-list :steps="$preview['peerSteps']" :step-url="null" :open="false" :head="$mirrorHead" :tail="[
                        ['title' => __('diagnosis.peer_add'), 'meta' => __('diagnosis.peer_add_help')],
                        ['title' => __('diagnosis.end.finish'), 'meta' => $preview['main']?->publishes_on?->format('m.d.')],
                    ]" />
                </section>

                <x-diagnosis.end-picture :main="$preview['main']" peer />
            @endif
        @endunless
    </div>
</x-layouts::client>
