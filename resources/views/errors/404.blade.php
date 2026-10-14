<x-layouts::error :title="__('errors.404.title')" :message="__('errors.404.message')" symbol="spiral">
    <p>
        <a href="{{ url('/') }}" class="font-semibold text-charcoal underline">{{ __('errors.home') }}</a>
    </p>
</x-layouts::error>
