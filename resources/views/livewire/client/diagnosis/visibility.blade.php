{{-- V9 · Ki lát mit (23 · 23.4). --}}
<div class="flex max-w-2xl flex-col gap-6">
    <x-ui.flash />

    <h2 class="text-xl font-semibold">{{ __('diagnosis.visibility.lead') }}</h2>

    <dl class="flex flex-col gap-3">
        @php
            $actors = [
                'you' => [
                    'sees' => $main->show_peer_quotes && $main->hasPeers() ? __('diagnosis.visibility.you_sees_quotes') : __('diagnosis.visibility.you_sees'),
                    'not' => __('diagnosis.visibility.you_not'),
                ],
            ];

            if ($main->hasPeers()) {
                $actors['peers'] = ['sees' => __('diagnosis.visibility.peers_sees'), 'not' => __('diagnosis.visibility.peers_not')];
            }

            $actors['megis'] = ['sees' => __('diagnosis.visibility.megis_sees'), 'not' => __('diagnosis.visibility.megis_not')];
        @endphp

        @foreach ($actors as $key => $lines)
            <x-ui.card padding="sm" class="flex flex-col gap-1">
                <dt class="font-semibold">{{ __('diagnosis.visibility.actor.'.$key) }}</dt>
                <dd class="flex items-start gap-2"><x-megis.icon name="check" class="mt-1 text-green" :label="__('diagnosis.visibility.sees')" /> {{ $lines['sees'] }}</dd>
                <dd class="flex items-start gap-2 text-grey-700"><x-megis.icon name="close" class="mt-1" :label="__('diagnosis.visibility.not')" /> {{ $lines['not'] }}</dd>
            </x-ui.card>
        @endforeach
    </dl>

    @if ($main->stage->isBeforeRunning())
        <div class="flex flex-col gap-3">
            @if ($missing)
                <x-ui.notice tone="warning">
                    {{ __('diagnosis.step_missing', ['step' => $missing->step->label()]) }}
                    <x-slot:action>
                        <x-ui.button tone="outline" size="sm" :href="$missingUrl">{{ __('diagnosis.visibility.go_to_missing') }}</x-ui.button>
                    </x-slot:action>
                </x-ui.notice>
            @endif
            <div>
                @if ($canStart)
                    <x-ui.button size="lg" icon-right="arrow-right" wire:click="startDiagnosis">{{ __('diagnosis.visibility.start') }}</x-ui.button>
                @else
                    <x-ui.button size="lg" icon-right="arrow-right" :href="$missingUrl" aria-disabled="true">{{ __('diagnosis.visibility.start') }}</x-ui.button>
                @endif
            </div>
        </div>
    @else
        <x-ui.notice>{{ __('diagnosis.visibility.running') }}</x-ui.notice>
    @endif
</div>
