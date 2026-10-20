@php
    [$sortField, $sortDirection] = $this->currentSort();
    $usd = fn (?float $amount) => $amount === null ? '–' : '$'.number_format($amount, 4, ',', ' ');
    $count = fn (int $number) => number_format($number, 0, ',', ' ');
    $breakdownLabel = fn (string $by, string $key) => match ($by) {
        'task' => __('admin.ai.tasks.'.$key),
        'organization' => $key === \App\Services\Ai\AiUsageQuery::PUBLIC_MIRROR ? __('admin.ai.public_mirror') : ($key !== '' ? $key : __('admin.common.none')),
        'day' => \Illuminate\Support\Carbon::parse($key)->format('Y.m.d.'),
        default => $key,
    };
@endphp

<div class="flex flex-col gap-6">
    <x-admin.page-header :title="__('admin.ai.usage_title')">
        <x-slot:actions>
            <x-ui.button tone="outline" :href="$exportUrl">{{ __('admin.ai.export') }}</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Period (16 · 16.5) --}}
    <div class="flex flex-wrap items-end gap-3">
        <div class="flex flex-col gap-1.5">
            <label for="period" class="text-sm font-medium">{{ __('admin.ai.period') }}</label>
            <select id="period" wire:model.live="period" class="rounded-field border border-grey-350 bg-white px-3 py-2 text-base focus:border-charcoal">
                @foreach (\App\Services\Ai\AiUsageQuery::PERIODS as $option)
                    <option value="{{ $option }}">{{ __('admin.ai.periods.'.$option) }}</option>
                @endforeach
            </select>
        </div>
        @if ($period === 'custom')
            <x-form.input name="from" type="date" wire:model.live="from" :label="__('admin.ai.from')" />
            <x-form.input name="until" type="date" wire:model.live="until" :label="__('admin.ai.until')" />
        @endif
        <p class="pb-2 text-sm text-grey-600">{{ $start->format('Y.m.d.') }} – {{ $end->format('Y.m.d.') }}</p>
    </div>

    {{-- Totals --}}
    <div class="grid gap-4 sm:grid-cols-3">
        @foreach ([
            ['label' => __('admin.ai.calls'), 'value' => $count($summary['calls'])],
            ['label' => __('admin.ai.tokens'), 'value' => $count($summary['tokens'])],
            ['label' => __('admin.ai.cost'), 'value' => $usd($summary['cost'])],
        ] as $tile)
            <div class="rounded-card border border-grey-250 bg-white p-5">
                <p class="text-sm text-grey-600">{{ $tile['label'] }}</p>
                <p class="text-2xl font-semibold">{{ $tile['value'] }}</p>
            </div>
        @endforeach
    </div>
    @if ($summary['unpriced'] > 0)
        <p class="rounded-field bg-yellow-pale px-3 py-2 text-sm" role="note">{{ __('admin.ai.unpriced', ['count' => $summary['unpriced']]) }}</p>
    @endif

    {{-- Breakdowns --}}
    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($breakdowns as $by => $rows)
            <section class="flex flex-col gap-2" aria-labelledby="breakdown-{{ $by }}">
                <h2 id="breakdown-{{ $by }}" class="font-semibold">{{ __('admin.ai.by.'.$by) }}</h2>
                <x-admin.table>
                    <x-slot:head>
                        <x-admin.th>{{ __('admin.ai.by_columns.'.$by) }}</x-admin.th>
                        <x-admin.th>{{ __('admin.ai.calls') }}</x-admin.th>
                        <x-admin.th>{{ __('admin.ai.tokens') }}</x-admin.th>
                        <x-admin.th>{{ __('admin.ai.cost') }}</x-admin.th>
                    </x-slot:head>
                    @forelse ($rows as $row)
                        <tr wire:key="breakdown-{{ $by }}-{{ $loop->index }}">
                            <td class="px-3 py-2">{{ $breakdownLabel($by, $row['key']) }}</td>
                            <td class="px-3 py-2">{{ $count($row['calls']) }}</td>
                            <td class="px-3 py-2">{{ $count($row['tokens']) }}</td>
                            <td class="px-3 py-2">{{ $usd($row['cost']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-4 text-center text-grey-600">{{ __('admin.ai.no_calls') }}</td></tr>
                    @endforelse
                </x-admin.table>
            </section>
        @endforeach
    </div>

    {{-- Calls --}}
    <section class="flex flex-col gap-3" aria-labelledby="calls-title">
        <h2 id="calls-title" class="text-lg font-semibold">{{ __('admin.ai.calls_title') }}</h2>

        <div class="flex flex-wrap items-end gap-3">
            <x-admin.filter name="task" :label="__('admin.ai.task')" :options="$taskOptions" />
            <x-admin.filter name="model" :label="__('admin.ai.model')" :options="$modelOptions" />
            <x-admin.filter name="organization" :label="__('admin.ai.organization')" :options="$organizationOptions" />
            <x-admin.filter name="status" :label="__('admin.ai.status')" :options="$statusOptions" />
        </div>

        <x-admin.table :paginator="$calls">
            <x-slot:head>
                <x-admin.th field="time" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.ai.time') }}</x-admin.th>
                <x-admin.th field="task" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.ai.task') }}</x-admin.th>
                <x-admin.th field="model" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.ai.model') }}</x-admin.th>
                <x-admin.th field="organization" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.ai.organization') }}</x-admin.th>
                <x-admin.th field="tokens" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.ai.tokens') }}</x-admin.th>
                <x-admin.th field="cost" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.ai.cost') }}</x-admin.th>
                <x-admin.th field="status" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.ai.status') }}</x-admin.th>
                <x-admin.th field="duration" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.ai.duration') }}</x-admin.th>
            </x-slot:head>

            @forelse ($calls as $call)
                <tr wire:key="call-{{ $call->id }}">
                    <td class="px-3 py-2 whitespace-nowrap">
                        <button type="button" wire:click="toggleCall({{ $call->id }})" class="font-semibold text-charcoal underline" aria-expanded="{{ $openCallId === $call->id ? 'true' : 'false' }}">
                            {{ $call->created_at->format('Y.m.d. H:i:s') }}
                        </button>
                    </td>
                    <td class="px-3 py-2">{{ __('admin.ai.tasks.'.$call->task) }}</td>
                    <td class="px-3 py-2">{{ $call->model_version ?? $call->model }}</td>
                    <td class="px-3 py-2">{{ $call->contextLabel() }}</td>
                    <td class="px-3 py-2">{{ $count($call->totalTokens()) }}</td>
                    <td class="px-3 py-2">{{ $usd($call->cost_usd) }}</td>
                    <td class="px-3 py-2"><x-ui.badge :tone="$call->status === \App\Enums\AiCallStatus::Succeeded ? 'green' : 'coral'">{{ $call->status->label() }}</x-ui.badge></td>
                    <td class="px-3 py-2 whitespace-nowrap">{{ number_format($call->duration_ms / 1000, 1, ',', '') }} s</td>
                </tr>
                @if ($openCallId === $call->id)
                    <tr wire:key="call-detail-{{ $call->id }}" class="bg-paper">
                        <td colspan="8" class="px-3 py-3">
                            <dl class="grid gap-2 text-sm md:grid-cols-[12rem_1fr]">
                                <dt class="font-semibold">{{ __('admin.ai.prompt_version') }}</dt>
                                <dd>
                                    @if ($call->prompt)
                                        <a href="{{ route('admin.system.ai.prompts', ['prompt' => $call->prompt, 'verzio' => $call->prompt_version]) }}" class="text-charcoal">{{ $call->prompt }} · v{{ $call->prompt_version }}</a>
                                    @else
                                        {{ __('admin.common.none') }}
                                    @endif
                                </dd>
                                <dt class="font-semibold">{{ __('admin.ai.provider') }}</dt>
                                <dd>{{ $call->provider }} · {{ $call->model }}</dd>
                                <dt class="font-semibold">{{ __('admin.ai.token_detail') }}</dt>
                                <dd>{{ __('admin.ai.token_detail_text', ['input' => $count($call->input_tokens), 'output' => $count($call->output_tokens), 'cache_read' => $count($call->cache_read_tokens), 'cache_write' => $count($call->cache_write_tokens)]) }}</dd>
                                @if ($call->audio_minutes !== null)
                                    <dt class="font-semibold">{{ __('admin.ai.audio_minutes') }}</dt>
                                    <dd>{{ number_format($call->audio_minutes, 2, ',', '') }}</dd>
                                @endif
                                <dt class="font-semibold">{{ __('admin.ai.result') }}</dt>
                                <dd>{{ $call->subject_type ? $call->subject_type.' #'.$call->subject_id : __('admin.common.none') }}</dd>
                                @if ($call->error)
                                    <dt class="font-semibold">{{ __('admin.ai.error') }}</dt>
                                    <dd class="break-words">{{ $call->error }}</dd>
                                @endif
                            </dl>
                        </td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="8" class="px-3 py-6 text-center text-grey-600">{{ __('admin.ai.no_calls') }}</td></tr>
            @endforelse
        </x-admin.table>
    </section>
</div>
