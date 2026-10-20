<x-layouts::error :title="__('errors.404.title')" :message="__('errors.404.message')" symbol="spiral">
    <p class="flex flex-wrap gap-x-6 gap-y-2">
        <a href="{{ url('/') }}" class="font-semibold text-charcoal">{{ __('errors.home') }}</a>
        <a href="{{ route('client.landing') }}" class="font-semibold text-charcoal">{{ __('errors.account') }}</a>
    </p>
</x-layouts::error>
