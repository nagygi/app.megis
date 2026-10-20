{{-- "Változott az ÁSZF." (14 · 14.5). The admin working in the customer's name cannot accept. --}}
<x-layouts::client :title="__('auth_ui.legal.title')" color="white">
    <div class="flex max-w-md flex-col gap-5">
        <ul class="flex flex-col gap-3">
            @foreach ($versions as $version)
                <x-ui.card as="li" padding="sm" bordered class="flex flex-col items-start gap-2">
                    <p class="font-semibold">{{ __('auth_ui.legal.changed', ['document' => $version->type->label()]) }}</p>
                    <x-ui.button tone="outline" size="sm" icon="document" :href="route('legal.show', $version->type->value)" target="_blank" rel="noopener">
                        {{ __('auth_ui.legal.open') }}<span class="sr-only"> ({{ __('ui.new_tab') }})</span>
                    </x-ui.button>
                </x-ui.card>
            @endforeach
        </ul>

        @if ($isImpersonating)
            <x-ui.notice tone="warning">{{ __('auth_ui.legal.impersonating') }}</x-ui.notice>
        @else
            <form method="POST" action="{{ route('client.legal.store') }}">
                @csrf
                <x-ui.button type="submit" size="lg" icon-right="arrow-right">{{ __('auth_ui.legal.accept') }}</x-ui.button>
            </form>
        @endif

        <form method="POST" action="{{ route('client.logout') }}">
            @csrf
            <x-ui.button type="submit" tone="outline">{{ __('auth_ui.legal.logout') }}</x-ui.button>
        </form>
    </div>
</x-layouts::client>
