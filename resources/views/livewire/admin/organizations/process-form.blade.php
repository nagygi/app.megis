{{-- Új folyamat, under the Folyamatok tab (11 · 11.6). --}}
<div>
    <x-admin.page-header :title="__('admin.processes.add')" />

    <x-ui.card as="form" bordered padding="lg" wire:submit="save" class="flex flex-col gap-6 *:max-w-3xl">
        <x-form.input name="name" wire:model="name" :label="__('processes.list.fields.name')" required autofocus />
        <x-form.select name="rhythm" wire:model="rhythm" :label="__('processes.list.fields.rhythm')">
            <option value="">–</option>
            @foreach (\App\Enums\ProcessRhythm::cases() as $rhythmOption)
                <option value="{{ $rhythmOption->value }}">{{ $rhythmOption->label() }}</option>
            @endforeach
        </x-form.select>
        <x-form.select name="parentId" wire:model="parentId" :label="__('processes.list.fields.parent')">
            <option value="">{{ __('processes.list.parent_none') }}</option>
            @foreach ($parents as $parent)
                <option value="{{ $parent->id }}">{{ $parent->name }}</option>
            @endforeach
        </x-form.select>
        <x-form.textarea name="description" wire:model="description" :label="__('processes.sheet.description')" rows="3" maxlength="2000" />

        <div class="flex flex-wrap gap-2">
            <x-ui.button type="submit" icon="plus">{{ __('processes.list.add') }}</x-ui.button>
            <x-ui.button tone="outline" :href="route('admin.organizations.processes', $organization)">{{ __('admin.common.cancel') }}</x-ui.button>
        </div>
    </x-ui.card>
</div>
