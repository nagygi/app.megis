{{-- V5 · A megélésed (08 · 8.3), the peer's T4 (25 · 25.4). What is asked and who sees it comes first (MEGIS-2, MEGIS-3). --}}
<div class="flex max-w-2xl flex-col gap-6">
    <div class="flex flex-col gap-3">
        <h2 class="text-xl font-semibold">{{ __('client.home.greeting', ['name' => $firstName]) }}</h2>
        <p>{{ $individual ? __('diagnosis.experience.values_lead_individual') : __('diagnosis.experience.values_lead', ['organization' => $this->organization->name]) }}</p>
        @unless ($this->isLeader())
            <x-ui.notice>{{ __('diagnosis.experience.peer_visibility', ['count' => $peerThreshold]) }}</x-ui.notice>
        @endunless
    </div>

    <p class="text-grey-700" aria-live="polite">{{ __('diagnosis.experience.count', ['answered' => $answeredCount, 'total' => $total]) }}</p>

    <x-form.error name="answers" id="answers" />

    @foreach ([
        ['title' => __('diagnosis.experience.values'), 'lead' => null, 'note' => null, 'rows' => $valueRows],
        ['title' => __('diagnosis.experience.brand'), 'lead' => __($individual ? 'diagnosis.experience.brand_lead_individual' : 'diagnosis.experience.brand_lead'), 'note' => $givenBy, 'rows' => $treeRows],
    ] as $block)
        <section class="flex flex-col gap-3" aria-label="{{ $block['title'] }}">
            <div class="flex flex-col gap-1">
                <h3 class="text-lg font-semibold">{{ $block['title'] }}</h3>
                @if ($block['lead'])
                    <p>{{ $block['lead'] }}</p>
                @endif
                @if ($block['note'])
                    <p class="text-sm text-grey-700">{{ $block['note'] }}</p>
                @endif
            </div>
            @foreach ($block['rows'] as $row)
                @php($inputName = 'answers.'.$row['key'])
                <x-ui.card wire:key="row-{{ $row['key'] }}" class="flex flex-col gap-3">
                    <div class="flex flex-col gap-1">
                        <p class="font-semibold">{{ $row['label'] }}</p>
                        @if (isset($row['question']))
                            {{-- The brand base: the question V4 asked, then what the organization answered (MEGIS-3). --}}
                            <p class="text-sm text-grey-700">{{ $row['question'] }}</p>
                            @if (filled($row['meaning']))
                                <p>{{ $row['meaning'] }}</p>
                            @endif
                        @elseif (filled($row['meaning']))
                            <p class="text-sm text-grey-700">{{ $row['meaning'] }}</p>
                        @endif
                        @if ($row['kind'] === 'value' && (filled($row['low']) || filled($row['high'])))
                            <p class="mt-2 text-sm font-semibold">{{ __('diagnosis.experience.definition', ['name' => $row['label']]) }}</p>
                            <dl class="grid grid-cols-[2rem_1fr] gap-1 rounded-field bg-grey-100 p-3 text-sm">
                                <dt class="font-extrabold text-coral-ink">1</dt>
                                <dd>{{ $row['low'] }}</dd>
                                <dt class="font-extrabold text-green">6</dt>
                                <dd>{{ $row['high'] }}</dd>
                            </dl>
                        @endif
                    </div>
                    <x-diagnosis.scale :name="$inputName" :label="$row['label']" :disabled="! $editable" />
                </x-ui.card>
            @endforeach
        </section>
    @endforeach

    @if ($editable)
        <x-diagnosis.next
            wire:click="submit"
            :disabled-reason="$answeredCount < $total ? __('diagnosis.experience.missing', ['count' => $total - $answeredCount]) : null"
        >{{ $this->nextLabel(\App\Enums\DiagnosisStep::Experience) }}</x-diagnosis.next>
    @else
        <x-ui.notice>{{ __('diagnosis.locked') }}</x-ui.notice>
    @endif
</div>
