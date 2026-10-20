<x-layouts::error :title="__('errors.419.title')" :message="__('errors.419.message')" symbol="growth-rings">
    <p class="flex flex-wrap gap-x-6 gap-y-2">
        <a href="{{ url()->previous(url('/')) }}" class="font-semibold text-charcoal">{{ __('errors.reload') }}</a>
        <a href="{{ route('client.landing') }}" class="font-semibold text-charcoal">{{ __('errors.account') }}</a>
    </p>
</x-layouts::error>
