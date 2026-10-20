{{--
    Rows of one block of a diagnosis, with the row editor under the edited row (04 · 4.4).
    A calculated row (13, 18) is written over with a reason; the automatic value stays next to it.
--}}
@php($title ??= $removable ? __('admin.diagnoses.values_block') : __('admin.diagnoses.tree_block'))
<x-admin.table>
    <x-slot:head>
        <x-admin.th>{{ $title }}</x-admin.th>
        <x-admin.th>{{ __('admin.diagnoses.score') }}</x-admin.th>
        <x-admin.th>{{ __('admin.diagnoses.experience') }}</x-admin.th>
        <x-admin.th>{{ __('admin.diagnoses.gap') }}</x-admin.th>
        <x-admin.th>{{ __('admin.diagnoses.reasoning') }}</x-admin.th>
        <x-admin.th><span class="sr-only">{{ __('admin.common.actions') }}</span></x-admin.th>
    </x-slot:head>

    @forelse ($rows as $row)
        @php($stats = $row->aggregation_stats ?? [])
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
                    @if ($row->overridden_at)
                        <x-ui.badge tone="yellow">{{ __('admin.diagnoses.override.badge') }}</x-ui.badge>
                    @endif
                    @if ($stats['insufficient'] ?? false)
                        <x-ui.badge tone="coral">{{ __('admin.diagnoses.process_few', ['count' => $stats['n'] ?? 0]) }}</x-ui.badge>
                    @endif
                    @foreach ($stats['warnings'] ?? [] as $warning)
                        <x-ui.badge tone="coral">{{ $warning === 'few_users'
                            ? __('admin.diagnoses.aggregation.few_users', ['count' => $stats['n']])
                            : __('admin.diagnoses.aggregation.divided') }}</x-ui.badge>
                    @endforeach
                </span>
                @if ($row->overridden_at)
                    <span class="mt-1 block text-xs text-grey-700">
                        {{ __('admin.diagnoses.override.by', ['name' => $row->overrider?->name ?? '–', 'date' => $row->overridden_at->format('Y.m.d. H:i')]) }} · {{ $row->override_reason }}
                        @if ($row->differsFromAutomatic())
                            · <strong>{{ __('admin.diagnoses.override.automatic', ['value' => $row->automatic_score_state === \App\Enums\ScoreState::Scored ? number_format((float) $row->automatic_score, 1, ',', '') : '–']) }}</strong>
                        @endif
                    </span>
                @endif
                @if (isset($stats['leader']) || isset($stats['peers']))
                    {{-- The experience of the leader and of the peers, apart (13 · 13.3, 18 · 18.4) --}}
                    <span class="mt-1 block text-xs text-grey-700">
                        {{ __('admin.diagnoses.experience_split', [
                            'leader' => ($stats['leader'] ?? null) === null ? '–' : number_format($stats['leader'], 1, ',', ''),
                            'peers' => ($stats['peers'] ?? null) === null ? '–' : number_format($stats['peers'], 1, ',', ''),
                            'count' => $stats['peer_count'] ?? 0,
                        ]) }}
                        @if (($stats['situations'] ?? null) !== null)
                            · {{ __('admin.diagnoses.situations_experience', ['value' => number_format($stats['situations'], 1, ',', '')]) }}
                        @endif
                    </span>
                @endif
                @if (($row->quotes ?? []) !== [])
                    {{-- The quotes behind an automatic row, read-only (06 · 6.5) --}}
                    <ul class="mt-2 flex flex-col gap-1 text-sm text-grey-700">
                        @foreach ($row->quotes as $quote)
                            <li>„{{ $quote['quote'] }}”</li>
                        @endforeach
                    </ul>
                @endif
            </td>
            <td class="px-3 py-2 text-base font-semibold">
                {{ $row->scoreLabel() }}
                @if (array_key_exists('n', $stats))
                    @php($deviation = $stats['score_deviation'] ?? $stats['deviation'] ?? null)
                    <span class="block text-xs font-normal text-grey-700">n ={{ $stats['n'] }}@if (array_key_exists('score_deviation', $stats) || array_key_exists('deviation', $stats)) · {{ __('admin.diagnoses.aggregation.deviation') }}: {{ $deviation === null ? '–' : number_format($deviation, 2, ',', '') }}@endif</span>
                @endif
            </td>
            <td class="px-3 py-2">{{ $row->experienceLabel() }}</td>
            <td class="px-3 py-2">{{ $row->gapLabel() }}</td>
            <td class="max-w-xs px-3 py-2 text-grey-700">{{ \Illuminate\Support\Str::limit((string) $row->reasoning, 80) }}</td>
            <td class="px-3 py-2 text-right">
                @if ($isEditable && $editingRowId !== $row->id)
                    {{-- A calculated row is written over, the others edited; the accessible name starts with the visible label. --}}
                    @php($editLabel = $row->automatic_score_state !== null ? __('admin.diagnoses.override.edit') : __('admin.diagnoses.edit_row'))
                    @php($rowRemovable = $removable && ! $isCalculated)
                    {{-- Editing, the row's main action, stays a button; resetting and removing wait in the menu. --}}
                    <div class="flex items-center justify-end gap-1">
                        <x-ui.button tone="outline" size="sm" wire:click="editRow({{ $row->id }})" aria-label="{{ $editLabel }}: {{ $row->label() }}">{{ $editLabel }}</x-ui.button>
                        @if ($row->overridden_at || $rowRemovable)
                            <x-admin.row-actions :label="__('admin.common.row_actions', ['name' => $row->label()])">
                                @if ($row->overridden_at)
                                    {{-- The manual value and its reason are lost. --}}
                                    <x-admin.row-action
                                        tone="danger"
                                        wire:click="resetRow({{ $row->id }})"
                                        wire:confirm="{{ __('admin.diagnoses.override.confirm_reset') }}"
                                        data-confirm-tone="danger"
                                        :data-confirm-label="__('admin.diagnoses.override.reset')"
                                        aria-label="{{ __('admin.diagnoses.override.reset') }}: {{ $row->label() }}"
                                    >{{ __('admin.diagnoses.override.reset') }}</x-admin.row-action>
                                @endif
                                @if ($rowRemovable)
                                    <x-admin.row-action
                                        tone="danger"
                                        wire:click="removeRow({{ $row->id }})"
                                        wire:confirm="{{ __('admin.diagnoses.confirm_remove_row') }}"
                                        data-confirm-tone="danger"
                                        :data-confirm-label="__('admin.diagnoses.remove_row')"
                                        aria-label="{{ __('admin.diagnoses.remove_row') }}: {{ $row->label() }}"
                                    >{{ __('admin.diagnoses.remove_row') }}</x-admin.row-action>
                                @endif
                            </x-admin.row-actions>
                        @endif
                    </div>
                @endif
            </td>
        </tr>

        @if ($editingRowId === $row->id)
            <tr wire:key="row-editor-{{ $row->id }}" class="bg-paper">
                <td colspan="6" class="px-3 pb-4">
                    <form wire:submit="saveRow" class="grid gap-4 md:grid-cols-2">
                        @php($overriding = $row->automatic_score_state !== null)
                        @php($scoreRequired = $overriding || ($removable && ! $diagnosis->type->isExperience()))
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

                        @if ($overriding)
                            <x-form.textarea name="rowOverrideReason" wire:model="rowOverrideReason" :label="__('admin.diagnoses.override.reason')" :help="__('admin.diagnoses.override.reason_help')" rows="2" required />
                        @elseif (! $isCalculated)
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
                        @endif

                        @unless ($overriding)
                            <x-form.textarea name="rowMetExample" wire:model="rowMetExample" :label="__('admin.diagnoses.experience_met_example')" rows="2" />
                            <x-form.textarea name="rowUnmetExample" wire:model="rowUnmetExample" :label="__('admin.diagnoses.experience_unmet_example')" rows="2" />
                        @endunless
                        <x-form.textarea name="rowReasoning" wire:model="rowReasoning" :label="__('admin.diagnoses.reasoning')" rows="2" />
                        @unless ($overriding)
                            <x-form.input name="rowAssignedOn" type="date" wire:model="rowAssignedOn" :label="__('admin.diagnoses.assigned_on')" required />
                        @endunless

                        <div class="flex flex-wrap gap-3 md:col-span-2">
                            <x-ui.button type="submit" size="sm">{{ __('admin.common.save') }}</x-ui.button>
                            <x-ui.button tone="outline" size="sm" wire:click="cancelEditing">{{ __('admin.common.cancel') }}</x-ui.button>
                        </div>
                    </form>
                </td>
            </tr>
        @endif
    @empty
        <x-admin.empty-row :colspan="6">{{ __('admin.diagnoses.no_values') }}</x-admin.empty-row>
    @endforelse
</x-admin.table>
