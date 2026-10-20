<div class="flex max-w-3xl flex-col gap-4">
    <x-ui.flash />

    <x-admin.page-header :title="__('admin.support_questions.title')">
        <x-slot:actions>
            <x-ui.button wire:click="create">{{ __('admin.support_questions.create') }}</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <p class="text-sm text-grey-700">{{ __('admin.support_questions.help') }}</p>

    @if ($showForm)
        <form wire:submit="save" class="flex flex-col gap-4 rounded-card border border-grey-250 bg-white p-5">
            <x-form.input name="text" wire:model="text" :label="__('admin.support_questions.text')" maxlength="255" required />
            <x-form.toggle name="is_active" wire:model="is_active" :label="__('admin.support_questions.active')" :help="__('admin.support_questions.active_help')" />
            <div class="flex flex-wrap gap-3">
                <x-ui.button type="submit">{{ __('admin.common.save') }}</x-ui.button>
                <x-ui.button tone="outline" wire:click="cancel">{{ __('admin.common.cancel') }}</x-ui.button>
            </div>
        </form>
    @endif

    <ol wire:sort="sortQuestion" class="flex flex-col gap-2">
        @foreach ($questions as $question)
            <li wire:key="question-{{ $question->id }}" wire:sort:item="{{ $question->id }}" @class(['flex items-center gap-3 rounded-card border border-grey-250 bg-white px-4 py-3', 'opacity-60' => ! $question->is_active])>
                <span wire:sort:handle class="cursor-grab select-none text-grey-600" title="{{ __('admin.list_editor.drag') }}" aria-hidden="true">⋮⋮</span>
                <span class="flex-1">{{ $question->text }}</span>
                @unless ($question->is_active)
                    <x-ui.badge tone="muted">{{ __('admin.support_questions.inactive') }}</x-ui.badge>
                @endunless
                <div class="flex shrink-0 items-center gap-1" wire:sort:ignore>
                    <button type="button" wire:click="move({{ $question->id }}, -1)" @disabled($loop->first) class="rounded-field px-2 py-1 hover:bg-grey-100 disabled:opacity-40" aria-label="{{ __('admin.support_questions.up', ['text' => $question->text]) }}">↑</button>
                    <button type="button" wire:click="move({{ $question->id }}, 1)" @disabled($loop->last) class="rounded-field px-2 py-1 hover:bg-grey-100 disabled:opacity-40" aria-label="{{ __('admin.support_questions.down', ['text' => $question->text]) }}">↓</button>
                    <button type="button" wire:click="toggleActive({{ $question->id }})" class="rounded-field px-2 py-1 text-sm hover:bg-grey-100">{{ $question->is_active ? __('admin.common.deactivate') : __('admin.common.activate') }}</button>
                    <button type="button" wire:click="edit({{ $question->id }})" class="rounded-field px-2 py-1 text-sm hover:bg-grey-100">{{ __('admin.common.edit') }}</button>
                </div>
            </li>
        @endforeach
    </ol>
</div>
