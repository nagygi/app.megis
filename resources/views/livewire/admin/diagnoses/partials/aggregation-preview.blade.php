{{-- Preview of the aggregated rows: n, mean, spread and warnings (04 · 4.5). --}}
@php($decimal = fn (?float $number, int $decimals = 1) => $number === null ? '–' : number_format($number, $decimals, ',', ''))

<x-admin.table>
    <x-slot:head>
        <x-admin.th>{{ __('admin.diagnoses.values_block') }}</x-admin.th>
        <x-admin.th>{{ __('admin.diagnoses.aggregation.n') }}</x-admin.th>
        <x-admin.th>{{ __('admin.diagnoses.score') }} · {{ __('admin.diagnoses.aggregation.mean') }}</x-admin.th>
        <x-admin.th>{{ __('admin.diagnoses.aggregation.deviation') }}</x-admin.th>
        <x-admin.th>{{ __('admin.diagnoses.experience') }} · {{ __('admin.diagnoses.aggregation.mean') }}</x-admin.th>
        <x-admin.th>{{ __('admin.diagnoses.aggregation.warnings') }}</x-admin.th>
    </x-slot:head>

    @forelse ($rows as $row)
        <tr wire:key="preview-{{ $row['kind'] }}-{{ $row['value_id'] ?? $row['tree_element'] }}">
            <td class="px-3 py-2 font-semibold">{{ $row['name'] }}</td>
            <td class="px-3 py-2">{{ $row['n'] }}</td>
            <td class="px-3 py-2">{{ $row['score_state'] === 'no_trace' ? '–' : $decimal($row['score']['mean']) }}</td>
            <td class="px-3 py-2">{{ $decimal($row['score']['deviation'], 2) }}</td>
            <td class="px-3 py-2">{{ $row['experience_state'] === 'dont_know' ? __('admin.diagnoses.experience_dont_know') : $decimal($row['experience']['mean']) }}</td>
            <td class="px-3 py-2">
                <span class="flex flex-wrap gap-1">
                    @foreach ($row['warnings'] as $warning)
                        <x-ui.badge tone="coral">{{ $warning === 'few_users'
                            ? __('admin.diagnoses.aggregation.few_users', ['count' => $row['n']])
                            : __('admin.diagnoses.aggregation.divided') }}</x-ui.badge>
                    @endforeach
                </span>
            </td>
        </tr>
    @empty
        <x-admin.empty-row :colspan="6">{{ __('admin.diagnoses.aggregation.nothing') }}</x-admin.empty-row>
    @endforelse
</x-admin.table>
