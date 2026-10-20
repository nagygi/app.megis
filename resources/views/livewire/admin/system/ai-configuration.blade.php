<div class="flex flex-col gap-4">
    <x-admin.page-header :title="__('admin.ai.configuration_title')" />

    <p class="max-w-3xl text-sm text-grey-700">
        {{ __('admin.ai.configuration_help') }}
        {{ $pricesSyncedAt
            ? __('admin.ai.prices_synced', ['date' => \Illuminate\Support\Carbon::parse($pricesSyncedAt)->format('Y.m.d. H:i')])
            : __('admin.ai.prices_never_synced') }}
    </p>

    <x-admin.table>
        <x-slot:head>
            <x-admin.th>{{ __('admin.ai.task') }}</x-admin.th>
            <x-admin.th>{{ __('admin.ai.provider') }}</x-admin.th>
            <x-admin.th>{{ __('admin.ai.model') }}</x-admin.th>
            <x-admin.th>{{ __('admin.ai.active_prompt') }}</x-admin.th>
            <x-admin.th>{{ __('admin.ai.unit_price') }}</x-admin.th>
            <x-admin.th>{{ __('admin.ai.last_success') }}</x-admin.th>
        </x-slot:head>

        @foreach ($tasks as $task)
            <tr wire:key="task-{{ $task['key'] }}">
                <td class="px-3 py-2 font-semibold">{{ __('admin.ai.tasks.'.$task['key']) }}</td>
                <td class="px-3 py-2">{{ $task['provider'] }}</td>
                <td class="px-3 py-2">{{ $task['model'] ?? __('admin.ai.not_chosen') }}</td>
                <td class="px-3 py-2">
                    @if ($task['prompt'] && $task['prompt_version'])
                        <a href="{{ route('admin.system.ai.prompts', ['prompt' => $task['prompt'], 'verzio' => $task['prompt_version']]) }}" class="text-charcoal">{{ $task['prompt'] }} · v{{ $task['prompt_version'] }}</a>
                    @elseif ($task['prompt'])
                        <span class="text-grey-600">{{ $task['prompt'] }} · {{ __('admin.ai.no_prompt_yet') }}</span>
                    @else
                        {{ __('admin.common.none') }}
                    @endif
                </td>
                <td class="px-3 py-2">
                    @if ($task['price'] !== null)
                        {{ __('admin.ai.price_text', [
                            'input' => number_format((float) ($task['price']['input'] ?? 0), 2, ',', ''),
                            'output' => number_format((float) ($task['price']['output'] ?? 0), 2, ',', ''),
                        ]) }}
                        @isset($task['price']['audio_minute'])
                            <span class="block">{{ __('admin.ai.audio_price_text', ['price' => number_format((float) $task['price']['audio_minute'], 4, ',', '')]) }}</span>
                        @endisset
                    @else
                        <x-ui.badge tone="yellow">{{ __('admin.ai.no_price') }}</x-ui.badge>
                    @endif
                </td>
                <td class="px-3 py-2 whitespace-nowrap">{{ $task['last_success'] ? \Illuminate\Support\Carbon::parse($task['last_success'])->format('Y.m.d. H:i') : __('admin.common.never') }}</td>
            </tr>
        @endforeach
    </x-admin.table>
</div>
