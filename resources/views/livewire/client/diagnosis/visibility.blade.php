{{-- V9 · Ki lát mit (23 · 23.4). --}}
<div class="flex max-w-2xl flex-col gap-6">
    <p class="text-xl font-semibold">{{ __('diagnosis.visibility.lead') }}</p>

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
            <div class="flex flex-col gap-1 rounded-card bg-white p-4">
                <dt class="font-semibold">{{ __('diagnosis.visibility.actor.'.$key) }}</dt>
                <dd class="flex items-start gap-2"><x-megis.icon name="check" class="mt-1 size-4 text-green" :label="__('diagnosis.visibility.sees')" /> {{ $lines['sees'] }}</dd>
                <dd class="flex items-start gap-2 text-grey-700"><x-megis.icon name="close" class="mt-1 size-4" :label="__('diagnosis.visibility.not')" /> {{ $lines['not'] }}</dd>
            </div>
        @endforeach
    </dl>

    @if ($main->stage->isBeforeRunning())
        <div class="flex flex-col gap-3">
            @if ($missing)
                <p class="text-sm" role="status">
                    {{ __('diagnosis.step_missing', ['step' => $missing->step->label()]) }}
                    <a href="{{ $missingUrl }}" class="font-semibold">{{ __('diagnosis.visibility.go_to_missing') }}</a>
                </p>
            @endif
            <div>
                @if ($canStart)
                    <x-ui.button tone="coral" size="lg" wire:click="startDiagnosis">{{ __('diagnosis.visibility.start') }}</x-ui.button>
                @else
                    <x-ui.button tone="coral" size="lg" :href="$missingUrl" aria-disabled="true">{{ __('diagnosis.visibility.start') }}</x-ui.button>
                @endif
            </div>
        </div>
    @else
        <p class="rounded-card bg-grey-100 p-4 text-sm">{{ __('diagnosis.visibility.running') }}</p>
    @endif
</div>
