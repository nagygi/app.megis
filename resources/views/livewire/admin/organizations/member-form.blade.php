<div class="max-w-3xl">
    <x-admin.page-header :title="$isEditing ? __('admin.members.edit_title') : __('admin.members.invite_title')" />

    <x-ui.card as="form" bordered padding="lg" wire:submit="save" class="flex flex-col gap-6">
        @if ($isEditing)
            <div class="flex flex-col gap-1">
                <span class="text-sm font-medium">{{ __('admin.members.email') }}</span>
                <span class="font-semibold">{{ $membership->user->name }} · {{ $membership->user->email }}</span>
            </div>
        @else
            <x-form.input name="email" type="email" wire:model="email" :label="__('admin.members.email')" required autofocus />

            <div class="flex flex-col gap-2">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.input name="last_name" wire:model="last_name" :label="__('admin.members.last_name')" />
                    <x-form.input name="first_name" wire:model="first_name" :label="__('admin.members.first_name')" />
                </div>
                <p class="text-sm text-grey-700">{{ __('admin.members.names_help') }}</p>
            </div>
        @endif

        @unless ($organization->isIndividual())
            <fieldset class="flex flex-col gap-2">
                <legend class="mb-1 text-sm font-medium">{{ __('admin.members.tags') }} <span class="font-normal text-grey-600">· {{ __('ui.required') }}</span></legend>
                <x-form.checkbox name="isOrganizationLeader" wire:model="isOrganizationLeader" :label="__('enums.membership_tag.organization_leader')" />
                <x-form.checkbox name="isEmployee" wire:model="isEmployee" :label="__('enums.membership_tag.employee')" />
                <x-form.checkbox name="isPartner" wire:model="isPartner" :label="__('enums.membership_tag.partner')" />
                <p class="text-sm text-grey-700">{{ __('admin.members.tags_help') }}</p>
                <x-form.error name="tags" id="tags" />
            </fieldset>

            @if ($teams->isNotEmpty())
                <fieldset class="flex flex-col gap-2">
                    <legend class="mb-1 text-sm font-medium">{{ __('admin.members.teams') }}</legend>
                    @foreach ($teams as $team)
                        <div class="flex flex-wrap items-start gap-x-6 gap-y-2" wire:key="team-option-{{ $team->id }}">
                            <x-form.checkbox name="teamIds" :id="'team-'.$team->id" :value="$team->id" wire:model.live="teamIds" :label="$team->name" />
                            @if (in_array($team->id, array_map('intval', $teamIds), true))
                                <x-form.checkbox name="leaderTeamIds" :id="'leader-team-'.$team->id" :value="$team->id" wire:model="leaderTeamIds" :label="__('admin.members.team_leader')" />
                            @endif
                        </div>
                    @endforeach
                </fieldset>
            @endif
        @endunless

        <div class="flex flex-col gap-1.5">
            <x-form.input name="position" wire:model="position" :label="__('admin.members.position')" :help="__('admin.members.position_help')" list="positions" autocomplete="off" />
            <datalist id="positions">
                @foreach ($positions as $positionOption)
                    <option value="{{ $positionOption }}"></option>
                @endforeach
            </datalist>
        </div>

        @unless ($isEditing)
            <x-form.toggle name="sendNow" wire:model="sendNow" :label="__('admin.members.send_now')" :help="__('admin.members.send_now_help')" />
        @endunless

        <div class="flex flex-wrap gap-3">
            <x-ui.button type="submit">{{ $isEditing ? __('admin.common.save') : __('admin.members.invite_title') }}</x-ui.button>
            <x-ui.button tone="outline" :href="route('admin.organizations.members', $organization)">{{ __('admin.common.cancel') }}</x-ui.button>
        </div>
    </x-ui.card>
</div>
