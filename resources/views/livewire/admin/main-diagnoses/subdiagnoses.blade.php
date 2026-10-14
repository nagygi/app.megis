{{-- Részdiagnózisok (17 · 17.4). --}}
<div class="flex max-w-6xl flex-col gap-6">
    <x-ui.flash />

    <x-admin.page-header :title="$diagnosis->name" class="mb-0" />

    <x-admin.main-diagnosis-tabs :diagnosis="$diagnosis" />

    <p class="text-sm text-grey-600">{{ __('admin.main_diagnoses.subdiagnoses_help') }}</p>

    <x-admin.table>
        <x-slot:head>
            <th scope="col" class="px-3 py-2 text-left">{{ __('admin.diagnoses.name') }}</th>
            <th scope="col" class="px-3 py-2 text-left">{{ __('admin.diagnoses.level') }}</th>
            <th scope="col" class="px-3 py-2 text-left">{{ __('admin.diagnoses.type') }}</th>
            <th scope="col" class="px-3 py-2 text-left">{{ __('admin.diagnoses.date') }}</th>
            <th scope="col" class="px-3 py-2 text-left">{{ __('admin.diagnoses.status') }}</th>
        </x-slot:head>

        @forelse ($subdiagnoses as $subdiagnosis)
            <tr wire:key="sub-{{ $subdiagnosis->id }}" @class(['opacity-60' => $subdiagnosis->excluded_at])>
                <td class="px-3 py-2">
                    <a href="{{ route('admin.diagnoses.show', $subdiagnosis) }}" class="font-semibold text-charcoal">{{ $subdiagnosis->name }}</a>
                    @if ($subdiagnosis->excluded_at)
                        <span class="block text-sm text-grey-600">{{ __('admin.main_diagnoses.excluded') }}</span>
                    @endif
                </td>
                <td class="px-3 py-2">{{ $subdiagnosis->subjectLabel() }}</td>
                <td class="px-3 py-2">{{ $subdiagnosis->type->name }}</td>
                <td class="px-3 py-2 whitespace-nowrap">{{ $subdiagnosis->dateLabel() }}</td>
                <td class="px-3 py-2"><x-admin.diagnosis-status :status="$subdiagnosis->status" /></td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-3 py-6 text-center text-grey-600">{{ __('admin.main_diagnoses.subdiagnoses_empty') }}</td></tr>
        @endforelse
    </x-admin.table>
</div>
