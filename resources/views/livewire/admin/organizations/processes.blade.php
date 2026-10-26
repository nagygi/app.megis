{{-- The organization's Folyamatok tab (11 · 11.6). --}}
<div class="flex flex-col gap-6" @if ($dictations !== []) wire:poll.2s="pollDictations" @endif>
    <x-ui.flash />

    <x-admin.page-header :title="__('admin.organizations.tabs.processes')" :spaced="false" />

    @if ($unclassified->isNotEmpty())
        {{-- Besorolásra vár (12 · 12.6) --}}
        <x-ui.card as="section" tone="paper" bordered padding="sm" class="flex flex-col gap-2" aria-labelledby="unclassified-title">
            <h2 id="unclassified-title" class="text-lg font-semibold">{{ __('admin.occasions.unclassified') }}</h2>
            <ul class="flex flex-col gap-2">
                @foreach ($unclassified as $material)
                    <x-ui.card as="li" bordered padding="sm" wire:key="unclassified-{{ $material->uuid }}" class="flex flex-col gap-3">
                        <p>
                            <a href="{{ route('admin.materials.show', $material) }}" class="font-semibold">{{ $material->is_situation ? __('processes.occasions.situation') : ($material->original_name ?? $material->source->label()) }}</a>
                            · {{ $material->user?->name ?? $material->creator?->name ?? '–' }}
                            · {{ $material->placed_at->format('Y.m.d.') }}
                            · {{ $material->status->label() }}
                        </p>
                        <x-form.inline as="form" wire:submit="classifyMaterial('{{ $material->uuid }}')">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <x-form.select name="classify.{{ $material->uuid }}.process_id" wire:model="classify.{{ $material->uuid }}.process_id" :label="__('admin.occasions.process')">
                                    <option value="">–</option>
                                    @foreach ($parents as $parent)
                                        <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                                    @endforeach
                                </x-form.select>
                                <x-form.input name="classify.{{ $material->uuid }}.date" type="date" wire:model="classify.{{ $material->uuid }}.date" :label="__('admin.occasions.date')" :max="today()->toDateString()" />
                            </div>
                            <x-slot:actions>
                                <x-ui.button type="submit" size="sm">{{ __('admin.occasions.classify') }}</x-ui.button>
                                <x-ui.button tone="outline" size="sm" wire:click="markOther('{{ $material->uuid }}')">{{ __('admin.occasions.other') }}</x-ui.button>
                            </x-slot:actions>
                        </x-form.inline>
                    </x-ui.card>
                @endforeach
            </ul>
        </x-ui.card>
    @endif

    {{-- Naptár --}}
    <section class="flex flex-col gap-2" aria-labelledby="calendar-rows">
        <h2 id="calendar-rows" class="text-lg font-semibold">{{ __('admin.processes.calendar') }}</h2>
        @forelse ($connections as $connection)
            <x-ui.card bordered padding="none" wire:key="connection-{{ $connection->id }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-3 text-sm">
                <span class="font-semibold">{{ $connection->providerLabel() }} · {{ $connection->user->name }}</span>
                <span>{{ __('admin.processes.connected_since', ['date' => $connection->connected_at->format('Y.m.d.')]) }}</span>
                @if ($connection->connectedBy)
                    <span>{{ __('admin.processes.connected_by', ['name' => $connection->connectedBy->name]) }}</span>
                @endif
                <span>{{ __('processes.list.events', ['count' => $connection->event_count]) }}</span>
                <span>{{ __('calendar.profile.metadata_until', ['date' => $connection->metadata_expires_at->format('Y.m.d.')]) }}</span>
                <x-ui.badge :tone="$connection->status === 'active' ? 'green' : ($connection->status === 'broken' ? 'coral' : 'muted')">{{ __('calendar.statuses.'.$connection->status) }}</x-ui.badge>
                @if ($connection->error)
                    <span class="text-coral-ink">{{ $connection->error }}</span>
                @endif
            </x-ui.card>
        @empty
            <p class="text-sm text-grey-700">{{ __('admin.processes.no_calendar') }}</p>
        @endforelse

        {{-- At the leader's request: their iCal link or exported file (09 · 9.2); a superadmin picks the member. --}}
        @if ($calendarUser)
            <x-ui.card as="section" bordered padding="sm" class="flex flex-col gap-4" aria-labelledby="calendar-load">
                <div class="flex flex-col gap-1">
                    <h3 id="calendar-load" class="font-semibold">{{ __('admin.processes.load_title', ['name' => $calendarUser->name]) }}</h3>
                    <p class="text-sm text-grey-700">{{ __('admin.processes.load_help') }}</p>
                </div>
                @if ($chooseCalendarUser)
                    <x-form.select name="calendarUserId" wire:model.live="calendarUserId" :label="__('admin.processes.load_for')" :help="__('admin.processes.load_for_help')" class="sm:max-w-sm">
                        @foreach ($members as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </x-form.select>
                @endif
                <x-form.checkbox name="leaderAsked" wire:model="leaderAsked" :label="__('admin.processes.leader_asked', ['name' => $calendarUser->name])" :help="__('admin.processes.leader_asked_help')" />
                <div class="grid gap-4 lg:grid-cols-2">
                    <form wire:submit="connectCalendarUrl" class="flex flex-col gap-3">
                        <x-form.input name="icsUrl" type="url" wire:model="icsUrl" :label="__('calendar.ics_url')" :help="__('calendar.ics_url_help')" inputmode="url" />
                        <div wire:loading wire:target="connectCalendarUrl" class="text-sm" role="status">{{ __('calendar.reading') }}</div>
                        <x-ui.button type="submit" size="sm" class="self-start">{{ __('admin.processes.load_url') }}</x-ui.button>
                    </form>
                    <form wire:submit="connectCalendarFile" class="flex flex-col gap-3">
                        <x-form.file name="icsFile" id="admin-ics-file" accept=".ics,text/calendar" wire:model="icsFile" :label="__('calendar.ics_file')" :help="__('calendar.ics_file_help', ['size' => $maxMegabytes])" />
                        <div wire:loading wire:target="connectCalendarFile,icsFile" class="text-sm" role="status">{{ __('calendar.reading') }}</div>
                        <x-ui.button type="submit" size="sm" class="self-start" wire:loading.attr="disabled" wire:target="icsFile">{{ __('admin.processes.load_file') }}</x-ui.button>
                    </form>
                </div>
            </x-ui.card>
        @else
            <p class="text-sm text-grey-700">{{ $chooseCalendarUser ? __('admin.processes.no_members') : __('admin.processes.no_running') }}</p>
        @endif
    </section>

    @foreach (['main' => __('admin.processes.list'), 'review' => __('admin.processes.review')] as $block => $title)
        <section class="flex flex-col gap-2" aria-labelledby="processes-{{ $block }}">
            <h2 id="processes-{{ $block }}" class="text-lg font-semibold">{{ $title }}</h2>
            <x-admin.table>
                <x-slot:head>
                    <x-admin.th>{{ __('processes.list.fields.name') }}</x-admin.th>
                    <x-admin.th>{{ __('processes.list.fields.parent') }}</x-admin.th>
                    <x-admin.th>{{ __('admin.processes.source') }}</x-admin.th>
                    <x-admin.th>{{ __('admin.processes.confidence') }}</x-admin.th>
                    <x-admin.th>{{ __('admin.processes.state') }}</x-admin.th>
                    <x-admin.th>{{ __('processes.sheet.responsible') }}</x-admin.th>
                    <x-admin.th>{{ __('admin.processes.occasions') }}</x-admin.th>
                    <x-admin.th><span class="sr-only">{{ __('admin.common.actions') }}</span></x-admin.th>
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
                                    <div class="sm:col-span-2 lg:col-span-5">
                                        <x-form.textarea name="edit.description" wire:model="edit.description" :label="__('processes.sheet.description')" rows="2" maxlength="2000" />
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @else
                        <tr wire:key="process-{{ $process->id }}">
                            <td class="px-3 py-2">
                                <a href="{{ route('admin.organizations.process-occasions', [$organization, $process]) }}" class="font-semibold">{{ $process->name }}</a>
                                @if ($process->description)
                                    <span class="block max-w-sm text-xs text-grey-700">{{ \Illuminate\Support\Str::limit($process->description, 120) }}</span>
                                @endif
                                @if ($process->renames->isNotEmpty())
                                    <span class="block text-xs text-grey-700">{{ __('processes.list.renamed_from', ['name' => $process->renames->last()->old_name]) }}</span>
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
                            <td class="px-3 py-2 text-right">
                                <x-ui.button tone="outline" size="sm" wire:click="startEdit({{ $process->id }})">{{ __('admin.common.edit') }}</x-ui.button>
                            </td>
                        </tr>
                    @endif
                @empty
                    <x-admin.empty-row :colspan="8">{{ $block === 'main' ? __('admin.processes.empty') : __('admin.processes.review_empty') }}</x-admin.empty-row>
                @endforelse
            </x-admin.table>
            <x-form.error name="edit.accepted" id="edit-accepted" />
        </section>
    @endforeach

    <x-processes.import :rows="$importRows" :parents="$parents" can-dictate :pending="isset($dictations['importText'])" bordered />

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card as="form" bordered padding="lg" wire:submit="addProcess" class="flex flex-col gap-4">
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
            <x-form.textarea name="newDescription" wire:model="newDescription" :label="__('processes.sheet.description')" rows="2" maxlength="2000" />
            <x-ui.button type="submit" tone="outline" icon="plus" class="self-start">{{ __('processes.list.add') }}</x-ui.button>
        </x-ui.card>

        <x-ui.card as="form" bordered padding="lg" wire:submit="saveRecommendation" class="flex flex-col gap-4">
            <h2 class="text-lg font-semibold">{{ __('admin.processes.recommendation') }}</h2>
            <x-form.textarea name="recommendation" wire:model="recommendation" :label="__('admin.processes.recommendation')" :help="__('admin.processes.recommendation_help')" rows="3" />
            <x-ui.button type="submit" class="self-start">{{ __('admin.common.save') }}</x-ui.button>
        </x-ui.card>
    </div>
</div>
