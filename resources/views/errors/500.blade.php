<x-layouts::error :title="__('errors.500.title')" :message="__('errors.500.message')" symbol="fire">
    <p>
        <a href="{{ url('/') }}" class="font-semibold text-charcoal underline">{{ __('errors.home') }}</a>
    </p>
</x-layouts::error>
