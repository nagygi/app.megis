{{-- Részdiagnózisok (17 · 17.4). --}}
<div class="flex max-w-7xl flex-col gap-6">
    <x-ui.flash />

    <x-admin.main-diagnosis-tabs :diagnosis="$diagnosis" />

    <p class="text-sm text-grey-700">{{ __('admin.main_diagnoses.subdiagnoses_help') }}</p>

    <x-admin.table>
        <x-slot:head>
            <x-admin.th>{{ __('admin.diagnoses.name') }}</x-admin.th>
            <x-admin.th>{{ __('admin.diagnoses.level') }}</x-admin.th>
            <x-admin.th>{{ __('admin.diagnoses.type') }}</x-admin.th>
            <x-admin.th>{{ __('admin.diagnoses.date') }}</x-admin.th>
            <x-admin.th>{{ __('admin.diagnoses.status') }}</x-admin.th>
        </x-slot:head>

        @forelse ($subdiagnoses as $subdiagnosis)
            <tr wire:key="sub-{{ $subdiagnosis->id }}" @class(['bg-grey-100 text-grey-600' => $subdiagnosis->excluded_at])>
                <td class="px-3 py-2">
                    <a href="{{ route('admin.diagnoses.show', $subdiagnosis) }}" class="font-semibold text-charcoal">{{ $subdiagnosis->name }}</a>
                    @if ($subdiagnosis->excluded_at)
                        <span class="block text-sm text-grey-700">{{ __('admin.main_diagnoses.excluded') }}</span>
                    @endif
                </td>
                <td class="px-3 py-2">{{ $subdiagnosis->subjectLabel() }}</td>
                <td class="px-3 py-2">{{ $subdiagnosis->type->name }}</td>
                <td class="px-3 py-2 whitespace-nowrap">{{ $subdiagnosis->dateLabel() }}</td>
                <td class="px-3 py-2"><x-admin.diagnosis-status :status="$subdiagnosis->status" /></td>
            </tr>
        @empty
            {{-- Subdiagnoses come with the run, so there is nothing to add here. --}}
            <x-admin.empty-row :colspan="5">{{ __('admin.main_diagnoses.subdiagnoses_empty') }}</x-admin.empty-row>
        @endforelse
    </x-admin.table>
</div>
