{{-- Ügyfélmérés of a main diagnosis (35 · 35.8). --}}
<div class="flex flex-col gap-6">
    <x-admin.main-diagnosis-tabs :diagnosis="$diagnosis" />

    <div class="flex flex-col gap-6">
        <x-ui.flash />

        <p class="max-w-3xl text-sm text-grey-700">{{ __('admin.external_clients.diagnosis_help') }}</p>

        @foreach ($clients as $client)
            @php($subdiagnosis = $subdiagnoses->get($client->id))
            <x-ui.card as="section" bordered class="flex flex-col gap-5" wire:key="external-client-{{ $client->id }}">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold">
                        <a href="{{ route('admin.organizations.external-clients.edit', [$diagnosis->organization, $client]) }}" class="text-charcoal">{{ $client->name }}</a>
                    </h2>
                    @if ($subdiagnosis)
                        <x-ui.button tone="outline" size="sm" :href="route('admin.diagnoses.show', $subdiagnosis)">{{ __('admin.external_clients.open_subdiagnosis') }}</x-ui.button>
                    @endif
                </div>

                <div class="flex flex-col gap-2">
                    <h3 class="text-sm font-semibold">{{ __('admin.external_clients.users') }}</h3>
                    <ul class="divide-y divide-grey-200 rounded-card border border-grey-250">
                        @forelse ($client->users as $user)
                            @php($state = $states[$user->id] ?? 'not_invited')
                            <li class="flex flex-wrap items-center gap-x-4 gap-y-2 py-2 pr-2 pl-4" wire:key="external-client-{{ $client->id }}-user-{{ $user->id }}">
                                <div class="min-w-0 flex-1">
                                    <x-admin.user-link :user="$user" />
                                    @if ($user->name !== $user->email)
                                        <p class="text-sm break-all text-grey-700">{{ $user->email }}</p>
                                    @endif
                                </div>
                                <x-admin.user-status :status="match ($state) { 'submitted' => 'active', 'started', 'invited' => 'invited', default => 'inactive' }">{{ __('admin.external_clients.states.'.$state) }}</x-admin.user-status>
                                @if ($canResend)
                                    <x-ui.button tone="outline" size="sm" icon="mail" wire:click="resend({{ $client->id }}, {{ $user->id }})">{{ __('admin.members.resend_invitation') }}</x-ui.button>
                                @endif
                            </li>
                        @empty
                            <li class="px-4 py-3 text-sm text-grey-700">{{ __('admin.external_clients.no_users') }}</li>
                        @endforelse
                    </ul>
                </div>

                <div class="flex flex-col gap-2">
                    <h3 class="text-sm font-semibold">{{ __('admin.external_clients.answers') }}</h3>
                    @forelse ($answers->get($client->id, collect()) as $answer)
                        @php($subject = $answer->kind === \App\Enums\ExternalClientItemKind::Value ? ($valueNames[$answer->value_key] ?? null) : $answer->process?->name)
                        <article class="flex flex-col gap-1.5 rounded-card border border-grey-250 p-4" wire:key="external-answer-{{ $answer->id }}">
                            <p class="text-sm text-grey-700">
                                {{ $answer->kind->label() }}{{ filled($subject) ? ' · '.$subject : '' }} · {{ $answer->user->name }} · {{ $answer->submitted_at->format('Y.m.d. H:i') }}
                            </p>
                            <p class="font-semibold">{{ $answer->question }}</p>
                            <p class="whitespace-pre-line">{{ $answer->answer }}</p>
                            @if ($answer->material)
                                <p class="text-sm">
                                    <a href="{{ route('admin.materials.show', $answer->material) }}" class="font-semibold text-charcoal">{{ __('admin.external_clients.material') }}</a>
                                    · {{ $answer->material->status->label() }}
                                </p>
                            @endif
                        </article>
                    @empty
                        <p class="text-sm text-grey-700">{{ __('admin.external_clients.no_answers') }}</p>
                    @endforelse
                </div>
            </x-ui.card>
        @endforeach
    </div>
</div>
