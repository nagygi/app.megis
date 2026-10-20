{{-- Futás (A2, 19 · 19.3–19.5): numbers and states, never answers. --}}
@php
    $start = ($main->starts_on ?? $main->invited_at ?? $main->created_at)->copy()->startOfDay();
    $span = max(1, $start->diffInDays($main->publishes_on));
    $position = fn ($date) => max(0, min(100, round($start->diffInDays($date, false) / $span * 100)));
@endphp

<div class="flex max-w-7xl flex-col gap-6">
    <x-admin.main-diagnosis-tabs :diagnosis="$diagnosis" />

    <x-ui.flash />

    {{-- The threshold (19 · 19.4) --}}
    @if ($thresholdAtRisk)
        <section class="flex flex-col gap-3 rounded-card border-2 border-coral bg-white p-5" aria-labelledby="threshold-title" role="region">
            <h2 id="threshold-title" class="text-lg font-semibold">{{ __('admin.run.threshold.title') }}</h2>
            <p>{{ __('admin.run.threshold.text', ['answered' => $main->calculation['peers_answered'] ?? 0, 'missing' => $missingPeers]) }}</p>
            @if ($composingThreshold)
                <form wire:submit="sendThreshold" class="flex flex-col gap-3">
                    <x-form.input name="thresholdSubject" wire:model="thresholdSubject" :label="__('admin.run.threshold.subject')" required />
                    <x-form.textarea name="thresholdBody" wire:model="thresholdBody" :label="__('admin.run.threshold.body')" rows="4" required />
                    <div class="flex gap-2">
                        <x-ui.button type="submit" size="sm">{{ __('admin.run.threshold.send') }}</x-ui.button>
                        <x-ui.button tone="outline" size="sm" wire:click="$set('composingThreshold', false)">{{ __('admin.common.cancel') }}</x-ui.button>
                    </div>
                </form>
            @else
                <div class="flex flex-wrap gap-2">
                    <x-ui.button size="sm" wire:click="composeThreshold">{{ __('admin.run.threshold.notify') }}</x-ui.button>
                    <x-ui.button tone="outline" size="sm" wire:click="snoozeThreshold">{{ __('admin.run.threshold.wait') }}</x-ui.button>
                    <x-ui.button tone="outline" size="sm" wire:click="overrideThreshold" wire:confirm="{{ __('admin.run.threshold.confirm_without') }}">{{ __('admin.run.threshold.without') }}</x-ui.button>
                </div>
            @endif
        </section>
    @elseif ($thresholdOverridable)
        <section class="flex flex-col gap-3 rounded-card border border-coral bg-white p-5" aria-labelledby="threshold-title" role="region">
            <h2 id="threshold-title" class="text-lg font-semibold">{{ __('admin.run.threshold.below_title') }}</h2>
            <p>{{ __('admin.run.threshold.below_text', ['answered' => $main->calculation['peers_answered'] ?? 0, 'missing' => $missingPeers]) }}</p>
            <x-ui.button tone="outline" size="sm" class="self-start" wire:click="overrideThreshold" wire:confirm="{{ __('admin.run.threshold.confirm_without') }}">{{ __('admin.run.threshold.without') }}</x-ui.button>
        </section>
    @endif

    @if ($main->threshold_overridden_at)
        <p class="flex flex-wrap items-center gap-3 rounded-lg bg-grey-100 px-3 py-2 text-sm">
            {{ __('admin.run.threshold.overridden', ['name' => $thresholdOverrider?->name ?? '–', 'date' => $main->threshold_overridden_at->format('Y.m.d. H:i')]) }}
            @if ($isOpen)
                <x-ui.button tone="outline" size="sm" wire:click="revokeThresholdOverride" wire:confirm="{{ __('admin.run.threshold.confirm_revoke') }}">{{ __('admin.run.threshold.revoke') }}</x-ui.button>
            @endif
        </p>
    @elseif ($main->threshold_snoozed_until && $main->threshold_snoozed_until->isFuture())
        <p class="rounded-lg bg-grey-100 px-3 py-2 text-sm">{{ __('admin.run.threshold.snoozed', ['date' => $main->threshold_snoozed_until->format('Y.m.d.')]) }}</p>
    @endif

    {{-- Időablak (19 · 19.3) --}}
    <section class="flex flex-col gap-3 rounded-card border border-grey-250 bg-white p-5" aria-labelledby="window-title">
        <h2 id="window-title" class="text-lg font-semibold">{{ __('admin.run.window') }}</h2>
        <div class="relative h-3 rounded-pill bg-grey-100" role="img" aria-label="{{ __('admin.run.window_label', ['from' => $start->format('Y.m.d.'), 'to' => $main->publishes_on->format('Y.m.d.'), 'today' => today()->format('Y.m.d.')]) }}">
            <div class="absolute inset-y-0 left-0 rounded-pill bg-green/60" style="width: {{ $position(today()) }}%"></div>
            <div class="absolute inset-y-[-4px] w-0.5 bg-coral-dark" style="left: {{ $position($main->readingStartsOn()) }}%" title="{{ __('admin.main_diagnoses.reading_short', ['date' => $main->readingStartsOn()->format('Y.m.d.')]) }}"></div>
        </div>
        <p class="flex flex-wrap justify-between gap-2 text-sm text-grey-700">
            <span>{{ $start->format('Y.m.d.') }}</span>
            <span>{{ __('admin.main_diagnoses.reading_short', ['date' => $main->readingStartsOn()->format('Y.m.d.')]) }}</span>
            <span>{{ $main->publishes_on->format('Y.m.d.') }}</span>
        </p>
        @if ($isOpen)
            <form wire:submit="changePublishDate" class="flex flex-wrap items-end gap-3">
                <x-form.input name="publishesOn" type="date" wire:model="publishesOn" :label="__('admin.main_diagnoses.publishes_on')" :help="__('admin.run.window_help')" />
                <x-ui.button type="submit" tone="outline" size="sm" class="mb-7">{{ __('admin.common.save') }}</x-ui.button>
            </form>
        @endif
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- A vezető --}}
        <section class="flex flex-col gap-3 rounded-card border border-grey-250 bg-white p-5" aria-labelledby="leader-title">
            <h2 id="leader-title" class="text-lg font-semibold">{{ __('admin.run.leader', ['name' => $main->leader->name]) }}</h2>
            @php($current = $leaderSteps->firstMissing())
            <ol class="flex flex-col gap-1 text-sm">
                @foreach ($leaderSteps->steps as $state)
                    <li class="flex justify-between gap-3">
                        <span>{{ $state->step->label() }}</span>
                        <span @class(['font-semibold' => $current?->step === $state->step])>{{ $state->done ? __('admin.run.steps.done') : ($current?->step === $state->step ? __('admin.run.steps.now') : __('admin.run.steps.left')) }}</span>
                    </li>
                @endforeach
            </ol>
            <p class="text-sm text-grey-700">
                {{ __('admin.run.last_login', ['date' => $main->leader->last_login_at?->format('Y.m.d. H:i') ?? '–']) }}
                · {{ __('admin.run.situations', ['count' => $leaderSituations]) }}
            </p>
            @if ($canRemindLeader)
                <x-ui.button tone="outline" size="sm" class="self-start" wire:click="remindLeader">{{ __('admin.run.remind_leader') }}</x-ui.button>
            @endif
        </section>

        {{-- Társak (Teljes, csapat) --}}
        @if ($main->hasPeers())
            <section class="flex flex-col gap-3 rounded-card border border-grey-250 bg-white p-5" aria-labelledby="peers-title">
                <h2 id="peers-title" class="text-lg font-semibold">{{ __('admin.run.peers') }}</h2>
                <p class="text-sm">
                    @foreach ($peerCounts as $state => $count)
                        <span class="mr-3">{{ __('admin.run.peer_states.'.$state) }}: <strong>{{ $count }}</strong></span>
                    @endforeach
                </p>
                @if ($missingPeers > 0)
                    <p class="text-sm font-semibold text-coral-ink">{{ __('admin.run.threshold_missing', ['count' => $missingPeers]) }}</p>
                @endif
                <details class="text-sm">
                    <summary class="cursor-pointer font-semibold">{{ __('admin.run.by_name') }}</summary>
                    <ul class="mt-2 flex flex-col gap-1">
                        @foreach ($peerStates as $entry)
                            <li class="flex justify-between gap-3">
                                <span>{{ $entry['peer']->user->name }}</span>
                                <span>{{ __('admin.run.peer_states.'.$entry['state']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </details>
                @if ($peersToRemind > 0)
                    <x-ui.button tone="outline" size="sm" class="self-start" wire:click="remindPeers">{{ __('admin.run.remind_peers', ['count' => $peersToRemind]) }}</x-ui.button>
                @endif
            </section>
        @endif

        {{-- Folyamatok --}}
        <section class="flex flex-col gap-2 rounded-card border border-grey-250 bg-white p-5" aria-labelledby="processes-title">
            <h2 id="processes-title" class="text-lg font-semibold">{{ __('admin.run.processes') }}</h2>
            <p class="text-sm">
                @foreach ($processCounts as $key => $count)
                    <span class="mr-3">{{ __('admin.run.process_counts.'.$key) }}: <strong>{{ $count }}</strong></span>
                @endforeach
            </p>
            <a href="{{ route('admin.organizations.processes', $diagnosis->organization) }}" class="self-start text-sm font-semibold">{{ __('admin.organizations.tabs.processes') }}</a>
        </section>

        {{-- Anyagok --}}
        <section class="flex flex-col gap-2 rounded-card border border-grey-250 bg-white p-5" aria-labelledby="materials-title">
            <h2 id="materials-title" class="text-lg font-semibold">{{ __('admin.run.materials') }}</h2>
            <p class="text-sm">
                @foreach ($materialCounts as $key => $count)
                    <span @class(['mr-3', 'font-semibold text-coral-ink' => in_array($key, ['failed', 'unclassified'], true) && $count > 0])>{{ __('admin.run.material_counts.'.$key) }}: <strong>{{ $count }}</strong></span>
                @endforeach
            </p>
            <a href="{{ route('admin.inbox.index') }}" class="self-start text-sm font-semibold">{{ __('admin.nav.inbox') }}</a>
        </section>

        {{-- Naptár --}}
        <section class="flex flex-col gap-2 rounded-card border border-grey-250 bg-white p-5" aria-labelledby="calendar-title">
            <h2 id="calendar-title" class="text-lg font-semibold">{{ __('admin.processes.calendar') }}</h2>
            @forelse ($connections as $connection)
                <p class="text-sm">
                    {{ $connection->providerLabel() }} · {{ $connection->user?->name }} · {{ __('calendar.statuses.'.$connection->status) }}
                    @if ($connection->metadata_expires_at)
                        · {{ __('calendar.profile.metadata_until', ['date' => $connection->metadata_expires_at->format('Y.m.d.')]) }}
                    @endif
                </p>
            @empty
                <p class="text-sm text-grey-600">{{ __('admin.processes.no_calendar') }}</p>
            @endforelse
        </section>
    </div>

    {{-- Beszélgetések a mégisszel (19 · 19.5) --}}
    <section class="flex flex-col gap-3 rounded-card border border-grey-250 bg-white p-5" aria-labelledby="conversations-title">
        <h2 id="conversations-title" class="text-lg font-semibold">{{ __('admin.run.conversations.title') }}</h2>
        @if ($conversations->isEmpty())
            <p class="text-sm text-grey-600">{{ __('admin.run.conversations.empty') }}</p>
        @else
            <p class="text-sm">{{ __('admin.run.conversations.summary', ['held' => $conversations->filter(fn ($item) => $item->held_at->isPast())->count()]) }}</p>
            <ul class="flex flex-col gap-1 text-sm">
                @foreach ($conversations as $item)
                    <li wire:key="conversation-{{ $item->id }}" class="flex flex-wrap items-center justify-between gap-2">
                        <span>
                            {{ $item->held_at->format('Y.m.d. H:i') }} · {{ $item->withLabel() }}
                            @if ($item->admin) · {{ $item->admin->name }} @endif
                            @if ($item->note) · <span class="text-grey-700">{{ $item->note }}</span> @endif
                        </span>
                        <button type="button" wire:click="removeConversation({{ $item->id }})" wire:confirm="{{ __('admin.run.conversations.confirm_remove') }}" class="text-sm underline">{{ __('admin.run.conversations.remove') }}</button>
                    </li>
                @endforeach
            </ul>
        @endif

        <form wire:submit="addConversation" class="grid gap-3 md:grid-cols-3">
            <x-form.input name="conversation.held_at" type="datetime-local" wire:model="conversation.held_at" :label="__('admin.run.conversations.held_at')" required />
            <x-form.select name="conversation.with_kind" wire:model.live="conversation.with_kind" :label="__('admin.run.conversations.with_kind')">
                @foreach (\App\Models\MegisConversation::WITH as $kind)
                    <option value="{{ $kind }}">{{ __('admin.run.conversations.with.'.$kind) }}</option>
                @endforeach
            </x-form.select>
            @if ($conversation['with_kind'] === 'external')
                <x-form.input name="conversation.with_name" wire:model="conversation.with_name" :label="__('admin.run.conversations.with_name')" required />
            @else
                <x-form.select name="conversation.with_user_id" wire:model="conversation.with_user_id" :label="__('admin.run.conversations.with_label')">
                    <option value="">–</option>
                    @foreach ($conversation['with_kind'] === 'leader' ? collect([$main->leader]) : $peerStates->pluck('peer.user') as $person)
                        <option value="{{ $person->id }}">{{ $person->name }}</option>
                    @endforeach
                </x-form.select>
            @endif
            <x-form.select name="conversation.admin_id" wire:model="conversation.admin_id" :label="__('admin.run.conversations.admin')">
                <option value="">–</option>
                @foreach ($admins as $admin)
                    <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                @endforeach
            </x-form.select>
            <div class="md:col-span-2">
                <x-form.input name="conversation.note" wire:model="conversation.note" :label="__('admin.run.conversations.note')" />
            </div>
            <div class="flex flex-wrap items-center gap-3 md:col-span-3">
                <x-ui.button type="submit" tone="outline" size="sm">{{ __('admin.run.conversations.add') }}</x-ui.button>
                <a href="{{ route('admin.diagnoses.upload', ['diagnozis' => $diagnosis->id]) }}" class="text-sm font-semibold">{{ __('admin.run.conversations.upload') }}</a>
            </div>
        </form>
    </section>

    @if ($main->stage === \App\Enums\MainDiagnosisStage::Running)
        <div class="flex flex-wrap items-center gap-3">
            @if ($processingCount > 0)
                <x-ui.button tone="coral" wire:click="startReading" wire:confirm="{{ __('admin.run.confirm_reading', ['count' => $processingCount]) }}">{{ __('admin.run.start_reading') }}</x-ui.button>
            @elseif ($thresholdOverridable)
                <x-ui.button tone="coral" wire:click="startReading" wire:confirm="{{ __('admin.run.confirm_reading_below_threshold', ['count' => $missingPeers]) }}">{{ __('admin.run.start_reading') }}</x-ui.button>
            @else
                <x-ui.button tone="coral" wire:click="startReading" wire:confirm="{{ __('admin.run.confirm_reading_plain') }}">{{ __('admin.run.start_reading') }}</x-ui.button>
            @endif
            <span class="text-sm text-grey-700">{{ __('admin.run.start_reading_help') }}</span>
        </div>
    @endif
</div>
