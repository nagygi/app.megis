<div class="max-w-2xl">
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="$isEditing ? __('admin.organizations.tabs.data') : __('admin.organizations.create_title')" />

    <form wire:submit="save" class="flex flex-col gap-5 rounded-card border border-grey-250 bg-white p-6">
        @if ($isEditing)
            <div class="flex flex-col gap-1.5">
                <span class="text-sm font-medium">{{ __('admin.organizations.type') }}</span>
                <span>{{ $organization->type->label() }}</span>
            </div>
        @else
            <fieldset class="flex flex-col gap-2">
                <legend class="mb-1 text-sm font-medium">{{ __('admin.organizations.type') }} <span class="font-normal text-grey-600">· {{ __('ui.required') }}</span></legend>
                @foreach ($typeOptions as $typeOption)
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="type" value="{{ $typeOption->value }}" wire:model.live="type" class="size-4 accent-charcoal">
                        {{ $typeOption->label() }}
                    </label>
                @endforeach
                <p class="text-sm text-grey-600">{{ __('admin.organizations.type_help') }}</p>
                <x-form.error name="type" id="type" />
            </fieldset>
        @endif

        <x-form.input name="name" wire:model.live.debounce.400ms="name" :label="__('admin.organizations.name')" required />

        <div class="flex flex-col gap-1.5">
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <x-form.input
                        name="slug"
                        wire:model.live.debounce.400ms="slug"
                        :label="__('admin.organizations.slug')"
                        :help="__('admin.organizations.slug_help', ['url' => url('/'.($slug ?: '…'))])"
                        :readonly="$slugLocked"
                        required
                    />
                </div>
                @if ($slugLocked)
                    <x-ui.button tone="outline" size="sm" wire:click="unlockSlug" class="mb-7">{{ __('admin.organizations.slug_edit') }}</x-ui.button>
                @endif
            </div>
            @if ($slugChanged)
                <p class="rounded-field bg-yellow-pale px-3 py-2 text-sm" role="alert">{{ __('admin.organizations.slug_warning') }}</p>
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
            <x-ui.button tone="outline" :href="route('admin.organizations.index')">{{ __('admin.common.back') }}</x-ui.button>
        </div>
    </form>
</div>
