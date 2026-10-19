{{-- V10, V10·b · the leader's home while it runs and is read (26 · 26.1, 26.2). Numbers, never answers. --}}
@php($stepState = fn (string $state) => __('diagnosis.home.states.'.$state))
<div class="flex max-w-2xl flex-col gap-6 pb-16">
    <x-ui.flash />

    @if ($home['correcting'])
        <p class="rounded-card bg-yellow-pale p-4">{{ __('diagnosis.home.correcting') }}</p>
    @endif

    {{-- In place of the ring: the publishing day and the measuring points --}}
    <section class="flex flex-col items-center gap-1 rounded-card bg-white p-6 text-center" aria-label="{{ __('diagnosis.home.report') }}">
        <p class="text-3xl font-extrabold tabular-nums">{{ $main->publishes_on->format('m.d.') }}</p>
        <p class="text-grey-700">{{ __('diagnosis.home.ring', ['count' => $home['measuringPoints']]) }}</p>
    </section>

    <section class="flex flex-col gap-4" aria-labelledby="where-we-are">
        <h2 id="where-we-are" class="text-lg font-semibold">{{ __('diagnosis.home.where') }}</h2>

        <div class="flex flex-col gap-1">
            <ol class="flex gap-1" aria-label="{{ __('diagnosis.home.'.$home['unit'].'_of', ['current' => $home['current'], 'count' => $home['count']]) }}">
                @foreach ($home['segments'] as $segment)
                    <li @class([
                        'h-2 flex-1 rounded-pill',
                        'bg-charcoal' => $segment === 'done',
                        'bg-coral' => $segment === 'now',
                        'bg-grey-200' => $segment === 'later',
                    ])></li>
                @endforeach
            </ol>
            <p class="text-sm text-grey-700">{{ __('diagnosis.home.'.$home['unit'].'_of', ['current' => $home['current'], 'count' => $home['count']]) }}</p>
        </div>

        <ul class="flex flex-col gap-2">
            {{-- Onboarding: tapping opens the step list (23, question 3) --}}
            <li class="rounded-card bg-white px-4 py-3">
                <details>
                    <summary class="flex cursor-pointer items-center justify-between gap-3">
                        <span>
                            <span class="block font-semibold">{{ __('diagnosis.home.onboarding') }}</span>
                            <span class="block text-sm text-grey-700">{{ __('diagnosis.home.onboarding_help', ['date' => $home['onboarding']['date']?->format('m.d.') ?? '–']) }}</span>
                        </span>
                        <span class="text-sm">{{ $stepState($home['onboarding']['done'] ? 'done' : 'now') }}</span>
                    </summary>
                    <div class="mt-3">
                        <x-diagnosis.step-list :steps="$steps" :step-url="$stepUrl" />
                    </div>
                </details>
            </li>

            @if ($home['peers'])
                <li class="flex flex-col gap-2 rounded-card bg-white px-4 py-3">
                    <div class="flex items-center justify-between gap-3">
                        <span>
                            <span class="block font-semibold">{{ __('diagnosis.home.peers') }}</span>
                            <span class="block text-sm text-grey-700">{{ __('diagnosis.home.peers_count', ['answered' => $home['peers']['answered'], 'total' => $home['peers']['total']]) }}</span>
                        </span>
                        @if ($main->stage === \App\Enums\MainDiagnosisStage::Running && $home['peers']['answered'] < $home['peers']['total'])
                            <x-ui.button tone="outline" size="sm" wire:click="remindPeers">{{ __('diagnosis.home.remind') }}</x-ui.button>
                        @endif
                    </div>
                    @if ($home['peers']['missing'] > 0)
                        <p class="text-sm text-grey-600">{{ trans_choice('diagnosis.home.missing_peers', $home['peers']['missing'], ['count' => $home['peers']['missing']]) }}</p>
                    @endif
                </li>
            @endif

            <li class="flex items-center justify-between gap-3 rounded-card bg-white px-4 py-3">
                <span>
                    <span class="block font-semibold">{{ __('admin.run.conversations.title') }}</span>
                    <span class="block text-sm text-grey-700">
                        {{ __('diagnosis.home.conversations', ['held' => $home['conversations']['held']]) }}
                        @if ($home['conversations']['next'])
                            · {{ __('diagnosis.home.conversation_next', ['date' => $home['conversations']['next']->held_at->format('m.d. H:i'), 'admin' => $home['conversations']['next']->admin?->first_name ?? '']) }}
                        @endif
                    </span>
                </span>
            </li>

            <li>
                <a href="{{ route('client.diagnosis.situations', [$main->diagnosis->organization, $main->diagnosis]) }}" class="flex items-center justify-between gap-3 rounded-card bg-white px-4 py-3 text-charcoal no-underline hover:bg-paper">
                    <span>
                        <span class="block font-semibold">{{ __('diagnosis.home.contributions') }}</span>
                        <span class="block text-sm text-grey-700">{{ __('diagnosis.home.contributions_count', $home['contributions']) }}</span>
                    </span>
                    <x-megis.icon name="chevron-right" class="size-4 shrink-0" />
                </a>
            </li>

            <li class="flex items-center justify-between gap-3 rounded-card bg-white px-4 py-3">
                <span>
                    <span class="block font-semibold">{{ __('diagnosis.home.reading') }}</span>
                    <span class="block text-sm text-grey-700">{{ $home['reading']['from']->format('m.d.') }} → {{ $home['reading']['to']->format('m.d.') }}</span>
                </span>
                <span @class(['text-sm', 'font-semibold' => $home['reading']['state'] === 'now'])>{{ $stepState($home['reading']['state']) }}</span>
            </li>

            <li class="flex items-center justify-between gap-3 rounded-card bg-white px-4 py-3">
                <span class="font-semibold">{{ __('diagnosis.home.report') }}</span>
                <span class="text-sm">{{ $main->publishes_on->format('m.d.') }}</span>
            </li>
        </ul>
    </section>

    {{-- Mélyítés: grey, not clickable until published (26 · 26.1) --}}
    <p class="rounded-card border border-dashed border-grey-350 px-4 py-3 text-sm text-grey-600" aria-disabled="true">
        {{ $main->isSnapshot() ? __('diagnosis.home.deepening_snapshot', ['date' => $main->publishes_on->format('m.d.')]) : __('diagnosis.home.deepening', ['date' => $main->publishes_on->format('m.d.')]) }}
    </p>

    @foreach ($otherRoles as $peerMain)
        <a href="{{ route('client.diagnosis.show', [$main->diagnosis->organization, $peerMain->diagnosis]) }}" class="text-sm font-semibold">
            {{ __('diagnosis.peer_link', ['subject' => $peerMain->subjectName()]) }}
        </a>
    @endforeach
</div>
