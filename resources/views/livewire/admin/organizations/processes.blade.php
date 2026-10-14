{{-- The organization's Folyamatok tab (11 · 11.6). --}}
<div class="flex flex-col gap-6">
    <x-ui.flash />

    <x-admin.page-header :title="__('admin.organizations.tabs.processes')" class="mb-0" />

    @if ($unclassified->isNotEmpty())
        {{-- Besorolásra vár (12 · 12.6) --}}
        <section class="flex flex-col gap-2 rounded-card bg-paper p-4" aria-labelledby="unclassified-title">
            <h2 id="unclassified-title" class="text-lg font-semibold">{{ __('admin.occasions.unclassified') }}</h2>
            <ul class="flex flex-col gap-2">
                @foreach ($unclassified as $material)
                    <li wire:key="unclassified-{{ $material->uuid }}" class="flex flex-col gap-3 rounded-card border border-grey-250 bg-white p-4">
                        <p>
                            <a href="{{ route('admin.materials.show', $material) }}" class="font-semibold">{{ $material->is_situation ? __('processes.occasions.situation') : ($material->original_name ?? $material->source->label()) }}</a>
                            · {{ $material->user?->name ?? $material->creator?->name ?? '–' }}
                            · {{ $material->placed_at->format('Y.m.d.') }}
                            · {{ $material->status->label() }}
                        </p>
                        <form wire:submit="classifyMaterial('{{ $material->uuid }}')" class="flex flex-wrap items-end gap-3">
                            <x-form.select name="classify.{{ $material->uuid }}.process_id" wire:model="classify.{{ $material->uuid }}.process_id" :label="__('admin.occasions.process')">
                                <option value="">–</option>
                                @foreach ($parents as $parent)
                                    <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                                @endforeach
                            </x-form.select>
                            <x-form.input name="classify.{{ $material->uuid }}.date" type="date" wire:model="classify.{{ $material->uuid }}.date" :label="__('admin.occasions.date')" :max="today()->toDateString()" />
                            <x-ui.button type="submit" size="sm">{{ __('admin.occasions.classify') }}</x-ui.button>
                            <x-ui.button tone="outline" size="sm" wire:click="markOther('{{ $material->uuid }}')">{{ __('admin.occasions.other') }}</x-ui.button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Naptár --}}
    <section class="flex flex-col gap-2" aria-labelledby="calendar-rows">
        <h2 id="calendar-rows" class="text-lg font-semibold">{{ __('admin.processes.calendar') }}</h2>
        @forelse ($connections as $connection)
            <div wire:key="connection-{{ $connection->id }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 rounded-card border border-grey-250 bg-white px-4 py-3 text-sm">
                <span class="font-semibold">{{ $connection->providerLabel() }} · {{ $connection->user->name }}</span>
                <span>{{ __('admin.processes.connected_since', ['date' => $connection->connected_at->format('Y.m.d.')]) }}</span>
                <span>{{ __('processes.list.events', ['count' => $connection->event_count]) }}</span>
                <span>{{ __('calendar.profile.metadata_until', ['date' => $connection->metadata_expires_at->format('Y.m.d.')]) }}</span>
                <x-ui.badge :tone="$connection->status === 'active' ? 'green' : ($connection->status === 'broken' ? 'coral' : 'muted')">{{ __('calendar.statuses.'.$connection->status) }}</x-ui.badge>
                @if ($connection->error)
                    <span class="text-coral-dark">{{ $connection->error }}</span>
                @endif
            </div>
        @empty
            <p class="text-sm text-grey-600">{{ __('admin.processes.no_calendar') }}</p>
        @endforelse
    </section>

    @foreach (['main' => __('admin.processes.list'), 'review' => __('admin.processes.review')] as $block => $title)
        <section class="flex flex-col gap-2" aria-labelledby="processes-{{ $block }}">
            <h2 id="processes-{{ $block }}" class="text-lg font-semibold">{{ $title }}</h2>
            <x-admin.table>
                <x-slot:head>
                    <th scope="col" class="px-3 py-2">{{ __('processes.list.fields.name') }}</th>
                    <th scope="col" class="px-3 py-2">{{ __('processes.list.fields.parent') }}</th>
                    <th scope="col" class="px-3 py-2">{{ __('admin.processes.source') }}</th>
                    <th scope="col" class="px-3 py-2">{{ __('admin.processes.confidence') }}</th>
                    <th scope="col" class="px-3 py-2">{{ __('admin.processes.state') }}</th>
                    <th scope="col" class="px-3 py-2">{{ __('processes.sheet.responsible') }}</th>
                    <th scope="col" class="px-3 py-2">{{ __('admin.processes.occasions') }}</th>
                    <th scope="col" class="px-3 py-2"><span class="sr-only">{{ __('admin.common.actions') }}</span></th>
                </x-slot:head>

                @forelse ($$block as $process)
                    @if ($editingId === $process->id)
                        <tr wire:key="process-{{ $process->id }}">
                            <td colspan="8" class="px-3 py-3">
                                <form wire:submit="saveEdit" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                                    <x-form.input name="edit.name" wire:model="edit.name" :label="__('processes.list.fields.name')" />
                                    <x-form.select name="edit.kind" wire:model="edit.kind" :label="__('admin.processes.kind')">
                                        @foreach (\App\Enums\ProcessKind::cases() as $kind)
                                            <option value="{{ $kind->value }}">{{ $kind->label() }}</option>
                                        @endforeach
                                    </x-form.select>
                                    <x-form.select name="edit.responsible_user_id" wire:model="edit.responsible_user_id" :label="__('processes.sheet.responsible')">
                                        <option value="">{{ __('processes.sheet.responsible_none') }}</option>
                                        @foreach ($members as $member)
                                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                                        @endforeach
                                    </x-form.select>
                                    <x-form.select name="edit.parent_id" wire:model="edit.parent_id" :label="__('processes.list.fields.parent')">
                                        <option value="">{{ __('processes.list.parent_none') }}</option>
                                        @foreach ($parents as $parent)
                                            @continue($parent->id === $process->id)
                                            <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                                        @endforeach
                                    </x-form.select>
                                    <div class="flex flex-col justify-end gap-2">
                                        <x-form.checkbox name="edit.accepted" wire:model="edit.accepted" :label="__('processes.list.accepted')" />
                                        <div class="flex gap-2">
                                            <x-ui.button type="submit" size="sm">{{ __('admin.common.save') }}</x-ui.button>
                                            <x-ui.button tone="outline" size="sm" wire:click="$set('editingId', null)">{{ __('admin.common.cancel') }}</x-ui.button>
                                        </div>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @else
                        <tr wire:key="process-{{ $process->id }}">
                            <td class="px-3 py-2">
                                <a href="{{ route('admin.organizations.process-occasions', [$organization, $process]) }}" class="font-semibold">{{ $process->name }}</a>
                                @if ($process->renames->isNotEmpty())
                                    <span class="block text-xs text-grey-600">{{ __('processes.list.renamed_from', ['name' => $process->renames->last()->old_name]) }}</span>
                                @endif
                                @if ($process->breakpoint_candidate)
                                    <x-ui.badge tone="coral">{{ __('processes.list.breakpoint') }}</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $process->parent?->name ?? '–' }}</td>
                            <td class="px-3 py-2">{{ $process->source->label() }}</td>
                            <td class="px-3 py-2">{{ $process->confidence->label() }}@if ($process->unclear) · {{ __('admin.processes.unclear') }}@endif</td>
                            <td class="px-3 py-2">{{ $process->kind->label() }}@if ($process->isAccepted()) · {{ __('processes.list.accepted') }}@endif</td>
                            <td class="px-3 py-2">{{ $process->responsible?->name ?? '–' }}</td>
                            <td class="px-3 py-2">{{ $process->occasions_count }}</td>
                            <td class="px-3 py-2 text-right"><button type="button" wire:click="startEdit({{ $process->id }})" class="font-semibold underline">{{ __('admin.common.edit') }}</button></td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="8" class="px-3 py-6 text-center text-grey-600">{{ __('admin.common.empty') }}</td></tr>
                @endforelse
            </x-admin.table>
            <x-form.error name="edit.accepted" id="edit-accepted" />
        </section>
    @endforeach

    <div class="grid gap-6 lg:grid-cols-2">
        <form wire:submit="addProcess" class="flex flex-col gap-4 rounded-card border border-grey-250 bg-white p-6">
            <h2 class="text-lg font-semibold">{{ __('processes.list.add_title') }}</h2>
            <x-form.input name="newName" wire:model="newName" :label="__('processes.list.fields.name')" required />
            <x-form.select name="newRhythm" wire:model="newRhythm" :label="__('processes.list.fields.rhythm')">
                <option value="">–</option>
                @foreach (\App\Enums\ProcessRhythm::cases() as $rhythm)
                    <option value="{{ $rhythm->value }}">{{ $rhythm->label() }}</option>
                @endforeach
            </x-form.select>
            <x-form.select name="newParentId" wire:model="newParentId" :label="__('processes.list.fields.parent')">
                <option value="">{{ __('processes.list.parent_none') }}</option>
                @foreach ($parents as $parent)
                    <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                @endforeach
            </x-form.select>
            <x-ui.button type="submit" tone="outline" class="self-start">{{ __('processes.list.add') }}</x-ui.button>
        </form>

        <form wire:submit="saveRecommendation" class="flex flex-col gap-4 rounded-card border border-grey-250 bg-white p-6">
            <h2 class="text-lg font-semibold">{{ __('admin.processes.recommendation') }}</h2>
            <x-form.textarea name="recommendation" wire:model="recommendation" :label="__('admin.processes.recommendation')" :help="__('admin.processes.recommendation_help')" rows="3" />
            <x-ui.button type="submit" class="self-start">{{ __('admin.common.save') }}</x-ui.button>
        </form>
    </div>
</div>
