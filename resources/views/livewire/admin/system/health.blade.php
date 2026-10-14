@php($tones = ['ok' => 'green', 'warning' => 'yellow', 'error' => 'coral'])

<div class="max-w-4xl" wire:poll.60s>
    <x-admin.page-header :title="__('admin.system.health_title')" />

    <dl class="mb-6 grid gap-4 rounded-card border border-grey-250 bg-white p-6 sm:grid-cols-3">
        <div><dt class="text-sm text-grey-600">{{ __('admin.system.version') }}</dt><dd class="font-semibold">{{ $version }}</dd></div>
        <div><dt class="text-sm text-grey-600">{{ __('admin.system.environment') }}</dt><dd class="font-semibold">{{ $environment }}</dd></div>
        <div><dt class="text-sm text-grey-600">{{ __('admin.system.deployed_at') }}</dt><dd class="font-semibold">{{ $deployedAt ?? __('admin.system.unknown') }}</dd></div>
        <div><dt class="text-sm text-grey-600">{{ __('admin.system.queue_size') }}</dt><dd class="font-semibold">{{ $queueSize ?? __('admin.system.unknown') }}</dd></div>
        <div><dt class="text-sm text-grey-600">{{ __('admin.system.failed_jobs') }}</dt><dd class="font-semibold">{{ $failedJobs }}</dd></div>
    </dl>

    <h2 class="mb-3 text-lg font-semibold">{{ __('admin.system.checks') }}</h2>
    <x-admin.table>
        <x-slot:head>
            <x-admin.th>{{ __('checks.command.status') }}</x-admin.th>
            <x-admin.th>{{ __('checks.command.check') }}</x-admin.th>
            <x-admin.th>{{ __('checks.command.details') }}</x-admin.th>
        </x-slot:head>
        @foreach ($results as $result)
            <tr>
                <td class="px-3 py-2"><x-ui.badge :tone="$tones[$result->status->value]">{{ $result->status->label() }}</x-ui.badge></td>
                <td class="px-3 py-2 font-semibold">{{ $result->label }}</td>
                <td class="px-3 py-2">{{ $result->message }}</td>
            </tr>
        @endforeach
    </x-admin.table>
</div>
