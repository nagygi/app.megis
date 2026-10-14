<x-layouts::error :title="__('errors.403.title')" :message="__('errors.403.message')" symbol="circle">
    <p>
        <a href="{{ url('/') }}" class="font-semibold text-charcoal underline">{{ __('errors.home') }}</a>
    </p>
</x-layouts::error>
