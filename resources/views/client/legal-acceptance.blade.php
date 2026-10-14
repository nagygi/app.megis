{{-- "Változott az ÁSZF." (14 · 14.5). The admin working in the customer's name cannot accept. --}}
<x-layouts::client :title="__('auth_ui.legal.title')" color="white">
    <div class="flex max-w-md flex-col gap-5">
        <ul class="flex flex-col gap-3">
            @foreach ($versions as $version)
                <li class="rounded-card border border-grey-250 bg-white p-4">
                    <p class="font-semibold">{{ __('auth_ui.legal.changed', ['document' => $version->type->label()]) }}</p>
                    <a href="{{ route('legal.show', $version->type->value) }}" target="_blank" class="font-semibold text-charcoal">{{ __('auth_ui.legal.open') }}</a>
                </li>
            @endforeach
        </ul>

        @if ($isImpersonating)
            <p class="rounded-card bg-yellow-pale p-4">{{ __('auth_ui.legal.impersonating') }}</p>
        @else
            <form method="POST" action="{{ route('client.legal.store') }}">
                @csrf
                <x-ui.button type="submit" size="lg">{{ __('auth_ui.legal.accept') }}</x-ui.button>
            </form>
        @endif

        <form method="POST" action="{{ route('client.logout') }}">
            @csrf
            <x-ui.button type="submit" tone="outline">{{ __('auth_ui.legal.logout') }}</x-ui.button>
        </form>
    </div>
</x-layouts::client>
