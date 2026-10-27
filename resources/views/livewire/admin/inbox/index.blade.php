@php
    [$sortField, $sortDirection] = $this->currentSort();
@endphp

<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="$title" />

    <x-admin.list-toolbar>
        <x-admin.filter name="state" :label="__('admin.inbox.state')" :all-label="__('admin.inbox.open')" :options="['closed' => __('admin.inbox.closed')]" />
        <x-admin.filter name="type" :label="__('admin.inbox.type')" :options="$types" />
        <x-admin.filter name="organization" :label="__('admin.inbox.organization')" :options="$organizations" />
        <x-admin.filter name="contact" :label="__('admin.inbox.contact')" :all-label="__('admin.inbox.contact_me')" :options="$contacts" />
    </x-admin.list-toolbar>

    @if ($items->isEmpty() && ! $showClosed)
        <x-ui.empty-state symbol="tree-of-life" :title="__('admin.inbox.empty')" class="border border-grey-250" />
    @else
        <x-admin.table :paginator="$items">
            <x-slot:head>
                <x-admin.th field="type" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.inbox.type') }}</x-admin.th>
                <x-admin.th field="organization" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.inbox.diagnosis') }}</x-admin.th>
                <x-admin.th>{{ __('admin.inbox.summary') }}</x-admin.th>
                <x-admin.th field="age" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.inbox.age') }}</x-admin.th>
                @if ($showClosed)
                    <x-admin.th field="closed" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.inbox.closed_by') }}</x-admin.th>
                @else
                    <x-admin.th><span class="sr-only">{{ __('admin.common.actions') }}</span></x-admin.th>
                @endif
            </x-slot:head>

            @forelse ($items as $item)
                <tr wire:key="inbox-{{ $item->id }}">
                    <td class="px-3 py-2"><x-ui.badge>{{ $item->type->label() }}</x-ui.badge></td>
                    <td class="px-3 py-2">
                        @if ($item->diagnosis)
                            <a href="{{ route('admin.diagnoses.show', $item->diagnosis) }}" class="font-semibold text-charcoal">{{ $item->organization?->name }}@if ($item->diagnosis->team) · {{ $item->diagnosis->team->name }}@endif</a>
                        @else
                            {{ $item->organization?->name ?? __('admin.common.none') }}
                        @endif
                    </td>
                    <td class="px-3 py-2">
                        {{ $item->summary }}
                        {{-- The customer it is about opens their datasheet (33 · 33.1). --}}
                        <x-admin.user-link :user="$item->subjectUser()" class="mt-0.5 block text-sm" />
                    </td>
                    <td class="px-3 py-2 whitespace-nowrap text-grey-700" title="{{ $item->created_at->format('Y.m.d. H:i') }}">{{ $item->created_at->diffForHumans() }}</td>
                    @if ($showClosed)
                        <td class="px-3 py-2 whitespace-nowrap text-grey-700">
                            {{ $item->closed_at->format('Y.m.d. H:i') }}
                            <span class="block text-sm">{{ $item->closer?->name ?? __('admin.inbox.closed_automatically') }}</span>
                        </td>
                    @else
                        <td class="px-3 py-2 text-right">
                            @php
                                $viewUrl = $this->viewUrl($item);
                                // The close button says the same to everyone: its accessible name starts with its label.
                                $closeLabel = $item->type === \App\Enums\InboxItemType::MirrorBooking ? __('admin.inbox.done') : __('admin.inbox.close');
                                $classifies = $item->type === \App\Enums\InboxItemType::Classification && $item->subject instanceof \App\Models\Material;
                                $retries = $item->type === \App\Enums\InboxItemType::ProcessingError && ($item->subject instanceof \App\Models\Material || $item->subject instanceof \App\Models\MainDiagnosis);
                                $canReply = $item->type === \App\Enums\InboxItemType::Feedback && $item->subject instanceof \App\Models\ReportFeedback && $item->subject->user;
                                $replying = $canReply && $replyingTo === $item->id;
                                // A row, a decision (22 · 22.2): its main action stays a button, the rest
                                // and closing go in the row menu; closing alone is a button too.
                                [$primary, $more] = match ($item->type) {
                                    \App\Enums\InboxItemType::Classification => [null, $classifies ? ['other'] : []],
                                    \App\Enums\InboxItemType::ProcessingError => [$retries ? 'retry' : ($viewUrl ? 'view' : null), $retries && $viewUrl ? ['view'] : []],
                                    \App\Enums\InboxItemType::Invitation => ['remind', ['reinvite']],
                                    \App\Enums\InboxItemType::Completion => ['remind', $viewUrl ? ['view'] : []],
                                    \App\Enums\InboxItemType::Feedback => [$viewUrl ? 'incorporate' : null, $canReply && ! $replying ? ['reply'] : []],
                                    \App\Enums\InboxItemType::Threshold => [$viewUrl ? 'to_run' : null, []],
                                    \App\Enums\InboxItemType::Publication => [$viewUrl ? 'to_reading' : null, []],
                                    default => [$viewUrl ? 'view' : null, []],
                                };
                            @endphp
                            <div class="flex flex-wrap items-start justify-end gap-2">
                                @if ($classifies)
                                    {{-- Besorolás: the process and the date of the occasion (12 · 12.6) --}}
                                    <x-form.inline as="form" wire:submit="classifyMaterial({{ $item->id }})" class="text-left">
                                        <div class="grid gap-2 sm:grid-cols-2">
                                            <x-form.select name="classify.{{ $item->id }}.process_id" wire:model="classify.{{ $item->id }}.process_id" :label="__('admin.occasions.process')">
                                                <option value="">–</option>
                                                @foreach ($processes->get($item->organization_id, collect()) as $process)
                                                    <option value="{{ $process->id }}">{{ $process->name }}</option>
                                                @endforeach
                                            </x-form.select>
                                            <x-form.input name="classify.{{ $item->id }}.date" type="date" wire:model="classify.{{ $item->id }}.date" :label="__('admin.occasions.date')" :max="today()->toDateString()" />
                                        </div>
                                        <x-slot:actions>
                                            <x-ui.button type="submit" size="sm">{{ __('admin.inbox.done') }}</x-ui.button>
                                        </x-slot:actions>
                                    </x-form.inline>
                                @endif

                                @switch($primary)
                                    @case('retry')
                                        <x-ui.button tone="outline" size="sm" icon="refresh" wire:click="retry({{ $item->id }})">{{ __('admin.inbox.retry') }}</x-ui.button>
                                        @break
                                    @case('remind')
                                        <x-ui.button tone="outline" size="sm" icon="mail" wire:click="remind({{ $item->id }})">{{ __('admin.inbox.remind') }}</x-ui.button>
                                        @break
                                    @case('incorporate')
                                        <x-ui.button tone="outline" size="sm" icon-right="chevron-right" :href="$viewUrl">{{ __('admin.inbox.incorporate') }}</x-ui.button>
                                        @break
                                    @case('to_run')
                                        <x-ui.button tone="outline" size="sm" icon-right="chevron-right" :href="$viewUrl">{{ __('admin.inbox.to_run') }}</x-ui.button>
                                        @break
                                    @case('to_reading')
                                        <x-ui.button tone="outline" size="sm" icon-right="chevron-right" :href="$viewUrl">{{ __('admin.inbox.to_reading') }}</x-ui.button>
                                        @break
                                    @case('view')
                                        <x-ui.button tone="outline" size="sm" icon-right="chevron-right" :href="$viewUrl">{{ __('admin.inbox.view') }}</x-ui.button>
                                        @break
                                @endswitch

                                @if ($more === [] && ! $classifies)
                                    <x-ui.button tone="ghost" size="sm" wire:click="close({{ $item->id }})" aria-label="{{ $closeLabel }}: {{ $item->summary }}">{{ $closeLabel }}</x-ui.button>
                                @else
                                    {{-- Next to the classification form, the menu lines up with its fields. --}}
                                    <x-admin.row-actions :label="__('admin.common.row_actions', ['name' => $item->summary])" @class(['pt-[1.625rem]' => $classifies])>
                                        @foreach ($more as $action)
                                            @switch($action)
                                                @case('other')
                                                    <x-admin.row-action wire:click="markOther({{ $item->id }})">{{ __('admin.occasions.other') }}</x-admin.row-action>
                                                    @break
                                                @case('reinvite')
                                                    <x-admin.row-action wire:click="reinvite({{ $item->id }})">{{ __('admin.inbox.reinvite') }}</x-admin.row-action>
                                                    @break
                                                @case('reply')
                                                    <x-admin.row-action wire:click="$set('replyingTo', {{ $item->id }})">{{ __('admin.inbox.reply') }}</x-admin.row-action>
                                                    @break
                                                @case('view')
                                                    <x-admin.row-action :href="$viewUrl">{{ __('admin.inbox.view') }}</x-admin.row-action>
                                                    @break
                                            @endswitch
                                        @endforeach
                                        <x-admin.row-action wire:click="close({{ $item->id }})" aria-label="{{ $closeLabel }}: {{ $item->summary }}">{{ $closeLabel }}</x-admin.row-action>
                                    </x-admin.row-actions>
                                @endif

                                @if ($replying)
                                    <form wire:submit="reply({{ $item->id }})" class="flex basis-full flex-col gap-2 text-left">
                                        <x-form.textarea name="replies.{{ $item->id }}" wire:model="replies.{{ $item->id }}" :label="__('admin.inbox.reply_text')" rows="3" />
                                        <x-ui.button type="submit" size="sm" class="self-end">{{ __('admin.inbox.send_reply') }}</x-ui.button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                {{-- Only "Lezártak" gets here: an empty open list shows the empty state above. --}}
                <x-admin.empty-row :colspan="5">{{ __('admin.common.empty') }}</x-admin.empty-row>
            @endforelse
        </x-admin.table>
    @endif
</div>
