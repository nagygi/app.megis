{{-- One card of V6·d (11 · 11.2). --}}
<li wire:key="process-{{ $process->id }}" @class([
    'flex flex-col gap-3 rounded-card bg-white p-5',
    'opacity-70' => $process->kind === \App\Enums\ProcessKind::Excluded,
])>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex flex-col gap-1">
            @if ($renamingId === $process->id)
                <form wire:submit="saveRename" class="flex flex-wrap items-end gap-2">
                    <x-form.input name="renameValue" wire:model="renameValue" :label="__('processes.list.rename_label', ['name' => $process->name])" />
                    <x-ui.button type="submit" size="sm">{{ __('diagnosis.save') }}</x-ui.button>
                </form>
            @else
                <p class="text-lg font-semibold">
                    {{ $process->name }}
                    @if ($editable)
                        · <button type="button" wire:click="startRename({{ $process->id }})" class="text-sm font-normal underline">{{ __('processes.list.rename') }}</button>
                    @endif
                </p>
            @endif
            <p class="text-sm text-grey-700">
                @if ($process->isInCalendar())
                    {{ $process->summaryLine() }}
                @else
                    {{ collect([$process->rhythm?->label(), __('processes.list.not_in_calendar', ['count' => $process->occasions_count ?? 0])])->filter()->implode(' · ') }}
                @endif
                · {{ $process->confidence->label() }}
            </p>
        </div>
        <a href="{{ $sheetUrl($process) }}" class="text-sm font-semibold">{{ __('processes.list.open') }}</a>
    </div>

    @if ($process->mergeCandidate && $editable)
        <div class="flex flex-wrap items-center gap-3 rounded-field bg-yellow-pale px-3 py-2 text-sm">
            <span>{{ __('processes.list.merge', ['name' => $process->mergeCandidate->name]) }}</span>
            <button type="button" wire:click="merge({{ $process->id }})" class="font-semibold underline">{{ __('processes.list.merge_accept') }}</button>
            <button type="button" wire:click="dismissMerge({{ $process->id }})" class="underline">{{ __('processes.list.merge_dismiss') }}</button>
        </div>
    @endif

    @if ($editable)
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex flex-wrap gap-1" role="group" aria-label="{{ __('processes.list.fields.name') }}: {{ $process->name }}">
                @foreach (\App\Enums\ProcessKind::cases() as $kind)
                    <button
                        type="button"
                        wire:click="setKind({{ $process->id }}, '{{ $kind->value }}')"
                        aria-pressed="{{ $process->kind === $kind ? 'true' : 'false' }}"
                        @class([
                            'rounded-pill border px-3 py-1 text-sm font-semibold',
                            'border-charcoal bg-charcoal text-cream' => $process->kind === $kind,
                            'border-grey-350 bg-white' => $process->kind !== $kind,
                        ])
                    >{{ $kind->label() }}</button>
                @endforeach
            </div>
            <span class="flex-1"></span>
            @if ($process->parent_id === null)
                <button type="button" wire:click="openSub({{ $process->id }})" class="text-sm font-semibold underline">{{ __('processes.list.add_sub') }}</button>
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
        <form wire:submit="addSub" class="flex flex-wrap items-end gap-2 border-t border-grey-200 pt-3">
            <x-form.input name="subName" wire:model="subName" :label="__('processes.list.sub_name')" />
            <x-ui.button type="submit" size="sm">{{ __('processes.list.add') }}</x-ui.button>
        </form>
    @endif

    @if ($process->children->isNotEmpty())
        <ul class="flex flex-col gap-2 border-l-4 border-yellow pl-4">
            @foreach ($process->children as $child)
                <li class="flex flex-wrap items-center justify-between gap-2">
                    <span>
                        <span class="font-semibold">{{ $child->name }}</span>
                        <span class="text-sm text-grey-700">· {{ $child->summaryLine() ?: $child->confidence->label() }} · {{ __('processes.list.sub') }}</span>
                    </span>
                    <a href="{{ $sheetUrl($child) }}" class="text-sm font-semibold">{{ __('processes.list.open') }}</a>
                </li>
            @endforeach
        </ul>
    @endif
</li>
