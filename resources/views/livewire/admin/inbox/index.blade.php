@php([$sortField, $sortDirection] = $this->currentSort())

<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="__('admin.inbox.title')" />

    <x-admin.list-toolbar>
        <x-admin.filter name="state" :label="__('admin.inbox.state')" :all-label="__('admin.inbox.open')" :options="['closed' => __('admin.inbox.closed')]" />
        <x-admin.filter name="type" :label="__('admin.inbox.type')" :options="$types" />
        <x-admin.filter name="organization" :label="__('admin.inbox.organization')" :options="$organizations" />
        <x-admin.filter name="contact" :label="__('admin.inbox.contact')" :all-label="__('admin.inbox.contact_me')" :options="$contacts" />
    </x-admin.list-toolbar>

    @if ($items->isEmpty() && ! $showClosed)
        <div class="flex flex-col items-center gap-3 rounded-card border border-grey-250 bg-white px-6 py-12 text-center">
            <x-megis.symbol name="tree-of-life" class="size-16 text-green" />
            <p class="text-lg font-semibold">{{ __('admin.inbox.empty') }}</p>
        </div>
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

            @foreach ($items as $item)
                <tr wire:key="inbox-{{ $item->id }}">
                    <td class="px-3 py-2"><x-ui.badge>{{ $item->type->label() }}</x-ui.badge></td>
                    <td class="px-3 py-2">
                        @if ($item->diagnosis)
                            <a href="{{ route('admin.diagnoses.show', $item->diagnosis) }}" class="font-semibold text-charcoal">{{ $item->organization?->name }}@if ($item->diagnosis->team) · {{ $item->diagnosis->team->name }}@endif</a>
                        @else
                            {{ $item->organization?->name ?? __('admin.common.none') }}
                        @endif
                    </td>
                    <td class="px-3 py-2">{{ $item->summary }}</td>
                    <td class="px-3 py-2 whitespace-nowrap text-grey-700" title="{{ $item->created_at->format('Y.m.d. H:i') }}">{{ $item->created_at->diffForHumans() }}</td>
                    @if ($showClosed)
                        <td class="px-3 py-2 whitespace-nowrap text-grey-700">
                            {{ $item->closed_at->format('Y.m.d. H:i') }}
                            <span class="block text-sm">{{ $item->closer?->name ?? __('admin.inbox.closed_automatically') }}</span>
                        </td>
                    @else
                        <td class="px-3 py-2 text-right">
                            @php($viewUrl = $this->viewUrl($item))
                            <div class="flex flex-wrap items-end justify-end gap-2">
                                @switch($item->type)
                                    @case(\App\Enums\InboxItemType::Classification)
                                        @if ($item->subject instanceof \App\Models\Material)
                                            <form wire:submit="classifyMaterial({{ $item->id }})" class="flex flex-wrap items-end justify-end gap-2 text-left">
                                                <x-form.select name="classify.{{ $item->id }}.process_id" wire:model="classify.{{ $item->id }}.process_id" :label="__('admin.occasions.process')">
                                                    <option value="">–</option>
                                                    @foreach ($processes->get($item->organization_id, collect()) as $process)
                                                        <option value="{{ $process->id }}">{{ $process->name }}</option>
                                                    @endforeach
                                                </x-form.select>
                                                <x-form.input name="classify.{{ $item->id }}.date" type="date" wire:model="classify.{{ $item->id }}.date" :label="__('admin.occasions.date')" :max="today()->toDateString()" />
                                                <x-ui.button type="submit" size="sm">{{ __('admin.inbox.done') }}</x-ui.button>
                                                <x-ui.button tone="outline" size="sm" wire:click="markOther({{ $item->id }})">{{ __('admin.occasions.other') }}</x-ui.button>
                                            </form>
                                        @endif
                                        @break
                                    @case(\App\Enums\InboxItemType::ProcessingError)
                                        @if ($item->subject instanceof \App\Models\Material || $item->subject instanceof \App\Models\MainDiagnosis)
                                            <x-ui.button size="sm" wire:click="retry({{ $item->id }})">{{ __('admin.inbox.retry') }}</x-ui.button>
                                        @endif
                                        @if ($viewUrl)
                                            <x-ui.button tone="outline" size="sm" :href="$viewUrl">{{ __('admin.inbox.view') }}</x-ui.button>
                                        @endif
                                        @break
                                    @case(\App\Enums\InboxItemType::Invitation)
                                        <x-ui.button size="sm" wire:click="remind({{ $item->id }})">{{ __('admin.inbox.remind') }}</x-ui.button>
                                        <x-ui.button tone="outline" size="sm" wire:click="reinvite({{ $item->id }})">{{ __('admin.inbox.reinvite') }}</x-ui.button>
                                        @break
                                    @case(\App\Enums\InboxItemType::Completion)
                                        <x-ui.button size="sm" wire:click="remind({{ $item->id }})">{{ __('admin.inbox.remind') }}</x-ui.button>
                                        @if ($viewUrl)
                                            <x-ui.button tone="outline" size="sm" :href="$viewUrl">{{ __('admin.inbox.view') }}</x-ui.button>
                                        @endif
                                        @break
                                    @case(\App\Enums\InboxItemType::Feedback)
                                        @if ($viewUrl)
                                            <x-ui.button size="sm" :href="$viewUrl">{{ __('admin.inbox.incorporate') }}</x-ui.button>
                                        @endif
                                        @if ($item->subject instanceof \App\Models\ReportFeedback && $item->subject->user)
                                            @if ($replyingTo === $item->id)
                                                <form wire:submit="reply({{ $item->id }})" class="flex w-full flex-col items-end gap-2 text-left">
                                                    <x-form.textarea name="replies.{{ $item->id }}" wire:model="replies.{{ $item->id }}" :label="__('admin.inbox.reply_text')" rows="3" />
                                                    <x-ui.button type="submit" size="sm">{{ __('admin.inbox.send_reply') }}</x-ui.button>
                                                </form>
                                            @else
                                                <x-ui.button tone="outline" size="sm" wire:click="$set('replyingTo', {{ $item->id }})">{{ __('admin.inbox.reply') }}</x-ui.button>
                                            @endif
                                        @endif
                                        @break
                                    @case(\App\Enums\InboxItemType::Threshold)
                                        @if ($viewUrl)
                                            <x-ui.button size="sm" :href="$viewUrl">{{ __('admin.inbox.to_run') }}</x-ui.button>
                                        @endif
                                        @break
                                    @case(\App\Enums\InboxItemType::Publication)
                                        @if ($viewUrl)
                                            <x-ui.button size="sm" :href="$viewUrl">{{ __('admin.inbox.to_reading') }}</x-ui.button>
                                        @endif
                                        @break
                                    @default
                                        @if ($viewUrl)
                                            <x-ui.button tone="outline" size="sm" :href="$viewUrl">{{ __('admin.inbox.view') }}</x-ui.button>
                                        @endif
                                @endswitch
                                <x-ui.button tone="outline" size="sm" wire:click="close({{ $item->id }})" aria-label="{{ __('admin.inbox.close') }}: {{ $item->summary }}">{{ $item->type === \App\Enums\InboxItemType::MirrorBooking ? __('admin.inbox.done') : __('admin.inbox.close') }}</x-ui.button>
                            </div>
                        </td>
                    @endif
                </tr>
            @endforeach
        </x-admin.table>
    @endif
</div>
