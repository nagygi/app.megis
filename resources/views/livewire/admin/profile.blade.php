<div class="flex max-w-2xl flex-col gap-6">
    <x-ui.flash />

    <x-admin.page-header :title="__('admin.profile.title')" :spaced="false" />

    <form wire:submit="saveProfile" class="flex flex-col gap-5 rounded-card border border-grey-250 bg-white p-6">
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

        <div class="flex flex-col gap-1">
            <span class="text-sm font-medium">{{ __('admin.profile.role') }}</span>
            <span><x-ui.badge :tone="$admin->isSuperadmin() ? 'dark' : 'neutral'">{{ $admin->role->label() }}</x-ui.badge></span>
        </div>

        <x-ui.button type="submit" class="self-start">{{ __('admin.common.save') }}</x-ui.button>
    </form>

    <form wire:submit="changePassword" class="flex flex-col gap-5 rounded-card border border-grey-250 bg-white p-6">
        <h2 class="text-lg font-semibold">{{ __('admin.profile.password_title') }}</h2>

        <x-form.input name="current_password" type="password" wire:model="current_password" :label="__('auth_ui.current_password')" autocomplete="current-password" required />
        <x-form.input
            name="password"
            type="password"
            wire:model="password"
            :label="__('auth_ui.new_password')"
            :help="\App\Support\PasswordRules::hintFor($admin)"
            autocomplete="new-password"
            required
        />
        <x-form.input name="password_confirmation" type="password" wire:model="password_confirmation" :label="__('auth_ui.password_confirmation')" autocomplete="new-password" required />

        <x-ui.button type="submit" class="self-start">{{ __('admin.common.save') }}</x-ui.button>
    </form>
</div>
