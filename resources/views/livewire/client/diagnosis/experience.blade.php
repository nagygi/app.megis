{{-- V5 · A megélésed (08 · 8.3), the peer's T4 (25 · 25.4). --}}
<div class="flex max-w-2xl flex-col gap-6">
    <p class="text-grey-700" aria-live="polite">{{ __('diagnosis.experience.count', ['answered' => $answeredCount, 'total' => $total]) }}</p>

    <x-form.error name="answers" id="answers" />

    @foreach ([['title' => __('diagnosis.experience.values'), 'rows' => $valueRows], ['title' => __('diagnosis.experience.brand'), 'rows' => $treeRows]] as $block)
        <section class="flex flex-col gap-3" aria-label="{{ $block['title'] }}">
            <h3 class="text-lg font-semibold">{{ $block['title'] }}</h3>
            @foreach ($block['rows'] as $row)
                @php($inputName = 'answers.'.$row['key'])
                <x-ui.card wire:key="row-{{ $row['key'] }}" class="flex flex-col gap-3">
                    <div class="flex flex-col gap-1">
                        <p class="font-semibold">{{ $row['label'] }}</p>
                        @if (filled($row['meaning']))
                            <p class="text-sm text-grey-700">{{ $row['meaning'] }}</p>
                        @endif
                        @if ($row['kind'] === 'value' && (filled($row['low']) || filled($row['high'])))
                            <x-ui.button tone="ghost" size="sm" class="-ml-1.5 self-start" :icon-right="$openKey === $row['key'] ? 'chevron-up' : 'chevron-down'" wire:click="toggle('{{ $row['key'] }}')" aria-expanded="{{ $openKey === $row['key'] ? 'true' : 'false' }}">
                                {{ __('diagnosis.experience.definition', ['name' => $row['label']]) }}
                            </x-ui.button>
                            @if ($openKey === $row['key'])
                                <dl class="grid grid-cols-[2rem_1fr] gap-1 rounded-field bg-grey-100 p-3 text-sm">
                                    <dt class="font-extrabold text-coral-ink">1</dt>
                                    <dd>{{ $row['low'] }}</dd>
                                    <dt class="font-extrabold text-green">6</dt>
                                    <dd>{{ $row['high'] }}</dd>
                                </dl>
                            @endif
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
