<x-layouts::error :title="__('errors.4xx.title')" :message="__('errors.4xx.message')" symbol="spiral">
    <p>
        <a href="{{ url('/') }}" class="font-semibold text-charcoal underline">{{ __('errors.home') }}</a>
    </p>
</x-layouts::error>
