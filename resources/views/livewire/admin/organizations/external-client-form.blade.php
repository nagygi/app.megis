<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="$isEditing ? $externalClient->name : __('admin.external_clients.create')" />

    <x-ui.card as="form" bordered padding="lg" wire:submit="save" class="flex flex-col gap-8 *:max-w-3xl">
        <section class="flex flex-col gap-5">
            <x-form.input name="name" wire:model="name" :label="__('admin.external_clients.name')" required autofocus />
            <x-form.textarea name="description" wire:model="description" :label="__('admin.external_clients.description')" rows="3" />
            <x-form.toggle name="active" wire:model="active" :label="__('admin.external_clients.active')" :help="__('admin.external_clients.active_help')" />
        </section>

        <fieldset class="flex flex-col gap-4">
            <legend class="mb-1 flex items-center gap-2 text-lg font-semibold">
                {{ __('admin.external_clients.users') }}
                <x-ui.badge>{{ count($users) }}</x-ui.badge>
            </legend>
            <p class="text-sm text-grey-700">{{ __('admin.external_clients.users_help') }}</p>

            <ul id="external-client-users" class="divide-y divide-grey-200 rounded-card border border-grey-250">
                @forelse ($users as $index => $user)
                    @php
                        $account = $user['id'] !== null ? $accounts->get($user['id']) : null;
                        $name = trim($user['last_name'].' '.$user['first_name']);
                    @endphp
                    <li wire:key="external-client-user-{{ $user['email'] }}" class="flex flex-wrap items-center gap-x-4 gap-y-2 py-2 pr-2 pl-4">
                        <div class="min-w-0 flex-1">
                            <p class="flex flex-wrap items-center gap-2 font-semibold text-charcoal">
                                @if ($account !== null)
                                    <x-admin.user-link :user="$account" />
                                @else
                                    {{ $name !== '' ? $name : $user['email'] }}
                                @endif
                                @if ($account?->isActivated())
                                    <x-admin.user-status status="active">{{ __('admin.external_clients.user_status.active') }}</x-admin.user-status>
                                @else
                                    <x-admin.user-status status="pending">{{ __('admin.external_clients.user_status.pending') }}</x-admin.user-status>
                                @endif
                            </p>
                            @if ($name !== '')
                                <p class="text-sm break-all text-grey-700">{{ $user['email'] }}</p>
                            @endif
                        </div>
                        <x-ui.icon-button icon="close" :label="__('admin.external_clients.remove_user', ['name' => $name !== '' ? $name : $user['email']])" wire:click="removeUser({{ $index }})" />
                    </li>
                @empty
                    <li class="px-4 py-3 text-sm text-grey-700">{{ __('admin.external_clients.no_users') }}</li>
                @endforelse
            </ul>

            <div class="flex flex-col gap-3 rounded-card border border-grey-250 p-4">
                <p class="text-sm font-medium">{{ __('admin.external_clients.add_user') }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-form.input name="newLastName" wire:model="newLastName" :label="__('admin.members.last_name')" />
                    <x-form.input name="newFirstName" wire:model="newFirstName" :label="__('admin.members.first_name')" />
                </div>
                <x-form.input name="newEmail" type="email" wire:model="newEmail" wire:keydown.enter.prevent="addUser" :label="__('admin.members.email')" :help="__('admin.external_clients.add_user_help')" />
                <div>
                    <x-ui.button tone="outline" icon="plus" wire:click="addUser">{{ __('admin.external_clients.add_user_button') }}</x-ui.button>
                </div>
            </div>
        </fieldset>

        <fieldset class="flex flex-col gap-4">
            <legend class="mb-1 text-lg font-semibold">{{ __('admin.external_clients.measured_values') }}</legend>

            <x-form.textarea name="valueQuestion" wire:model="valueQuestion" :label="__('admin.external_clients.value_question')" :help="__('admin.external_clients.value_question_help')" rows="3" />

            <div class="flex flex-col gap-3 rounded-card border border-grey-250 p-4">
                <p class="text-sm font-medium">{{ __('admin.external_clients.values_measured') }}</p>
                @forelse ($valueNames as $key => $valueName)
                    <x-form.checkbox :name="'values.'.$key" :id="'external-client-value-'.$key" wire:key="external-client-value-{{ $key }}" wire:model="values.{{ $key }}" :label="$valueName" />
                @empty
                    <p class="text-sm text-grey-700">
                        {{ __('admin.external_clients.no_values') }}
                        <a href="{{ route('admin.organizations.values', $organization) }}" class="font-semibold underline">{{ __('admin.organizations.tabs.values') }}</a>
                    </p>
                @endforelse
            </div>

            <x-form.toggle name="anyValue" wire:model="anyValue" :label="__('admin.external_clients.any_value')" :help="__('admin.external_clients.any_value_help')" />
        </fieldset>

        <fieldset class="flex flex-col gap-4">
            <legend class="mb-1 text-lg font-semibold">{{ __('admin.external_clients.measured_processes') }}</legend>
            <p class="text-sm text-grey-700">{{ __('admin.external_clients.process_questions_help') }}</p>

            @forelse ($processList as $processId => $process)
                <div wire:key="external-client-process-{{ $processId }}" class="flex flex-col gap-3 rounded-card border border-grey-250 p-4">
                    <x-form.checkbox :name="'processes.'.$processId.'.selected'" :id="'external-client-process-'.$processId" wire:model.live="processes.{{ $processId }}.selected" :label="$process->name" />
                    @if ($processes[$processId]['selected'] ?? false)
                        <x-form.textarea :name="'processes.'.$processId.'.question'" :id="'external-client-process-question-'.$processId" wire:model="processes.{{ $processId }}.question" :label="__('admin.external_clients.question')" rows="3" />
                    @endif
                </div>
            @empty
                <p class="text-sm text-grey-700">{{ __('admin.external_clients.no_processes') }}</p>
            @endforelse

            <div>
                <x-ui.button tone="outline" icon="plus" :href="route('admin.organizations.processes.create', $organization)">{{ __('admin.external_clients.new_process') }}</x-ui.button>
            </div>
        </fieldset>

        <div class="flex flex-wrap gap-3">
            <x-ui.button type="submit">{{ __('admin.common.save') }}</x-ui.button>
            <x-ui.button tone="outline" :href="route('admin.organizations.external-clients', $organization)">{{ __('admin.common.cancel') }}</x-ui.button>
        </div>
    </x-ui.card>
</div>
