<div class="flex flex-col gap-4">
    <x-admin.page-header :title="__('admin.ai.prompts_title')" />

    <p class="max-w-3xl text-sm text-grey-700">{{ __('admin.ai.prompts_help') }}</p>

    <div class="grid gap-6 lg:grid-cols-[22rem_1fr]">
        <ul class="flex flex-col gap-3">
            @foreach ($groups as $group)
                <li wire:key="prompt-{{ $group['prompt'] }}" class="rounded-card border border-grey-250 bg-white p-4">
                    <p class="font-semibold">{{ __('admin.ai.tasks.'.$group['task']) }}</p>
                    <p class="text-sm text-grey-600">{{ $group['prompt'] }}</p>
                    @if ($group['versions'] === [])
                        <p class="pt-2 text-sm text-grey-600">{{ __('admin.ai.no_prompt_yet') }}</p>
                    @else
                        <ul class="flex flex-wrap gap-2 pt-2">
                            @foreach (array_reverse($group['versions']) as $version)
                                @php($isOpen = $opened?->name === $group['prompt'] && $opened?->version === $version)
                                <li>
                                    <button
                                        type="button"
                                        wire:click="open('{{ $group['prompt'] }}', {{ $version }})"
                                        @class([
                                            'rounded-pill border px-3 py-1 text-sm',
                                            'border-charcoal bg-charcoal text-cream' => $isOpen,
                                            'border-grey-350 hover:bg-grey-100' => ! $isOpen,
                                        ])
                                        @if ($isOpen) aria-current="true" @endif
                                    >
                                        {{ 'v'.$version.($version === $group['active'] ? ' · '.__('admin.ai.active') : '') }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>

        <div>
            @if ($opened)
                <div class="flex flex-col gap-2 rounded-card border border-grey-250 bg-white p-5">
                    <h2 class="font-semibold">{{ $opened->name }} · v{{ $opened->version }}</h2>
                    <pre class="max-h-[70vh] overflow-auto whitespace-pre-wrap rounded-xl bg-paper p-4 text-sm">{{ $opened->content }}</pre>
                </div>
            @else
                <p class="text-grey-600">{{ __('admin.ai.pick_prompt') }}</p>
            @endif
        </div>
    </div>
</div>
