{{-- A client user's personal link (35 · 35.5): only the button uses it up, as the login link (14 · 14.2). --}}
<x-layouts::client :title="__('external_clients.invitation.title')" :subtitle="$organization->name" guest>
    <form
        method="POST"
        action="{{ route('external.invitation.store', $token) }}"
        x-data="{ accepted: {{ ! $needsAcceptance || old('accept') ? 'true' : 'false' }} }"
        class="flex max-w-md flex-col gap-5"
    >
        @csrf
        <p>{{ __('external_clients.invitation.lead', ['organization' => $organization->name]) }}</p>

        @if ($needsAcceptance)
            <x-form.checkbox name="accept" x-model="accepted" :checked="(bool) old('accept')">
                {{-- Each document is a link once uploaded, plain text until then (05 · 5.2). --}}
                @php
                    $documentLink = fn ($version, string $slug, string $label) => $version
                        ? '<a href="'.e(route('legal.show', $slug)).'" target="_blank" rel="noopener" class="font-semibold text-charcoal">'.e($label).'<span class="sr-only"> ('.e(__('ui.new_tab')).')</span></a>'
                        : e($label);
                @endphp
                {!! __('auth_ui.registration.accept', [
                    'terms' => $documentLink($terms, 'aszf', __('auth_ui.registration.accept_terms')),
                    'privacy' => $documentLink($privacy, 'adatkezelesi-tajekoztato', __('auth_ui.registration.accept_privacy')),
                ]) !!}
            </x-form.checkbox>
        @endif

        <div class="flex flex-col gap-2">
            <x-ui.button type="submit" size="lg" icon-right="arrow-right" class="self-start" ::disabled="! accepted">{{ __('external_clients.invitation.button') }}</x-ui.button>
            @if ($needsAcceptance)
                <p class="text-sm text-grey-700" x-show="! accepted" x-cloak>{{ __('external_clients.invitation.accept_needed') }}</p>
            @endif
        </div>
    </form>
</x-layouts::client>
