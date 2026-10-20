<div class="max-w-3xl">
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="$isEditing ? __('admin.organizations.tabs.data') : __('admin.organizations.create_title')" />

    <x-ui.card as="form" bordered padding="lg" wire:submit="save" class="flex flex-col gap-5">
        @if ($isEditing)
            <div class="flex flex-col gap-1.5">
                <span class="text-sm font-medium">{{ __('admin.organizations.type') }}</span>
                <span>{{ $organization->type->label() }}</span>
            </div>
        @else
            <x-form.radio-group
                name="type"
                wire:model.live="type"
                :label="__('admin.organizations.type')"
                :options="collect($typeOptions)->mapWithKeys(fn ($typeOption) => [$typeOption->value => $typeOption->label()])->all()"
                :help="__('admin.organizations.type_help')"
                required
                inline
            />
        @endif

        <x-form.input name="name" wire:model.live.debounce.400ms="name" :label="__('admin.organizations.name')" required />

        <div class="flex flex-col gap-2">
            <x-form.inline>
                <x-form.input
                    name="slug"
                    wire:model.live.debounce.400ms="slug"
                    :label="__('admin.organizations.slug')"
                    :help="__('admin.organizations.slug_help', ['url' => url('/'.($slug ?: '…'))])"
                    :readonly="$slugLocked"
                    required
                />
                <x-slot:actions>
                    @if ($slugLocked)
                        <x-ui.button tone="outline" size="sm" wire:click="unlockSlug">{{ __('admin.organizations.slug_edit') }}</x-ui.button>
                    @endif
                </x-slot:actions>
            </x-form.inline>
            @if ($slugChanged)
                <x-ui.notice tone="warning" role="alert">{{ __('admin.organizations.slug_warning') }}</x-ui.notice>
            @endif
        </div>

        <x-form.textarea name="description" wire:model="description" :label="__('admin.organizations.description')" />

        <x-form.select name="contact_admin_id" wire:model="contact_admin_id" :label="__('admin.organizations.contact')" :help="__('admin.organizations.contact_help')">
            <option value="">{{ __('admin.organizations.contact_none') }}</option>
            @foreach ($contacts as $contact)
                <option value="{{ $contact->id }}">{{ $contact->name }}</option>
            @endforeach
        </x-form.select>

        <x-form.select name="product_id" wire:model="product_id" :label="__('admin.organizations.product')" required>
            <option value="">{{ __('admin.organizations.product_select') }}</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}">{{ $product->name }}</option>
            @endforeach
        </x-form.select>

        @if ($type !== \App\Enums\OrganizationType::Individual->value)
            <x-form.input
                name="headcount_limit"
                type="number"
                min="1"
                wire:model="headcount_limit"
                :label="__('admin.organizations.headcount_limit')"
                :help="__('admin.organizations.headcount_limit_help')"
            />
        @endif

        <div class="flex flex-wrap gap-3">
            <x-ui.button type="submit">{{ __('admin.common.save') }}</x-ui.button>
            <x-ui.button tone="outline" :href="route('admin.organizations.index')">{{ __('admin.common.cancel') }}</x-ui.button>
        </div>
    </x-ui.card>
</div>
