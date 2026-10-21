<div class="max-w-5xl">
    <x-ui.flash class="mb-6" />

    @include('livewire.admin.capabilities.partials.header', ['capability' => $capability])

    @if ($groups->isEmpty())
        <x-ui.notice>{{ __('admin.patterns.capability_tab.empty') }}</x-ui.notice>
    @else
        <div class="flex flex-col gap-6">
            <p class="text-grey-700">{{ __('admin.patterns.capability_tab.lead') }}</p>

            @foreach ($groups as $group => $patterns)
                <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-4" aria-labelledby="patterns-{{ $loop->index }}">
                    <x-ui.section-header :id="'patterns-'.$loop->index" :title="$group.' ('.$patterns->count().')'" />

                    <ul class="flex flex-col gap-4">
                        @foreach ($patterns as $pattern)
                            <li wire:key="pattern-{{ $pattern->id }}" class="flex flex-col gap-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('admin.patterns.show', $pattern) }}" class="font-semibold text-charcoal">
                                        <span class="font-mono text-sm">{{ $pattern->code }}</span> · {{ $pattern->name }}
                                    </a>
                                    @if ($pattern->pivot->fit)
                                        <x-ui.badge>{{ __('enums.pattern_fit.'.$pattern->pivot->fit) }}</x-ui.badge>
                                    @endif
                                    <x-ui.badge tone="muted">{{ $pattern->maturity()->label() }}</x-ui.badge>
                                    @if ($pattern->validity !== \App\Enums\PatternValidity::Valid)
                                        <x-ui.badge tone="muted">{{ $pattern->validity->label() }}</x-ui.badge>
                                    @endif
                                </div>
                                @if ($pattern->text('behavior'))
                                    <p class="text-grey-700">{{ $pattern->text('behavior') }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endforeach
        </div>
    @endif
</div>
