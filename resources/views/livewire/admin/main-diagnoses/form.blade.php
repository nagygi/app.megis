{{-- A1 · starting a main diagnosis (17 · 17.2), with the leader's V1 as a live preview. --}}
@php($subjectEnum = \App\Enums\MainDiagnosisSubject::tryFrom($subject))

<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="$title">
        @if ($main)
            <x-slot:actions>
                <x-ui.badge tone="dark">{{ $main->stage->label() }}</x-ui.badge>
            </x-slot:actions>
        @endif
    </x-admin.page-header>

    @if ($main)
        <x-admin.main-diagnosis-tabs :diagnosis="$main->diagnosis" class="mb-6" />
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <form wire:submit="saveDraft" class="flex flex-col gap-6 rounded-card border border-grey-250 bg-white p-6">
            {{-- Ügyfél --}}
            <fieldset class="flex flex-col gap-4">
                <legend class="mb-2 text-lg font-semibold">{{ __('admin.main_diagnoses.section_client') }}</legend>

                @if ($main)
                    <div class="flex flex-col gap-1">
                        <span class="text-sm font-medium">{{ __('admin.main_diagnoses.organization') }}</span>
                        <span>{{ $organization->name }}</span>
                        <p class="text-sm text-grey-600">{{ __('admin.main_diagnoses.organization_fixed') }}</p>
                    </div>
                @else
                    <x-form.select name="organization_id" wire:model.live="organization_id" :label="__('admin.main_diagnoses.organization')" required>
                        <option value="">{{ __('admin.diagnoses.organization_select') }}</option>
                        @foreach ($organizations as $option)
                            <option value="{{ $option->id }}">{{ $option->name }}</option>
                        @endforeach
                    </x-form.select>
                @endif

                @if ($organization)
                    <x-form.select name="contact_admin_id" wire:model.live="contact_admin_id" :label="__('admin.main_diagnoses.contact')" :help="__('admin.main_diagnoses.contact_help')">
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
                            <p class="text-sm font-semibold text-coral-dark" role="alert">{{ __('admin.main_diagnoses.no_flow') }}</p>
                        @endif
                    </div>
                @endif
            </fieldset>

            @if ($organization && $hasFlow)
                {{-- Kit diagnosztizálunk? --}}
                <fieldset class="flex flex-col gap-3">
                    <legend class="mb-2 text-lg font-semibold">{{ __('admin.main_diagnoses.subject') }}</legend>

                    @if ($organization->isIndividual())
                        <p>{{ __('enums.main_diagnosis_subject.individual') }} · <span class="text-grey-600">{{ __('admin.main_diagnoses.subject_individual_help') }}</span></p>
                    @elseif ($main)
                        <p>{{ $subjectEnum?->label() }}</p>
                    @else
                        @foreach ([\App\Enums\MainDiagnosisSubject::Team, \App\Enums\MainDiagnosisSubject::Person] as $option)
                            <label class="flex items-start gap-2">
                                <input type="radio" name="subject" value="{{ $option->value }}" wire:model.live="subject" class="mt-1 size-4 accent-charcoal">
                                <span>
                                    <span class="font-medium">{{ $option->label() }}</span>
                                    <span class="block text-sm text-grey-600">{{ __('admin.main_diagnoses.subject_help.'.$option->value) }}</span>
                                </span>
                            </label>
                        @endforeach
                        <x-form.error name="subject" id="subject" />
                    @endif
                </fieldset>

                {{-- Csapat --}}
                @if ($subjectEnum === \App\Enums\MainDiagnosisSubject::Team)
                    <fieldset class="flex flex-col gap-3">
                        <legend class="mb-2 text-lg font-semibold">{{ __('admin.main_diagnoses.team') }}</legend>
                        @if ($main)
                            <p>{{ $main->diagnosis->team?->name }} <span class="text-sm text-grey-600">· {{ __('admin.main_diagnoses.team_fixed') }}</span></p>
                        @else
                            @if (! $newTeam)
                                <x-form.select name="team_id" wire:model.live="team_id" :label="__('admin.main_diagnoses.team')" required>
                                    <option value="">{{ __('admin.diagnoses.team_select') }}</option>
                                    @foreach ($teams as $teamOption)
                                        <option value="{{ $teamOption->id }}">{{ $teamOption->name }}</option>
                                    @endforeach
                                </x-form.select>
                            @else
                                <x-form.input name="new_team_name" wire:model="new_team_name" :label="__('admin.main_diagnoses.new_team')" required />
                            @endif
                            <button type="button" wire:click="$toggle('newTeam')" class="self-start text-sm font-semibold underline">
                                {{ $newTeam ? __('admin.main_diagnoses.pick_team') : __('admin.main_diagnoses.add_team') }}
                            </button>
                        @endif
                    </fieldset>
                @endif

                {{-- Vezető --}}
                <fieldset class="flex flex-col gap-3">
                    <legend class="mb-2 text-lg font-semibold">
                        {{ $subjectEnum === \App\Enums\MainDiagnosisSubject::Person ? __('admin.main_diagnoses.person') : __('admin.main_diagnoses.leader') }}
                    </legend>

                    @if ($organization->isIndividual())
                        <p>{{ $organization->users()->first()?->name }}</p>
                    @elseif ($main)
                        <p>{{ $main->leader->name }} · {{ $main->leader->email }}</p>
                        @if ($canEditSettings)
                            <div class="flex flex-wrap items-end gap-3 rounded-xl bg-grey-100 p-4">
                                <div class="min-w-56 flex-1">
                                    <x-form.select name="replacementLeaderId" wire:model="replacementLeaderId" :label="__('admin.main_diagnoses.new_leader')" :help="__('admin.main_diagnoses.change_leader_help')">
                                        <option value="">{{ __('admin.diagnoses.user_select') }}</option>
                                        @foreach ($leaderOptions as $option)
                                            @continue($option->id === $main->leader_id)
                                            <option value="{{ $option->id }}">{{ $option->name }} · {{ $option->email }}</option>
                                        @endforeach
                                    </x-form.select>
                                </div>
                                <x-ui.button tone="outline" wire:click="changeLeader" wire:confirm="{{ __('admin.main_diagnoses.change_leader_confirm') }}">{{ __('admin.main_diagnoses.change_leader') }}</x-ui.button>
                            </div>
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
                        @if ($subjectEnum === \App\Enums\MainDiagnosisSubject::Team)
                            <button type="button" wire:click="$toggle('newLeader')" class="self-start text-sm font-semibold underline">
                                {{ $newLeader ? __('admin.main_diagnoses.pick_leader') : __('admin.main_diagnoses.add_leader') }}
                            </button>
                        @endif
                    @endif
                </fieldset>

                {{-- Társak: Teljes, csapat --}}
                @if ($hasPeers)
                    <fieldset class="flex flex-col gap-3">
                        <legend class="mb-2 text-lg font-semibold">{{ __('admin.main_diagnoses.peers') }}</legend>
                        <p class="text-sm text-grey-600">{{ __('admin.main_diagnoses.peers_help') }}</p>
                        @forelse ($peerCandidates as $candidate)
                            <x-form.checkbox
                                :name="'peers.'.$candidate->id"
                                wire:model.live="peers.{{ $candidate->id }}"
                                :label="$candidate->name.' · '.$candidate->email"
                            />
                        @empty
                            <p class="text-sm text-grey-600">{{ __('admin.main_diagnoses.peers_empty') }}</p>
                        @endforelse
                    </fieldset>
                @endif

                {{-- Időablak --}}
                <fieldset class="flex flex-col gap-3" @disabled(! $canEditSettings)>
                    <legend class="mb-2 text-lg font-semibold">{{ __('admin.main_diagnoses.window') }}</legend>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-form.input name="starts_on" type="date" wire:model.live="starts_on" :label="__('admin.main_diagnoses.starts_on')" :help="__('admin.main_diagnoses.starts_on_help')" />
                        <x-form.input name="publishes_on" type="date" wire:model.live="publishes_on" :label="__('admin.main_diagnoses.publishes_on')" required />
                    </div>
                    @if ($readingStartsOn)
                        <p class="text-sm text-grey-600">{{ __('admin.main_diagnoses.reading_info', ['date' => $readingStartsOn, 'days' => config('diagnosis.reading_offset_days')]) }}</p>
                    @endif
                </fieldset>

                {{-- Hozzáférés --}}
                @if ($hasPeers)
                    <fieldset class="flex flex-col gap-3" @disabled(! $canEditSettings)>
                        <legend class="mb-2 text-lg font-semibold">{{ __('admin.main_diagnoses.access') }}</legend>
                        <x-form.toggle name="show_peer_quotes" wire:model="show_peer_quotes" :label="__('admin.main_diagnoses.show_peer_quotes')" :help="__('admin.main_diagnoses.show_peer_quotes_help')" />
                    </fieldset>
                @endif

                <x-form.input name="name" wire:model="name" :label="__('admin.main_diagnoses.name')" :help="__('admin.main_diagnoses.name_help')" />

                @if ($blockers !== [] && $isDraft)
                    <ul class="flex flex-col gap-1 rounded-xl bg-coral-light p-4 text-sm text-black" role="status">
                        @foreach ($blockers as $blocker)
                            <li>{{ $blocker }}</li>
                        @endforeach
                    </ul>
                @endif

                <div class="flex flex-wrap gap-3">
                    @if ($isDraft)
                        <x-ui.button type="button" wire:click="sendInvitation" :disabled="$blockers !== []">{{ __('admin.main_diagnoses.send_invitation') }}</x-ui.button>
                        <x-ui.button type="submit" tone="outline">{{ __('admin.main_diagnoses.save_draft') }}</x-ui.button>
                    @elseif ($canEditSettings)
                        <x-ui.button type="submit">{{ __('admin.common.save') }}</x-ui.button>
                    @endif
                    <x-ui.button tone="outline" :href="$main ? route('admin.diagnoses.show', $main->diagnosis) : route('admin.diagnoses.index')">{{ __('admin.common.cancel') }}</x-ui.button>
                </div>

                @if ($main && $main->stage !== \App\Enums\MainDiagnosisStage::Closed)
                    <div class="border-t border-grey-200 pt-4">
                        <x-ui.button tone="outline" size="sm" wire:click="close" wire:confirm="{{ __('admin.main_diagnoses.close_confirm') }}">{{ __('admin.main_diagnoses.close') }}</x-ui.button>
                        <p class="mt-2 text-sm text-grey-600">{{ __('admin.main_diagnoses.close_help') }}</p>
                    </div>
                @endif
            @endif
        </form>

        {{-- Amit a vezető lát (V1) --}}
        <aside class="flex flex-col gap-4 self-start rounded-card bg-charcoal p-6 text-cream lg:sticky lg:top-6" aria-labelledby="preview-title">
            <p id="preview-title" class="text-sm opacity-90">{{ __('admin.main_diagnoses.preview_title') }}</p>
            @if ($preview === [])
                <p class="opacity-90">{{ __('admin.main_diagnoses.preview_empty') }}</p>
            @else
                <div>
                    <p class="text-xl font-extrabold uppercase">{{ __('diagnosis.what_awaits') }}</p>
                    <p>{{ trans_choice('diagnosis.steps_lead', count($preview), ['count' => count($preview)]) }}</p>
                    <p class="text-sm opacity-90">
                        {{ $productName }} ·
                        {{ __('diagnosis.minutes_until', ['minutes' => array_sum(array_column($preview, 'minutes'))]) }}
                        {{ filled($starts_on) ? rescue(fn () => today()->parse($starts_on)->format('m.d.').'-ig', '', false) : __('diagnosis.date_open') }}
                    </p>
                </div>
                <ol class="flex flex-col gap-2">
                    @foreach ($preview as $index => $step)
                        <li class="flex items-center gap-3">
                            <span class="grid size-6 shrink-0 place-items-center rounded-pill text-xs font-bold {{ $index === 0 ? 'bg-coral text-black' : 'bg-white/15' }}" aria-hidden="true">{{ $index + 1 }}</span>
                            <span class="flex-1">{{ $step['label'] }}@if ($step['readonly']) <span class="text-sm opacity-80">· {{ __('diagnosis.readonly') }}</span>@endif</span>
                            <span class="text-sm opacity-80">{{ __('diagnosis.minutes', ['minutes' => $step['minutes']]) }}</span>
                        </li>
                    @endforeach
                </ol>
                @if ($hasPeers)
                    @php($peerCount = $peerCandidates->filter(fn ($candidate) => $peers[$candidate->id] ?? false)->count())
                    <p class="text-sm opacity-90">{{ trans_choice('admin.main_diagnoses.preview_peers', $peerCount, ['count' => $peerCount]) }}</p>
                @endif
            @endif
        </aside>
    </div>
</div>
