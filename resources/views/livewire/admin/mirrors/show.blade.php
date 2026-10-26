@php
    $status = $mirror->status;
    $state = $mirror->accountState();
    $accountTones = ['claimed' => 'green', 'awaiting_confirmation' => 'yellow', 'awaiting_login' => 'yellow', 'anonymous' => 'muted'];
    $meta = collect([
        $mirror->material?->context->label(),
        $mirror->user?->name ?? $mirror->email,
        $mirror->organization?->name,
    ])->filter()->implode(' · ');
    $expires = $mirror->expires_at?->format('Y.m.d. H:i') ?? __('admin.common.none');
    $curiosity = collect($mirror->curiosity ?? [])->map(fn ($key) => __('mirror.curiosity.'.$key.'.question'));
@endphp

<div class="flex max-w-5xl flex-col gap-6" @if ($status === \App\Enums\MirrorStatus::Processing) wire:poll.5s @endif>
    <x-admin.page-header
        :title="__('admin.mirrors.show_title', ['date' => $mirror->created_at->format('Y.m.d. H:i')])"
        :description="$meta"
        :back="route('admin.mirrors.index')"
        :back-label="__('admin.mirrors.title')"
        :spaced="false"
    >
        <x-slot:badges>
            <x-ui.badge :tone="$status === \App\Enums\MirrorStatus::Failed ? 'coral' : ($status->isDone() ? 'green' : 'yellow')">{{ $status->label() }}</x-ui.badge>
            <x-ui.badge :tone="$accountTones[$state]">{{ __('admin.mirrors.account_states.'.$state) }}</x-ui.badge>
        </x-slot:badges>
        <x-slot:actions>
            <x-ui.button
                tone="danger"
                size="sm"
                wire:click="delete"
                wire:confirm="{{ __($mirror->diagnosis_id ? 'admin.mirrors.confirm_delete_keeps_diagnosis' : 'admin.mirrors.confirm_delete') }}"
                data-confirm-tone="danger"
                :data-confirm-label="__('admin.common.delete')"
            >{{ __('admin.common.delete') }}</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($status === \App\Enums\MirrorStatus::Failed)
        <x-ui.notice tone="error" :title="__('admin.materials.failed')">
            @if ($mirror->material?->error)
                <p class="text-sm break-words">{{ $mirror->material->error }}</p>
            @endif
            @if ($mirror->material)
                <x-slot:action>
                    <x-ui.button tone="outline" size="sm" icon-right="chevron-right" :href="route('admin.materials.show', $mirror->material)">{{ __('admin.materials.open') }}</x-ui.button>
                </x-slot:action>
            @endif
        </x-ui.notice>
    @endif

    <x-ui.card as="section" bordered aria-labelledby="mirror-account">
        <h2 id="mirror-account" class="mb-4 text-lg font-semibold">{{ __('admin.mirrors.section_account') }}</h2>
        <dl class="grid gap-x-6 gap-y-2 sm:grid-cols-[12rem_1fr]">
            <dt class="font-semibold">{{ __('admin.common.email') }}</dt>
            <dd>{{ $mirror->email ?? $mirror->user?->email ?? __('admin.common.none') }}</dd>

            <dt class="font-semibold">{{ __('admin.mirrors.account') }}</dt>
            <dd>
                @switch($state)
                    @case('claimed')
                        {{ __('admin.mirrors.account_texts.claimed', ['date' => $mirror->claimed_at->format('Y.m.d. H:i')]) }}
                        @break
                    @case('awaiting_confirmation')
                        {{ __('admin.mirrors.account_texts.awaiting_confirmation', ['sent' => $mirror->confirmation_sent_at->format('Y.m.d. H:i'), 'expires' => $expires]) }}
                        @break
                    @case('awaiting_login')
                        {{ __('admin.mirrors.account_texts.awaiting_login', ['expires' => $expires]) }}
                        @break
                    @default
                        {{ __('admin.mirrors.account_texts.anonymous', ['expires' => $expires]) }}
                @endswitch
            </dd>

            @if ($mirror->accepted_at)
                <dt class="font-semibold">{{ __('admin.mirrors.accepted') }}</dt>
                <dd>{{ $mirror->accepted_at->format('Y.m.d. H:i') }}</dd>
            @endif
        </dl>

        <div class="mt-4 flex flex-wrap gap-2">
            @if ($account)
                <x-ui.button tone="outline" size="sm" icon-right="chevron-right" :href="route('admin.clients.show', $account)">{{ __('admin.mirrors.open_client') }}</x-ui.button>
            @endif
            @if ($mirror->diagnosis)
                <x-ui.button tone="outline" size="sm" icon-right="chevron-right" :href="route('admin.diagnoses.show', $mirror->diagnosis)">{{ __('admin.mirrors.open_diagnosis') }}</x-ui.button>
            @endif
            @if ($mirror->material)
                <x-ui.button tone="outline" size="sm" icon-right="chevron-right" :href="route('admin.materials.show', $mirror->material)">{{ __('admin.mirrors.open_material') }}</x-ui.button>
            @endif
        </div>
    </x-ui.card>

    {{-- The values as on ET3: a value marked "nem az enyém" keeps its place, struck through --}}
    <section class="flex flex-col gap-3" aria-labelledby="mirror-values">
        <h2 id="mirror-values" class="text-lg font-semibold">{{ __('admin.mirrors.section_values') }}</h2>

        @forelse ($groups as $group)
            @php($insight = $insights->get($group->key))
            <x-ui.card bordered wire:key="group-{{ $loop->index }}" class="flex flex-col gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($group->valueId)
                        <a href="{{ route('admin.values.edit', $group->valueId) }}" @class(['font-semibold text-charcoal', 'line-through' => $group->excluded])>{{ $group->name }}</a>
                    @else
                        <span @class(['font-semibold', 'line-through' => $group->excluded])>{{ $group->name }}</span>
                        <x-ui.badge tone="muted">{{ __('admin.mirrors.free_value') }}</x-ui.badge>
                    @endif
                    @if ($group->excluded)
                        <x-ui.badge tone="coral">{{ __('admin.mirrors.excluded') }}</x-ui.badge>
                    @endif
                </div>

                @if ($insight?->meaning)
                    <p class="text-sm">
                        <span class="font-semibold">{{ __('admin.mirrors.meaning') }}:</span> {{ $insight->meaning }}
                        <span class="text-grey-700">· {{ __('admin.mirrors.meaning_states.'.($insight->edited_at ? 'edited' : ($insight->accepted_at ? 'accepted' : 'suggested'))) }}</span>
                    </p>
                @endif

                <ul class="flex flex-col gap-1 text-sm text-grey-700">
                    @foreach ($group->quotes as $quote)
                        <li>„{{ $quote }}”</li>
                    @endforeach
                </ul>
            </x-ui.card>
        @empty
            <p class="text-grey-700">{{ $status === \App\Enums\MirrorStatus::Processing ? __('admin.mirrors.values_processing') : __('admin.mirrors.no_values') }}</p>
        @endforelse
    </section>

    {{-- The curiosity questions ticked on ET5 (06 · 6.1) --}}
    <x-ui.card as="section" bordered aria-labelledby="mirror-curiosity">
        <h2 id="mirror-curiosity" class="mb-4 text-lg font-semibold">
            {{ __('admin.clients.mirror_curiosity') }}
            @if ($mirror->booking_requested_at)
                <x-ui.badge tone="yellow" class="ml-1">{{ __('admin.clients.mirror_booking') }}</x-ui.badge>
            @endif
        </h2>
        @if ($curiosity->isEmpty())
            <p class="text-grey-700">{{ __('admin.mirrors.no_curiosity') }}</p>
        @else
            <ul class="flex list-disc flex-col gap-1 pl-5">
                @foreach ($curiosity as $question)
                    <li>{{ $question }}</li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>
</div>
