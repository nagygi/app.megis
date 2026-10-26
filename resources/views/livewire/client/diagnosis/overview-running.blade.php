{{-- V10, V10·b · the leader's home while it runs and is read (26 · 26.1, 26.2). Numbers, never answers. --}}
@php($stepState = fn (string $state) => __('diagnosis.home.states.'.$state))
<div class="flex max-w-2xl flex-col gap-6">
    <x-ui.flash />

    @if ($home['correcting'])
        <x-ui.notice>{{ __('diagnosis.home.correcting') }}</x-ui.notice>
    @endif

    {{-- In place of the ring: the publishing day and the measuring points --}}
    <x-ui.card as="section" padding="lg" class="flex flex-col items-center gap-1 text-center" aria-label="{{ __('diagnosis.home.report') }}">
        <p class="text-3xl font-extrabold tabular-nums">{{ $main->publishes_on->format('m.d.') }}</p>
        <p class="text-grey-700">{{ __('diagnosis.home.ring', ['count' => $home['measuringPoints']]) }}</p>
    </x-ui.card>

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
            <x-ui.card as="li" padding="none">
                <details class="group">
                    <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 rounded-card px-5 py-4 hover:bg-paper [&::-webkit-details-marker]:hidden">
                        <span>
                            <span class="block font-semibold">{{ __('diagnosis.home.onboarding') }}</span>
                            <span class="block text-sm text-grey-700">{{ __('diagnosis.home.onboarding_help', ['date' => $home['onboarding']['date']?->format('m.d.') ?? '–']) }}</span>
                        </span>
                        <span class="flex shrink-0 items-center gap-2 text-sm">
                            {{ $stepState($home['onboarding']['done'] ? 'done' : 'now') }}
                            <x-megis.icon name="chevron-down" class="transition-transform duration-200 group-open:rotate-180" />
                        </span>
                    </summary>
                    <div class="px-5 pb-5">
                        <x-diagnosis.step-list :steps="$steps" :step-url="$stepUrl" />
                    </div>
                </details>
            </x-ui.card>

            @if ($home['peers'])
                <x-ui.card as="li" padding="none" class="flex flex-col gap-2 px-5 py-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span>
                            <span class="block font-semibold">{{ __('diagnosis.home.peers') }}</span>
                            <span class="block text-sm text-grey-700">{{ __('diagnosis.home.peers_count', ['answered' => $home['peers']['answered'], 'total' => $home['peers']['total']]) }}</span>
                        </span>
                        @if ($main->stage === \App\Enums\MainDiagnosisStage::Running && $home['peers']['answered'] < $home['peers']['total'])
                            <x-ui.button tone="outline" size="sm" icon="mail" wire:click="remindPeers">{{ __('diagnosis.home.remind') }}</x-ui.button>
                        @endif
                    </div>
                    @if ($home['peers']['missing'] > 0)
                        <p class="text-sm text-grey-700">{{ trans_choice('diagnosis.home.missing_peers', $home['peers']['missing'], ['count' => $home['peers']['missing']]) }}</p>
                    @endif
                </x-ui.card>
            @endif

            <x-ui.card as="li" padding="none" class="px-5 py-4">
                <span class="block font-semibold">{{ __('diagnosis.home.conversations_title') }}</span>
                <span class="block text-sm text-grey-700">
                    {{ __('diagnosis.home.conversations', ['held' => $home['conversations']['held']]) }}
                    @if ($home['conversations']['next'])
                        · {{ __('diagnosis.home.conversation_next', ['date' => $home['conversations']['next']->held_at->format('m.d. H:i'), 'admin' => $home['conversations']['next']->admin?->first_name ?? '']) }}
                    @endif
                </span>
            </x-ui.card>

            <li>
                <x-ui.link-row
                    :href="route('client.diagnosis.situations', [$main->diagnosis->organization, $main->diagnosis])"
                    :title="__('diagnosis.home.contributions')"
                    :meta="__('diagnosis.home.contributions_count', $home['contributions'])"
                />
            </li>

            {{-- Képességek: how many situations were told; open to more until the reading (NM-12) --}}
            @if ($home['capabilities'] !== null)
                @php($capabilityMeta = $main->hasPeers()
                    ? __('capabilities.home.count_with_peers', $home['capabilities'])
                    : trans_choice('capabilities.home.count', $home['capabilities']['total'], ['count' => $home['capabilities']['total']]))
                @if ($main->acceptsInput())
                    <li>
                        <x-ui.link-row :href="route('client.diagnosis.kepessegek', [$main->diagnosis->organization, $main->diagnosis])" :title="__('capabilities.home.title')" :meta="$capabilityMeta" />
                    </li>
                @else
                    <x-ui.card as="li" padding="none" class="px-5 py-4">
                        <span class="block font-semibold">{{ __('capabilities.home.title') }}</span>
                        <span class="block text-sm text-grey-700">{{ $capabilityMeta }}</span>
                    </x-ui.card>
                @endif
            @endif

            <x-ui.card as="li" padding="none" class="flex items-center justify-between gap-3 px-5 py-4">
                <span>
                    <span class="block font-semibold">{{ __('diagnosis.home.reading') }}</span>
                    <span class="block text-sm text-grey-700 tabular-nums">{{ $home['reading']['from']->format('m.d.') }} – {{ $home['reading']['to']->format('m.d.') }}</span>
                </span>
                <span @class(['text-sm', 'font-semibold' => $home['reading']['state'] === 'now'])>{{ $stepState($home['reading']['state']) }}</span>
            </x-ui.card>

            <x-ui.card as="li" padding="none" class="flex items-center justify-between gap-3 px-5 py-4">
                <span class="font-semibold">{{ __('diagnosis.home.report') }}</span>
                <span class="text-sm tabular-nums">{{ $main->publishes_on->format('m.d.') }}</span>
            </x-ui.card>
        </ul>
    </section>

    {{-- Mélyítés: grey, not clickable until published (26 · 26.1) --}}
    <p class="rounded-card border border-dashed border-grey-350 px-5 py-4 text-sm text-grey-700" aria-disabled="true">
        {{ $main->isSnapshot() ? __('diagnosis.home.deepening_snapshot', ['date' => $main->publishes_on->format('m.d.')]) : __('diagnosis.home.deepening', ['date' => $main->publishes_on->format('m.d.')]) }}
    </p>

    @foreach ($otherRoles as $peerMain)
        <x-ui.link-row :href="route('client.diagnosis.show', [$main->diagnosis->organization, $peerMain->diagnosis])" :title="__('diagnosis.peer_link', ['subject' => $peerMain->subjectName()])" />
    @endforeach
</div>
