{{-- A short list ordered by hand, edited in place: it keeps the form width. --}}
<div class="flex max-w-3xl flex-col gap-6">
    <x-ui.flash />

    <x-admin.page-header :title="__('admin.support_questions.title')" :description="__('admin.support_questions.help')" :spaced="false">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="create">{{ __('admin.support_questions.create') }}</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($showForm)
        <x-ui.card as="form" bordered padding="lg" wire:submit="save" class="flex flex-col gap-4">
            <x-form.input name="text" wire:model="text" :label="__('admin.support_questions.text')" maxlength="255" required />
            <x-form.toggle name="is_active" wire:model="is_active" :label="__('admin.support_questions.active')" :help="__('admin.support_questions.active_help')" />
            <div class="flex flex-wrap gap-3">
                <x-ui.button type="submit">{{ __('admin.common.save') }}</x-ui.button>
                <x-ui.button tone="outline" wire:click="cancel">{{ __('admin.common.cancel') }}</x-ui.button>
            </div>
        </x-ui.card>
    @endif

    @if ($questions->isEmpty())
        <x-ui.empty-state class="border border-grey-250">
            {{ __('admin.support_questions.empty') }}
            <x-slot:action>
                <x-ui.button tone="outline" icon="plus" wire:click="create">{{ __('admin.support_questions.create') }}</x-ui.button>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        <ol wire:sort="sortQuestion" class="flex flex-col gap-2">
            @foreach ($questions as $question)
                <x-ui.card
                    as="li"
                    bordered
                    padding="none"
                    wire:key="question-{{ $question->id }}"
                    wire:sort:item="{{ $question->id }}"
                    :class="'flex items-center gap-2 py-1 pr-2 pl-1'.($question->is_active ? '' : ' text-grey-600')"
                >
                    <span wire:sort:handle class="grid size-11 shrink-0 cursor-grab place-items-center text-grey-600" title="{{ __('admin.list_editor.drag') }}" aria-hidden="true">
                        <x-megis.icon name="drag" />
                    </span>
                    <span class="flex-1 py-2">{{ $question->text }}</span>
                    @unless ($question->is_active)
                        <x-ui.badge tone="muted">{{ __('admin.support_questions.inactive') }}</x-ui.badge>
                    @endunless
                    <div class="flex shrink-0 items-center" wire:sort:ignore>
                        <x-ui.icon-button icon="arrow-up" :label="__('admin.support_questions.up', ['text' => $question->text])" wire:click="move({{ $question->id }}, -1)" :disabled="$loop->first" />
                        <x-ui.icon-button icon="arrow-down" :label="__('admin.support_questions.down', ['text' => $question->text])" wire:click="move({{ $question->id }}, 1)" :disabled="$loop->last" />
                        <x-admin.row-actions :label="__('admin.common.row_actions', ['name' => $question->text])">
                            <x-admin.row-action wire:click="edit({{ $question->id }})">{{ __('admin.common.edit') }}</x-admin.row-action>
                            @if ($question->is_active)
                                <x-admin.row-action tone="danger" wire:click="toggleActive({{ $question->id }})">{{ __('admin.common.deactivate') }}</x-admin.row-action>
                            @else
                                <x-admin.row-action wire:click="toggleActive({{ $question->id }})">{{ __('admin.common.activate') }}</x-admin.row-action>
                            @endif
                        </x-admin.row-actions>
                    </div>
                </x-ui.card>
            @endforeach
        </ol>
    @endif
</div>
