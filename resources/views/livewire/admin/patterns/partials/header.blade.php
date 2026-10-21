{{-- Title, state and tabs of a pattern page (NM-19). Expects $pattern. --}}
<div class="mb-6 flex flex-col gap-4">
    <x-admin.page-header :title="$pattern->code.' · '.$pattern->name" :spaced="false">
        <x-slot:badges>
            <div class="flex flex-wrap items-center gap-2">
                @if ($pattern->latestVersion)
                    <x-ui.badge tone="dark">{{ $pattern->latestVersion->label() }}</x-ui.badge>
                @endif
                <x-ui.badge :tone="$pattern->type === \App\Enums\PatternType::DetectorError ? 'coral' : 'neutral'">{{ $pattern->type->label() }}</x-ui.badge>
                @if ($pattern->subtype)
                    <x-ui.badge tone="muted">{{ $pattern->subtype->label() }}</x-ui.badge>
                @endif
                <x-ui.badge :tone="$pattern->validity === \App\Enums\PatternValidity::Valid ? 'green' : 'muted'">{{ $pattern->maturity()->label() }}</x-ui.badge>
            </div>
        </x-slot:badges>
    </x-admin.page-header>

    @if ($pattern->type === \App\Enums\PatternType::DetectorError)
        <x-ui.notice tone="warning">{{ __('admin.patterns.detector_notice') }}</x-ui.notice>
    @endif

    @if ($pattern->imported_at)
        <x-ui.notice>{{ __('admin.patterns.read_only', ['date' => $pattern->imported_at->format('Y.m.d.')]) }}</x-ui.notice>
    @endif

    <x-ui.tabs
        :label="$pattern->name"
        :tabs="collect(['admin.patterns.show' => 'data', 'admin.patterns.versions' => 'versions'])->map(fn (string $tab, string $route) => [
            'href' => route($route, $pattern),
            'label' => __('admin.patterns.tabs.'.$tab),
            'active' => request()->routeIs($route),
        ])->values()->all()"
    />
</div>
