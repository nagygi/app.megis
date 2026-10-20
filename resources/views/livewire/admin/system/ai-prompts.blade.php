<div class="flex flex-col gap-6">
    <x-admin.page-header :title="__('admin.ai.prompts_title')" :description="__('admin.ai.prompts_help')" :spaced="false" />

    <div class="grid gap-6 lg:grid-cols-[22rem_1fr]">
        <ul class="flex flex-col gap-3">
            @foreach ($groups as $group)
                <x-ui.card as="li" bordered padding="sm" wire:key="prompt-{{ $group['prompt'] }}">
                    <p class="font-semibold">{{ __('admin.ai.tasks.'.$group['task']) }}</p>
                    <p class="text-sm text-grey-700">{{ $group['prompt'] }}</p>
                    @if ($group['versions'] === [])
                        <p class="pt-2 text-sm text-grey-700">{{ __('admin.ai.no_prompt_yet') }}</p>
                    @else
                        <x-ui.segmented :label="__('admin.ai.tasks.'.$group['task'])" class="pt-2">
                            @foreach (array_reverse($group['versions']) as $version)
                                @php($isOpen = $opened?->name === $group['prompt'] && $opened?->version === $version)
                                <x-ui.segment :selected="$isOpen" :aria-current="$isOpen ? 'true' : false" wire:click="open('{{ $group['prompt'] }}', {{ $version }})">
                                    {{ 'v'.$version.($version === $group['active'] ? ' · '.__('admin.ai.active') : '') }}
                                </x-ui.segment>
                            @endforeach
                        </x-ui.segmented>
                    @endif
                </x-ui.card>
            @endforeach
        </ul>

        <div>
            @if ($opened)
                <x-ui.card bordered class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold">{{ $opened->name }} · v{{ $opened->version }}</h2>
                    <pre class="max-h-[70vh] overflow-auto whitespace-pre-wrap rounded-field bg-paper p-4 text-sm">{{ $opened->content }}</pre>
                </x-ui.card>
            @else
                <p class="text-grey-700">{{ __('admin.ai.pick_prompt') }}</p>
            @endif
        </div>
    </div>
</div>
