{{--
    A1 · starting a main diagnosis (17 · 17.2) and its Beállítás tab. What the
    leader sees, the admin checks with "Belépek a nevében". One form in
    cards, one question each (who for, whom, who answers, when, what the
    leader sees), closing in a card of its own, and the buttons in the last
    card, a bar that stays at the bottom of the screen.
--}}
@php($subjectEnum = \App\Enums\MainDiagnosisSubject::tryFrom($subject))

<div class="flex flex-col gap-6">
    @if ($main)
        <x-admin.main-diagnosis-tabs :diagnosis="$main->diagnosis" />
    @endif

    <div class="flex flex-col gap-6">
        <x-ui.flash />

        @unless ($main)
            <x-admin.page-header :title="$title" />
        @endunless

        @if ($main && ! $canEditSettings)
            <x-ui.notice>{{ __('admin.main_diagnoses.locked_notice') }}</x-ui.notice>
        @endif

        <form wire:submit="saveDraft" class="flex flex-col gap-6">
            {{-- Ügyfél és kapcsolattartó --}}
            <x-ui.card as="section" padding="lg" aria-labelledby="section-client" class="flex flex-col gap-5">
                <x-ui.section-header id="section-client" :title="__('admin.main_diagnoses.section_client')" :description="__('admin.main_diagnoses.section_client_help')" />

                <div class="flex max-w-3xl flex-col gap-4">
                    {{-- Név: offered from the team, the product and the start until the admin writes their own (17 · 17.1). --}}
                    @if ($organization && $hasFlow)
                        <div class="flex flex-col gap-2">
                            <x-form.input
                                name="name"
                                wire:model.blur="name"
                                :label="__('admin.main_diagnoses.name')"
                                :help="$main ? null : __($subjectEnum === \App\Enums\MainDiagnosisSubject::Team ? 'admin.main_diagnoses.name_help' : 'admin.main_diagnoses.name_help_person')"
                            />
                            @if ($nameEdited && ! $main)
                                <x-ui.button tone="ghost" size="sm" icon="refresh" class="-ml-1.5 self-start" wire:click="resetName">{{ __('admin.main_diagnoses.name_reset') }}</x-ui.button>
                            @endif
                        </div>
                    @endif

                    @if ($main)
                        <div class="flex flex-col gap-1">
                            <span class="text-sm font-medium">{{ __('admin.main_diagnoses.organization') }}</span>
                            <span>{{ $organization->name }}</span>
                            <p class="text-sm text-grey-700">{{ __('admin.main_diagnoses.organization_fixed') }}</p>
                        </div>
                    @else
                        <x-form.select name="organization_id" wire:model.live="organization_id" :label="__('admin.main_diagnoses.organization')" required>
                            <option value="">{{ __('admin.diagnoses.organization_select') }}</option>
                            <x-admin.organization-options :organizations="$organizations" />
                        </x-form.select>
                    @endif

                    @if ($organization)
                        <x-form.select name="contact_admin_id" wire:model.live="contact_admin_id" :label="__('admin.main_diagnoses.contact')" :help="__('admin.main_diagnoses.contact_help')" :info="__('admin.main_diagnoses.contact_info')">
                            <option value="">{{ __('admin.main_diagnoses.contact_none') }}</option>
                            @foreach ($admins as $admin)
                                <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                            @endforeach
                        </x-form.select>

                        <div class="flex flex-col gap-1">
                            <span class="text-sm font-medium">{{ __('admin.main_diagnoses.product') }}</span>
                            <span>
                                {{ $productName ?? '–' }}
                                · <a href="{{ route('admin.organizations.edit', $organization) }}">{{ __('admin.main_diagnoses.edit_organization') }}</a>
                            </span>
                            @if (! $hasFlow)
                                <x-ui.notice tone="error" class="mt-1">{{ __('admin.main_diagnoses.no_flow') }}</x-ui.notice>
                            @endif
                        </div>
                    @endif
                </div>
            </x-ui.card>

            @if ($organization && $hasFlow)
                {{-- Kit diagnosztizálunk? Az alany, a csapat és a vezető. --}}
                <x-ui.card as="section" padding="lg" aria-labelledby="section-subject" class="flex flex-col gap-5">
                    <x-ui.section-header id="section-subject" :title="__('admin.main_diagnoses.subject')" :description="__('admin.main_diagnoses.subject_section_help')" />

                    <div class="flex max-w-3xl flex-col gap-5">
                        @if ($organization->isIndividual())
                            <p>{{ __('enums.main_diagnosis_subject.individual') }} · <span class="text-grey-700">{{ __('admin.main_diagnoses.subject_individual_help') }}</span></p>
                        @elseif ($main)
                            <p><span class="text-sm font-medium">{{ __('admin.main_diagnoses.subject_kind') }}:</span> {{ $subjectEnum?->label() }}</p>
                        @else
                            <x-form.radio-group
                                name="subject"
                                :label="__('admin.main_diagnoses.subject_kind')"
                                :options="collect([\App\Enums\MainDiagnosisSubject::Team, \App\Enums\MainDiagnosisSubject::Person])
                                    ->mapWithKeys(fn ($option) => [$option->value => ['label' => $option->label(), 'help' => __('admin.main_diagnoses.subject_help.'.$option->value)]])
                                    ->all()"
                                wire:model.live="subject"
                            />
                        @endif

                        {{-- Csapat --}}
                        @if ($subjectEnum === \App\Enums\MainDiagnosisSubject::Team)
                            <div class="flex flex-col gap-3">
                                <h3 class="font-semibold text-charcoal">{{ __('admin.main_diagnoses.team') }}</h3>
                                @if ($main)
                                    <p>{{ $main->diagnosis->team?->name }} <span class="text-sm text-grey-700">· {{ __('admin.main_diagnoses.team_fixed') }}</span></p>
                                @else
                                    @if (! $newTeam)
                                        <x-form.select name="team_id" wire:model.live="team_id" :label="__('admin.main_diagnoses.team')" required>
                                            <option value="">{{ __('admin.diagnoses.team_select') }}</option>
                                            @foreach ($teams as $teamOption)
                                                <option value="{{ $teamOption->id }}">{{ $teamOption->name }}</option>
                                            @endforeach
                                        </x-form.select>
                                    @else
                                        <x-form.input name="new_team_name" wire:model.live.debounce.500ms="new_team_name" :label="__('admin.main_diagnoses.new_team')" required />
                                    @endif
                                    <x-ui.button tone="ghost" size="sm" :icon="$newTeam ? null : 'plus'" class="-ml-1.5 self-start" wire:click="$toggle('newTeam')">
                                        {{ $newTeam ? __('admin.main_diagnoses.pick_team') : __('admin.main_diagnoses.add_team') }}
                                    </x-ui.button>
                                @endif
                            </div>
                        @endif

                        {{-- Vezető --}}
                        <div class="flex flex-col gap-3">
                            <h3 class="flex items-center gap-1.5 font-semibold text-charcoal">
                                {{ $subjectEnum === \App\Enums\MainDiagnosisSubject::Person ? __('admin.main_diagnoses.person') : __('admin.main_diagnoses.leader') }}
                                <x-ui.info :text="__('admin.main_diagnoses.leader_info')" />
                            </h3>

                            @if ($organization->isIndividual())
                                <p>{{ $organization->users()->first()?->name }}</p>
                            @elseif ($main)
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <p>{{ $main->leader->name }} · {{ $main->leader->email }}</p>
                                    @if ($canEditSettings && ! $changingLeader)
                                        <x-ui.button tone="ghost" size="sm" wire:click="$set('changingLeader', true)">{{ __('admin.common.edit') }}</x-ui.button>
                                    @endif
                                </div>
                                {{-- A replaced leader gets no e-mail: A1 sends the invitation here (17 · 17.7). --}}
                                @if ($leaderAwaitsInvitation)
                                    <div class="flex flex-col items-start gap-2">
                                        <p class="text-sm text-grey-700">{{ __('admin.main_diagnoses.leader_not_invited', ['name' => $main->leader->name]) }}</p>
                                        <x-ui.button size="sm" icon="mail" wire:click="inviteLeader">{{ __('admin.main_diagnoses.invite_new_leader') }}</x-ui.button>
                                    </div>
                                @endif
                                @if ($canEditSettings && $changingLeader)
                                    <x-form.inline class="rounded-field bg-grey-100 p-4">
                                        <x-form.select name="replacementLeaderId" wire:model.live="replacementLeaderId" :label="__('admin.main_diagnoses.new_leader')" :help="__('admin.main_diagnoses.change_leader_help')">
                                            <option value="">{{ __('admin.diagnoses.user_select') }}</option>
                                            @foreach ($leaderOptions as $option)
                                                @continue($option->id === $main->leader_id)
                                                <option value="{{ $option->id }}">{{ $option->name }} · {{ $option->email }}</option>
                                            @endforeach
                                        </x-form.select>
                                        <x-slot:actions>
                                            <x-ui.button tone="outline" wire:click="changeLeader" wire:confirm="{{ __('admin.main_diagnoses.change_leader_confirm') }}">{{ __('admin.main_diagnoses.change_leader') }}</x-ui.button>
                                            <x-ui.button tone="ghost" wire:click="cancelLeaderChange">{{ __('admin.common.cancel') }}</x-ui.button>
                                        </x-slot:actions>
                                    </x-form.inline>
                                    @if ($replacementNotOrganizationLeader)
                                        <x-ui.notice tone="warning" role="status">{{ __('admin.main_diagnoses.leader_not_organization_leader') }}</x-ui.notice>
                                    @endif
                                @endif
                            @else
                                @if (! $newLeader || $subjectEnum !== \App\Enums\MainDiagnosisSubject::Team)
                                    <x-form.select name="leader_id" wire:model.live="leader_id" :label="$subjectEnum === \App\Enums\MainDiagnosisSubject::Person ? __('admin.main_diagnoses.person') : __('admin.main_diagnoses.leader')" required>
                                        <option value="">{{ __('admin.diagnoses.user_select') }}</option>
                                        @foreach ($leaderOptions as $option)
                                            <option value="{{ $option->id }}">{{ $option->name }} · {{ $option->email }}</option>
                                        @endforeach
                                    </x-form.select>
                                @else
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <x-form.input name="leader_last_name" wire:model="leader_last_name" :label="__('admin.members.last_name')" required />
                                        <x-form.input name="leader_first_name" wire:model="leader_first_name" :label="__('admin.members.first_name')" required />
                                    </div>
                                    <x-form.input name="leader_email" type="email" wire:model="leader_email" :label="__('admin.members.email')" :help="__('admin.main_diagnoses.new_leader_help')" required />
                                @endif
                                @if ($leaderNotOrganizationLeader)
                                    <x-ui.notice tone="warning" role="status">{{ __('admin.main_diagnoses.leader_not_organization_leader') }}</x-ui.notice>
                                @endif
                                @if ($subjectEnum === \App\Enums\MainDiagnosisSubject::Team)
                                    <x-ui.button tone="ghost" size="sm" :icon="$newLeader ? null : 'plus'" class="-ml-1.5 self-start" wire:click="$toggle('newLeader')">
                                        {{ $newLeader ? __('admin.main_diagnoses.pick_leader') : __('admin.main_diagnoses.add_leader') }}
                                    </x-ui.button>
                                @endif
                            @endif
                        </div>
                    </div>
                </x-ui.card>

                {{-- Kik adnak visszajelzést? A társak (Teljes, csapat) és az Ügyfélmérés (35 · 35.4). --}}
                @if ($hasPeers || $externalClients->isNotEmpty())
                    <x-ui.card as="section" padding="lg" aria-labelledby="section-feedback" class="flex flex-col gap-5">
                        <x-ui.section-header id="section-feedback" :title="__('admin.main_diagnoses.feedback_section')" :description="__('admin.main_diagnoses.feedback_section_help')" />

                        <div class="flex max-w-3xl flex-col gap-5">
                            @if ($hasPeers)
                                <fieldset class="flex min-w-0 flex-col gap-3">
                                    <legend class="mb-2 font-semibold text-charcoal">{{ __('admin.main_diagnoses.peers') }}</legend>
                                    <p class="text-sm text-grey-700">{{ __('admin.main_diagnoses.peers_help') }}</p>
                                    @forelse ($peerCandidates as $candidate)
                                        <x-form.checkbox
                                            :name="'peers.'.$candidate->id"
                                            wire:model.live="peers.{{ $candidate->id }}"
                                            :label="$candidate->name.' · '.$candidate->email"
                                        />
                                    @empty
                                        <p class="text-sm text-grey-700">{{ __('admin.main_diagnoses.peers_empty') }}</p>
                                    @endforelse
                                    @if ($peerCandidates->filter(fn ($candidate) => $peers[$candidate->id] ?? false)->count() < \App\Services\Diagnoses\ThresholdPolicy::PEERS)
                                        {{-- Starting is still allowed, so a warning; it changes with every tick, hence the polite status. --}}
                                        <x-ui.notice tone="warning" role="status">{{ __('admin.main_diagnoses.peers_below_threshold') }}</x-ui.notice>
                                    @endif
                                </fieldset>
                            @endif

                            @if ($externalClients->isNotEmpty())
                                <fieldset class="flex min-w-0 flex-col gap-3" @disabled(! $canEditSettings)>
                                    <legend class="mb-2 font-semibold text-charcoal">{{ __('admin.main_diagnoses.external_clients') }}</legend>
                                    <p class="text-sm text-grey-700">{{ __('admin.main_diagnoses.external_clients_help') }}</p>
                                    @foreach ($externalClients as $externalClient)
                                        <x-form.checkbox
                                            name="externalClientIds"
                                            :id="'main-external-client-'.$externalClient->id"
                                            wire:model="externalClientIds"
                                            :value="$externalClient->id"
                                            :label="$externalClient->isActive() ? $externalClient->name : $externalClient->name.' · '.__('admin.organizations.inactive')"
                                        />
                                    @endforeach
                                </fieldset>
                            @endif
                        </div>
                    </x-ui.card>
                @endif

                {{-- Ütemezés: what each day is for, where it shows, and that none of them acts on its own (17 · 17.2). --}}
                <x-ui.card as="section" padding="lg" aria-labelledby="section-schedule" class="flex flex-col gap-5">
                    <x-ui.section-header id="section-schedule" :title="__('admin.main_diagnoses.window')" :description="__('admin.main_diagnoses.window_help')" />

                    <fieldset class="flex min-w-0 flex-col gap-5" aria-labelledby="section-schedule" @disabled(! $canEditSettings)>
                        <div class="grid gap-4 md:grid-cols-3">
                            <x-form.input name="starts_on" type="date" wire:model.live="starts_on" :label="__('admin.main_diagnoses.starts_on')" :help="__('admin.main_diagnoses.starts_on_help')" :info="__('admin.main_diagnoses.starts_on_info')" />
                            <x-form.input name="publishes_on" type="date" wire:model.live="publishes_on" :label="__('admin.main_diagnoses.publishes_on')" :help="__('admin.main_diagnoses.publishes_on_help')" :info="__('admin.main_diagnoses.publishes_on_info')" required />
                            <div class="flex flex-col gap-1.5">
                                <span class="flex items-center gap-1.5">
                                    <span class="text-sm font-medium text-charcoal">{{ __('admin.main_diagnoses.reading_on') }}</span>
                                    <x-ui.info :text="__('admin.main_diagnoses.reading_on_info')" />
                                </span>
                                <span class="flex min-h-11 items-center rounded-field bg-grey-100 px-4 text-charcoal">{{ $readingStartsOn ?? '–' }}</span>
                                <p class="text-sm text-grey-600">{{ __('admin.main_diagnoses.reading_on_help', ['days' => config('diagnosis.reading_offset_days')]) }}</p>
                            </div>
                        </div>

                        {{-- How the time splits up to publishing: the leader, the peers (or the materials), the reading. --}}
                        @if ($schedule)
                            <div class="flex flex-col gap-2">
                                <p class="text-sm font-medium text-charcoal">{{ __('admin.main_diagnoses.schedule.title') }}</p>
                                <ol class="flex gap-1 overflow-x-auto pb-1">
                                    @foreach ($schedule as $segment)
                                        <li class="flex min-w-28 flex-col gap-1" style="flex: {{ max(1, $segment['days']) }} 1 0%">
                                            <span @class([
                                                'h-2 rounded-pill',
                                                'bg-yellow' => $segment['key'] === 'leader',
                                                'bg-green-muted' => in_array($segment['key'], ['peers', 'materials', 'filling'], true),
                                                'bg-coral-light' => $segment['key'] === 'reading',
                                            ])></span>
                                            <span class="text-sm font-medium text-charcoal">{{ __('admin.main_diagnoses.schedule.'.$segment['key']) }}</span>
                                            <span class="text-xs text-grey-700">{{ trans_choice('admin.main_diagnoses.schedule.days', $segment['days'], ['count' => $segment['days']]) }} · {{ __('admin.main_diagnoses.schedule.until', ['date' => $segment['until']]) }}</span>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif
                    </fieldset>
                </x-ui.card>

                {{-- Mit lát a vezető a riportban? --}}
                @if ($hasPeers)
                    <x-ui.card as="section" padding="lg" aria-labelledby="section-access" class="flex flex-col gap-5">
                        <x-ui.section-header id="section-access" :title="__('admin.main_diagnoses.access')" :description="__('admin.main_diagnoses.access_help')" />
                        <fieldset class="min-w-0 max-w-3xl" aria-labelledby="section-access" @disabled(! $canEditSettings)>
                            <x-form.toggle name="show_peer_quotes" wire:model="show_peer_quotes" :label="__('admin.main_diagnoses.show_peer_quotes')" :help="__('admin.main_diagnoses.show_peer_quotes_help')" />
                        </fieldset>
                    </x-ui.card>
                @endif
            @endif

            {{-- Lezárás: a card of its own above the buttons, so the saving bar stays the last one. Its button does not submit the form. --}}
            @if ($main && $main->stage !== \App\Enums\MainDiagnosisStage::Closed)
                <x-ui.card as="section" padding="lg" aria-labelledby="section-close" class="flex flex-col items-start gap-4">
                    <x-ui.section-header id="section-close" :title="__('admin.main_diagnoses.section_close')" :description="__('admin.main_diagnoses.close_help')" />
                    <x-ui.button
                        tone="danger"
                        size="sm"
                        wire:click="close"
                        wire:confirm="{{ __('admin.main_diagnoses.close_confirm') }}"
                        data-confirm-tone="danger"
                        :data-confirm-label="__('admin.main_diagnoses.close')"
                    >{{ __('admin.main_diagnoses.close') }}</x-ui.button>
                </x-ui.card>
            @endif

            @if ($organization && $hasFlow)
                {{-- The buttons, the last card: they stay at the bottom of the screen while the form scrolls (from a tablet up: on a phone the bar would take a quarter of it). --}}
                @if ($isDraft || $canEditSettings)
                    <x-ui.card padding="sm" class="flex flex-col gap-3 md:sticky md:bottom-4 md:z-20">
                        @if ($blockers !== [] && $isDraft)
                            @if ($main)
                                <p class="text-sm text-coral-ink">{{ __('admin.main_diagnoses.invitation_blocked') }}</p>
                            @else
                                <x-ui.notice tone="error">
                                    <ul class="flex flex-col gap-1">
                                        @foreach ($blockers as $blocker)
                                            <li>{{ $blocker }}</li>
                                        @endforeach
                                    </ul>
                                </x-ui.notice>
                            @endif
                        @endif

                        <div class="flex flex-wrap items-center gap-3">
                            @if ($isDraft)
                                <x-ui.button type="button" id="invitation" icon="mail" wire:click="sendInvitation" :disabled="$blockers !== []">{{ __('admin.main_diagnoses.send_invitation') }}</x-ui.button>
                                <x-ui.button type="submit" tone="outline">{{ __('admin.main_diagnoses.save_draft') }}</x-ui.button>
                            @else
                                <x-ui.button type="submit">{{ __('admin.common.save') }}</x-ui.button>
                            @endif
                            <x-ui.button :tone="$isDraft ? 'ghost' : 'outline'" :href="match (true) {
                                    $main !== null => route('admin.diagnoses.show', $main->diagnosis),
                                    $organization !== null => route('admin.organizations.diagnoses', $organization),
                                    default => route('admin.diagnoses.index'),
                                }">{{ __('admin.common.cancel') }}</x-ui.button>
                            @if ($isDraft)
                                <span class="text-sm text-grey-700">{{ __('admin.main_diagnoses.invitation_note') }}</span>
                            @endif
                        </div>
                    </x-ui.card>
                @endif
            @endif
        </form>
    </div>
</div>
