{{-- Rows of one block of a diagnosis, with the row editor under the edited row (04 · 4.4). --}}
<x-admin.table>
    <x-slot:head>
        <x-admin.th>{{ $removable ? __('admin.diagnoses.values_block') : __('admin.diagnoses.tree_block') }}</x-admin.th>
        <x-admin.th>{{ __('admin.diagnoses.score') }}</x-admin.th>
        <x-admin.th>{{ __('admin.diagnoses.experience') }}</x-admin.th>
        <x-admin.th>{{ __('admin.diagnoses.gap') }}</x-admin.th>
        <x-admin.th>{{ __('admin.diagnoses.reasoning') }}</x-admin.th>
        <x-admin.th><span class="sr-only">{{ __('admin.common.actions') }}</span></x-admin.th>
    </x-slot:head>

    @forelse ($rows as $row)
        <tr wire:key="row-{{ $row->id }}" @class(['bg-paper' => $editingRowId === $row->id])>
            <td class="px-3 py-2">
                <span class="font-semibold">{{ $row->label() }}</span>
                <span class="flex flex-wrap gap-1 pt-1">
                    @if ($row->valueVersion)
                        <x-ui.badge tone="muted">{{ $row->valueVersion->label() }}</x-ui.badge>
                    @endif
                    @if ($row->manually_modified)
                        <x-ui.badge tone="yellow">{{ __('admin.diagnoses.manually_modified') }}</x-ui.badge>
                    @endif
                    @foreach ($row->aggregation_stats['warnings'] ?? [] as $warning)
                        <x-ui.badge tone="coral">{{ $warning === 'few_users'
                            ? __('admin.diagnoses.aggregation.few_users', ['count' => $row->aggregation_stats['n']])
                            : __('admin.diagnoses.aggregation.divided') }}</x-ui.badge>
                    @endforeach
                </span>
            </td>
            <td class="px-3 py-2 text-base font-semibold">
                {{ $row->scoreLabel() }}
                @if ($row->isAggregated())
                    <span class="block text-xs font-normal text-grey-600">n = {{ $row->aggregation_stats['n'] }} · {{ __('admin.diagnoses.aggregation.deviation') }}: {{ $row->aggregation_stats['score_deviation'] === null ? '–' : number_format($row->aggregation_stats['score_deviation'], 2, ',', '') }}</span>
                @endif
            </td>
            <td class="px-3 py-2">{{ $row->experienceLabel() }}</td>
            <td class="px-3 py-2">{{ $row->gapLabel() }}</td>
            <td class="max-w-xs px-3 py-2 text-grey-700">{{ \Illuminate\Support\Str::limit((string) $row->reasoning, 80) }}</td>
            <td class="px-3 py-2 text-right">
                @if ($isEditable && $editingRowId !== $row->id)
                    <div class="flex justify-end gap-2">
                        <x-ui.button tone="outline" size="sm" wire:click="editRow({{ $row->id }})" aria-label="{{ __('admin.diagnoses.edit_row') }}: {{ $row->label() }}">{{ __('admin.diagnoses.edit_row') }}</x-ui.button>
                        @if ($removable)
                            <x-ui.button tone="outline" size="sm" wire:click="removeRow({{ $row->id }})" wire:confirm="{{ __('admin.diagnoses.confirm_remove_row') }}" aria-label="{{ __('admin.diagnoses.remove_row') }}: {{ $row->label() }}">{{ __('admin.diagnoses.remove_row') }}</x-ui.button>
                        @endif
                    </div>
                @endif
            </td>
        </tr>

        @if ($editingRowId === $row->id)
            <tr wire:key="row-editor-{{ $row->id }}" class="bg-paper">
                <td colspan="6" class="px-3 pb-4">
                    <form wire:submit="saveRow" class="grid gap-4 md:grid-cols-2">
                        @php($scoreRequired = $removable && ! $diagnosis->type->isExperience())
                        <x-form.select name="rowScore" wire:model="rowScore" :label="__('admin.diagnoses.score')" :help="__('admin.diagnoses.score_help')" :required="$scoreRequired">
                            <option value="">{{ __('admin.diagnoses.score_none') }}</option>
                            @foreach (range(1, 6) as $number)
                                <option value="{{ $number }}">{{ $number }}</option>
                            @endforeach
                            @if (str_contains($rowScore, '.'))
                                <option value="{{ $rowScore }}">{{ str_replace('.', ',', $rowScore) }}</option>
                            @endif
                            <option value="{{ \App\Services\Diagnoses\DiagnosisService::NO_TRACE }}">{{ __('admin.diagnoses.score_no_trace') }}</option>
                        </x-form.select>

                        <x-form.select name="rowExperience" wire:model="rowExperience" :label="__('admin.diagnoses.experience')" :help="__('admin.diagnoses.experience_help')">
                            <option value="">{{ __('admin.diagnoses.experience_none') }}</option>
                            @foreach (range(1, 6) as $number)
                                <option value="{{ $number }}">{{ $number }}</option>
                            @endforeach
                            @if (str_contains($rowExperience, '.'))
                                <option value="{{ $rowExperience }}">{{ str_replace('.', ',', $rowExperience) }}</option>
                            @endif
                            <option value="{{ \App\Services\Diagnoses\DiagnosisService::DONT_KNOW }}">{{ __('admin.diagnoses.experience_dont_know') }}</option>
                        </x-form.select>

                        <x-form.textarea name="rowMetExample" wire:model="rowMetExample" :label="__('admin.diagnoses.experience_met_example')" rows="2" />
                        <x-form.textarea name="rowUnmetExample" wire:model="rowUnmetExample" :label="__('admin.diagnoses.experience_unmet_example')" rows="2" />
                        <x-form.textarea name="rowReasoning" wire:model="rowReasoning" :label="__('admin.diagnoses.reasoning')" rows="2" />
                        <x-form.input name="rowAssignedOn" type="date" wire:model="rowAssignedOn" :label="__('admin.diagnoses.assigned_on')" required />

                        <div class="flex flex-wrap gap-3 md:col-span-2">
                            <x-ui.button type="submit" size="sm">{{ __('admin.common.save') }}</x-ui.button>
                            <x-ui.button tone="outline" size="sm" wire:click="cancelEditing">{{ __('admin.common.cancel') }}</x-ui.button>
                        </div>
                    </form>
                </td>
            </tr>
        @endif
    @empty
        <tr><td colspan="6" class="px-3 py-6 text-center text-grey-600">{{ __('admin.diagnoses.no_values') }}</td></tr>
    @endforelse
</x-admin.table>
