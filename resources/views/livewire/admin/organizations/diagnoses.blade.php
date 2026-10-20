{{-- The organization's Diagnózisok tab (17 · 17.8). --}}
<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="__('admin.organizations.tabs.diagnoses')">
        <x-slot:actions>
            <x-ui.button :href="route('admin.main-diagnoses.create', ['szervezet' => $organization->id])">{{ __('admin.main_diagnoses.start') }}</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table>
        <x-slot:head>
            <x-admin.th>{{ __('admin.diagnoses.name') }}</x-admin.th>
            <x-admin.th>{{ __('admin.diagnoses.type') }}</x-admin.th>
            <x-admin.th>{{ __('admin.main_diagnoses.product') }}</x-admin.th>
            <x-admin.th>{{ __('admin.main_diagnoses.stage') }}</x-admin.th>
            <x-admin.th>{{ __('admin.diagnoses.date') }}</x-admin.th>
        </x-slot:head>

        @forelse ($diagnoses as $diagnosis)
            <tr wire:key="diagnosis-{{ $diagnosis->id }}">
                <td class="px-3 py-2">
                    <a href="{{ route('admin.diagnoses.show', $diagnosis) }}" class="font-semibold text-charcoal">{{ $diagnosis->name }}</a>
                    <span class="block text-sm text-grey-700">{{ $diagnosis->subjectLabel() }}</span>
                </td>
                <td class="px-3 py-2">{{ $diagnosis->type->name }}</td>
                <td class="px-3 py-2">{{ $diagnosis->main?->product_name ?? '–' }}</td>
                <td class="px-3 py-2">
                    @if ($diagnosis->main)
                        {{ $diagnosis->main->stage->label() }}
                    @else
                        <x-admin.diagnosis-status :status="$diagnosis->status" />
                    @endif
                </td>
                <td class="px-3 py-2 whitespace-nowrap">{{ $diagnosis->dateLabel() }}</td>
            </tr>
        @empty
            <x-admin.empty-row :colspan="5">
                {{ __('admin.organizations.no_diagnoses') }}
                <x-slot:action>
                    <x-ui.button tone="outline" :href="route('admin.main-diagnoses.create', ['szervezet' => $organization->id])">{{ __('admin.main_diagnoses.start') }}</x-ui.button>
                </x-slot:action>
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>
</div>
