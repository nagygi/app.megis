{{-- V5 · A megélésed (08 · 8.3), the peer's T4 (25 · 25.4). --}}
<div class="flex max-w-2xl flex-col gap-6">
    <div class="flex flex-col gap-1">
        <p class="text-xl font-semibold">{{ __('diagnosis.experience.question') }}</p>
        <p class="text-grey-700" aria-live="polite">{{ __('diagnosis.experience.count', ['answered' => $answeredCount, 'total' => $total]) }}</p>
    </div>

    <x-form.error name="answers" id="answers" />

    @foreach ([['title' => __('diagnosis.experience.values'), 'rows' => $valueRows], ['title' => __('diagnosis.experience.brand'), 'rows' => $treeRows]] as $block)
        <section class="flex flex-col gap-3" aria-label="{{ $block['title'] }}">
            <h2 class="text-lg font-semibold">{{ $block['title'] }}</h2>
            @foreach ($block['rows'] as $row)
                @php($inputName = 'answers.'.$row['key'])
                <div wire:key="row-{{ $row['key'] }}" class="flex flex-col gap-3 rounded-card bg-white p-5">
                    <div class="flex flex-col gap-1">
                        <p class="font-semibold">{{ $row['label'] }}</p>
                        @if (filled($row['meaning']))
                            <p class="text-sm text-grey-700">{{ $row['meaning'] }}</p>
                        @endif
                        @if ($row['kind'] === 'value' && (filled($row['low']) || filled($row['high'])))
                            <button type="button" wire:click="toggle('{{ $row['key'] }}')" class="self-start text-sm font-semibold underline" aria-expanded="{{ $openKey === $row['key'] ? 'true' : 'false' }}">
                                {{ __('diagnosis.experience.definition', ['name' => $row['label']]) }}
                            </button>
                            @if ($openKey === $row['key'])
                                <dl class="grid gap-1 rounded-field bg-grey-100 p-3 text-sm sm:grid-cols-[2rem_1fr]">
                                    <dt class="font-extrabold text-coral-ink">1</dt>
                                    <dd>{{ $row['low'] }}</dd>
                                    <dt class="font-extrabold text-green">6</dt>
                                    <dd>{{ $row['high'] }}</dd>
                                </dl>
                            @endif
                        @endif
                    </div>
                    <x-diagnosis.scale :name="$inputName" :label="$row['label']" :disabled="! $editable" />
                </div>
            @endforeach
        </section>
    @endforeach

    @if ($editable)
        <div class="flex flex-wrap items-center gap-4">
            <x-ui.button tone="coral" size="lg" wire:click="submit" :disabled="$answeredCount < $total">{{ __('diagnosis.next') }}</x-ui.button>
            <x-diagnosis.saved />
        </div>
    @else
        <p class="rounded-card bg-grey-100 p-4 text-sm">{{ __('diagnosis.locked') }}</p>
    @endif
</div>
