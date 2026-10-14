{{-- Flat card: no shadow anywhere in the identity, elevation comes from contrast. --}}
<div {{ $attributes->class(['rounded-card border border-grey-250 bg-white p-5']) }}>
    {{ $slot }}
</div>
