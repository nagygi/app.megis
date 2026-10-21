@php
    $label = fn (string $field) => __('admin.patterns.fields.'.$field);
    $none = __('admin.common.none');
@endphp

<div class="flex max-w-5xl flex-col gap-6">
    @include('livewire.admin.patterns.partials.header', ['pattern' => $pattern])

    {{-- Azonosítás --}}
    <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-4" aria-labelledby="pattern-identity">
        <x-ui.section-header id="pattern-identity" :title="__('admin.patterns.sections.identity')" />

        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-sm font-medium">{{ $label('capabilities') }}</dt>
                <dd class="text-grey-700">
                    @forelse ($pattern->capabilities as $capability)
                        <a href="{{ route('admin.capabilities.patterns', $capability) }}" class="block text-charcoal underline">
                            @php($fit = collect([$capability->pivot->fit ? __('enums.pattern_fit.'.$capability->pivot->fit) : null, $capability->pivot->fit_note])->filter()->implode(', '))
                            <span class="font-mono text-sm">{{ $capability->code }}</span> · {{ $capability->name }}{{ $fit !== '' ? ' ('.$fit.')' : '' }}
                        </a>
                    @empty
                        {{ __('admin.patterns.no_capability') }}
                    @endforelse
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium">{{ $label('level') }}</dt>
                <dd class="text-grey-700">
                    {{ collect([$pattern->level?->label(), $pattern->level_marker?->label()])->filter()->implode(' · ') ?: $none }}
                    @include('livewire.admin.partials.sheet-marks', ['item' => $pattern, 'field' => 'level'])
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium">{{ $label('preconditions') }}</dt>
                <dd class="text-grey-700">
                    @foreach ($pattern->preconditions as $precondition)
                        <span class="block">{{ $precondition->label() }}{{ $precondition->pivot->note ? ' ('.$precondition->pivot->note.')' : '' }}</span>
                    @endforeach
                    @if ($pattern->precondition_note)
                        <span class="block whitespace-pre-line">{{ $pattern->precondition_note }}</span>
                    @endif
                    @if ($pattern->preconditions->isEmpty() && ! $pattern->precondition_note)
                        {{ $none }}
                    @endif
                    @include('livewire.admin.partials.sheet-marks', ['item' => $pattern, 'field' => 'preconditions'])
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium">{{ $label('products') }}</dt>
                <dd class="text-grey-700">{{ collect($pattern->products)->map(fn ($product) => \App\Enums\PatternProduct::tryFrom($product)?->label() ?? $product)->implode(', ') ?: $none }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium">{{ $label('externally_visible') }}</dt>
                <dd class="text-grey-700">{{ match ($pattern->externally_visible) { true => __('admin.capabilities.tempting_yes'), false => __('admin.capabilities.tempting_no'), default => $none } }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium">{{ $label('where_visible') }}</dt>
                <dd class="text-grey-700">{{ implode(', ', $pattern->where_visible) ?: $none }} @include('livewire.admin.partials.sheet-marks', ['item' => $pattern, 'field' => 'where_visible'])</dd>
            </div>
            <div>
                <dt class="text-sm font-medium">{{ $label('origin') }}</dt>
                <dd class="text-grey-700">{{ $pattern->origin?->label() ?? $none }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium">{{ $label('validity') }}</dt>
                <dd class="text-grey-700">{{ $pattern->validity->label() }}</dd>
            </div>
        </dl>
    </x-ui.card>

    {{-- The text fields of the Sheet, by block; the source and the provenance are for the mégis only. --}}
    @foreach (\App\Models\Pattern::CONTENT_BLOCKS as $block => $fields)
        @php($filled = collect($fields)->filter(fn ($field) => $pattern->text($field) !== null || $pattern->fieldState($field) !== null))
        @continue($filled->isEmpty())

        <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-4" aria-labelledby="pattern-{{ $block }}">
            <x-ui.section-header :id="'pattern-'.$block" :title="__('admin.patterns.sections.'.$block)" :description="in_array($block, ['source', 'provenance'], true) ? __('admin.patterns.admin_only') : null" />

            <dl class="flex flex-col gap-3">
                @foreach ($filled as $field)
                    <div wire:key="field-{{ $field }}">
                        <dt class="text-sm font-medium">{{ $label($field) }} @include('livewire.admin.partials.sheet-marks', ['item' => $pattern, 'field' => $field])</dt>
                        <dd class="whitespace-pre-line text-grey-700">{{ $pattern->text($field) ?? $none }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-ui.card>
    @endforeach

    {{-- Számok: computed by the Sheet from its Találat and Kapcsolat sheets; the case data never comes in. --}}
    <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-4" aria-labelledby="pattern-counts">
        <x-ui.section-header id="pattern-counts" :title="__('admin.patterns.sections.counts')" :description="__('admin.patterns.counts_help')" />

        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach (['sheet_approved_clients', 'sheet_proposed_findings', 'sheet_false_alarms', 'sheet_evidence_count'] as $counter)
                <div>
                    <dt class="text-sm font-medium">{{ $label($counter) }}</dt>
                    <dd class="text-2xl font-semibold">{{ $pattern->{$counter} }}</dd>
                </div>
            @endforeach
        </dl>
    </x-ui.card>
</div>
