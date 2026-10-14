<div class="max-w-2xl">
    <x-admin.page-header :title="$isEditing ? __('admin.admins.edit_title') : __('admin.admins.invite_title')" />

    @unless ($canEdit)
        <p class="mb-6 rounded-card bg-yellow-pale p-4">{{ __('admin.admins.superadmin_readonly') }}</p>
    @endunless

    <form wire:submit="save" class="flex flex-col gap-5 rounded-card border border-grey-250 bg-white p-6">
        <fieldset @disabled(! $canEdit) class="flex flex-col gap-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="last_name" wire:model="last_name" :label="__('admin.admins.last_name')" autocomplete="family-name" required />
                <x-form.input name="first_name" wire:model="first_name" :label="__('admin.admins.first_name')" autocomplete="given-name" required />
            </div>

            <x-form.input
                name="email"
                type="email"
                wire:model="email"
                :label="__('admin.admins.email')"
                :help="$canChangeEmail ? null : __('admin.profile.email_readonly')"
                :disabled="! $canChangeEmail"
                required
            />

            <x-form.select name="role" wire:model="role" :label="__('admin.admins.role')" :help="__('admin.admins.role_help')" required>
                @foreach ($roles as $roleOption)
                    <option value="{{ $roleOption->value }}">{{ $roleOption->label() }}</option>
                @endforeach
            </x-form.select>
        </fieldset>

        <div class="flex flex-wrap gap-3">
            @if ($canEdit)
                <x-ui.button type="submit">{{ $isEditing ? __('admin.common.save') : __('admin.admins.invite_title') }}</x-ui.button>
            @endif
            <x-ui.button tone="outline" :href="route('admin.admins.index')">{{ __('admin.common.cancel') }}</x-ui.button>
        </div>
    </form>
</div>
