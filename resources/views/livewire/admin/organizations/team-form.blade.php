<div>
    <x-admin.page-header :title="$isEditing ? $team->name : __('admin.teams.create')" />

    <x-ui.card as="form" bordered padding="lg" wire:submit="save" class="flex flex-col gap-6 *:max-w-3xl">
        <x-form.input name="name" wire:model="name" :label="__('admin.teams.name')" required autofocus />
        <x-form.textarea name="description" wire:model="description" :label="__('admin.teams.description')" rows="3" />

        <fieldset class="flex flex-col gap-4">
            <legend class="mb-1 flex items-center gap-2 text-sm font-medium">
                {{ __('admin.teams.users') }}
                <x-ui.badge>{{ $members->count() }}</x-ui.badge>
            </legend>

            @unless ($hasOrganizationMembers)
                <p class="text-sm text-grey-700">{{ __('admin.teams.no_members') }}</p>
            @else
                <ul id="team-members" class="divide-y divide-grey-200 rounded-card border border-grey-250">
                    @forelse ($members as $member)
                        <li wire:key="team-member-{{ $member->user_id }}" class="flex flex-wrap items-center gap-x-4 gap-y-2 py-2 pr-2 pl-4">
                            <div class="min-w-0 flex-1">
                                <p class="flex flex-wrap items-center gap-2 font-semibold text-charcoal">
                                    {{ $member->user->name }}
                                    @if ($member->status === \App\Enums\MembershipStatus::Inactive)
                                        <x-admin.user-status status="inactive">{{ $member->status->label() }}</x-admin.user-status>
                                    @endif
                                </p>
                                @if ($member->user->name !== $member->user->email)
                                    <p class="text-sm break-all text-grey-700">{{ $member->user->email }}</p>
                                @endif
                            </div>
                            <x-form.toggle
                                name="leaderUserIds"
                                :id="'team-leader-'.$member->user_id"
                                :value="$member->user_id"
                                wire:model="leaderUserIds"
                                :label="__('admin.members.team_leader')"
                                :aria-label="__('admin.teams.leader_of', ['name' => $member->user->name])"
                            />
                            <x-ui.icon-button icon="close" :label="__('admin.teams.remove_member', ['name' => $member->user->name])" wire:click="removeMember({{ $member->user_id }})" />
                        </li>
                    @empty
                        <li class="px-4 py-3 text-sm text-grey-700">{{ __('admin.teams.no_team_members') }}</li>
                    @endforelse
                </ul>

                {{-- The candidates open under the search; ↓ and ↑ walk them, Enter adds the first, Esc closes. --}}
                <div
                    x-data="{
                        open: false,
                        options() {
                            return [...this.$refs.candidates.querySelectorAll('button')];
                        },
                        move(step) {
                            const options = this.options();
                            const index = options.indexOf(document.activeElement) + step;
                            index < 0 ? this.$refs.search.focus() : options[Math.min(index, options.length - 1)]?.focus();
                        },
                    }"
                    @click.outside="open = false"
                    @focusout="if (! $el.contains($event.relatedTarget)) open = false"
                    @keydown.escape="open = false"
                    class="flex flex-col gap-1.5"
                >
                    <label for="team-member-search" class="text-sm font-medium">{{ __('admin.teams.add_member') }}</label>
                    <div class="relative">
                        <x-megis.icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-grey-600" />
                        <input
                            id="team-member-search"
                            x-ref="search"
                            type="search"
                            wire:model.live.debounce.300ms="memberSearch"
                            placeholder="{{ __('admin.teams.add_member_placeholder') }}"
                            autocomplete="off"
                            aria-controls="team-member-candidates"
                            aria-describedby="team-member-search-help"
                            @focus="open = true"
                            @input="open = true"
                            @keydown.enter.prevent="$wire.addFirstCandidate($el.value)"
                            @keydown.down.prevent="open = true; $nextTick(() => move(1))"
                            class="field w-full pl-10"
                        >
                        <div
                            id="team-member-candidates"
                            x-ref="candidates"
                            x-show="open"
                            x-cloak
                            @keydown.down.prevent="move(1)"
                            @keydown.up.prevent="move(-1)"
                            class="absolute inset-x-0 top-full z-30 mt-1.5 flex max-h-72 flex-col gap-0.5 overflow-y-auto rounded-card border border-grey-250 bg-white p-2 text-charcoal"
                        >
                            @forelse ($candidates as $candidate)
                                <button
                                    type="button"
                                    wire:key="team-candidate-{{ $candidate->user_id }}"
                                    wire:click="addMember({{ $candidate->user_id }})"
                                    @click="$refs.search.focus()"
                                    class="flex min-h-10 w-full items-center gap-3 rounded-field px-3 py-2 text-left hover:bg-grey-100 focus-visible:bg-grey-100"
                                >
                                    <x-megis.icon name="plus" class="shrink-0 text-grey-600" />
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-semibold">{{ $candidate->user->name }}</span>
                                        @if ($candidate->user->name !== $candidate->user->email)
                                            <span class="block text-sm break-all text-grey-700">{{ $candidate->user->email }}</span>
                                        @endif
                                    </span>
                                </button>
                            @empty
                                <p class="px-3 py-2 text-sm text-grey-700">{{ $hasCandidates ? __('admin.common.empty') : __('admin.teams.no_candidates') }}</p>
                            @endforelse
                        </div>
                    </div>
                    <p id="team-member-search-help" class="text-sm text-grey-600">{{ __('admin.teams.members_help') }}</p>
                </div>
            @endunless
        </fieldset>

        <div class="flex flex-wrap gap-3">
            <x-ui.button type="submit">{{ __('admin.common.save') }}</x-ui.button>
            <x-ui.button tone="outline" :href="route('admin.organizations.teams', $organization)">{{ __('admin.common.cancel') }}</x-ui.button>
        </div>
    </x-ui.card>
</div>
