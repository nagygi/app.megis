{{-- One card of V6·d (11 · 11.2); an excluded process sits on the paler ground. --}}
@php($isExcluded = $process->kind === \App\Enums\ProcessKind::Excluded)

<x-ui.card as="li" :tone="$isExcluded ? 'paper' : 'white'" wire:key="process-{{ $process->id }}" class="flex flex-col gap-3">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex min-w-0 flex-1 flex-col gap-1">
            @if ($renamingId === $process->id)
                <x-form.inline as="form" wire:submit="saveRename" class="w-full">
                    <x-form.input name="renameValue" wire:model="renameValue" :label="__('processes.list.rename_label', ['name' => $process->name])" />
                    <x-slot:actions>
                        <x-ui.button type="submit">{{ __('diagnosis.save') }}</x-ui.button>
                    </x-slot:actions>
                </x-form.inline>
            @else
                <div class="flex flex-wrap items-center gap-x-2">
                    <h4 @class(['text-lg font-semibold', 'text-grey-700' => $isExcluded])>{{ $process->name }}</h4>
                    @if ($editable)
                        <x-ui.button tone="ghost" size="sm" wire:click="startRename({{ $process->id }})">{{ __('processes.list.rename') }}</x-ui.button>
                    @endif
                </div>
            @endif
            <p class="text-sm text-grey-700">
                @if ($process->isInCalendar())
                    {{ $process->summaryLine() }}
                @else
                    {{ collect([$process->rhythm?->label(), __('processes.list.not_in_calendar', ['count' => $process->occasions_count ?? 0])])->filter()->implode(' · ') }}
                @endif
                · {{ $process->confidence->label() }}
            </p>
            @if ($process->description)
                <p class="line-clamp-2 text-sm">{{ $process->description }}</p>
            @endif
        </div>
        <x-ui.button tone="outline" size="sm" icon-right="chevron-right" :href="$sheetUrl($process)">{{ __('processes.list.open') }}</x-ui.button>
    </div>

    @if ($process->mergeCandidate && $editable)
        <x-ui.notice>
            {{ __('processes.list.merge', ['name' => $process->mergeCandidate->name]) }}
            <x-slot:action>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button size="sm" wire:click="merge({{ $process->id }})">{{ __('processes.list.merge_accept') }}</x-ui.button>
                    <x-ui.button tone="outline" size="sm" wire:click="dismissMerge({{ $process->id }})">{{ __('processes.list.merge_dismiss') }}</x-ui.button>
                </div>
            </x-slot:action>
        </x-ui.notice>
    @endif

    @if ($editable)
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.segmented :label="__('processes.list.fields.name').': '.$process->name">
                @foreach (\App\Enums\ProcessKind::cases() as $kind)
                    <x-ui.segment :selected="$process->kind === $kind" wire:click="setKind({{ $process->id }}, '{{ $kind->value }}')">{{ $kind->label() }}</x-ui.segment>
                @endforeach
            </x-ui.segmented>
            <span class="flex-1"></span>
            @if ($process->parent_id === null)
                <x-ui.button tone="ghost" size="sm" icon="plus" wire:click="openSub({{ $process->id }})">{{ __('processes.list.add_sub') }}</x-ui.button>
            @endif
            @if ($process->kind === \App\Enums\ProcessKind::Routine)
                <x-ui.button size="sm" :tone="$process->isAccepted() ? 'outline' : 'dark'" wire:click="toggleAccept({{ $process->id }})">
                    {{ $process->isAccepted() ? __('processes.list.withdraw') : __('processes.list.accept') }}
                </x-ui.button>
            @endif
        </div>
    @elseif ($process->isAccepted())
        <x-ui.badge tone="green">{{ __('processes.list.accepted') }}</x-ui.badge>
    @endif

    @if ($addingSubTo === $process->id)
        <x-form.inline as="form" wire:submit="addSub" class="border-t border-grey-200 pt-3">
            <x-form.input name="subName" wire:model="subName" :label="__('processes.list.sub_name')" />
            <x-slot:actions>
                <x-ui.button type="submit" icon="plus">{{ __('processes.list.add') }}</x-ui.button>
            </x-slot:actions>
        </x-form.inline>
    @endif

    @if ($process->children->isNotEmpty())
        <ul class="flex flex-col gap-2 border-l-4 border-yellow pl-4">
            @foreach ($process->children as $child)
                <li class="flex flex-wrap items-center justify-between gap-2">
                    <span>
                        <span class="font-semibold">{{ $child->name }}</span>
                        <span class="text-sm text-grey-700">· {{ $child->summaryLine() ?: $child->confidence->label() }} · {{ __('processes.list.sub') }}</span>
                    </span>
                    <x-ui.button tone="ghost" size="sm" icon-right="chevron-right" :href="$sheetUrl($child)">{{ __('processes.list.open') }}</x-ui.button>
                </li>
            @endforeach
        </ul>
    @endif
</x-ui.card>
