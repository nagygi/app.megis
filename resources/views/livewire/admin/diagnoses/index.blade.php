@php([$sortField, $sortDirection] = $this->currentSort())

<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="__('admin.diagnoses.title')">
        <x-slot:actions>
            <x-ui.button :href="route('admin.diagnoses.create')">{{ __('admin.diagnoses.create') }}</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.list-toolbar>
        <x-admin.filter name="organization" :label="__('admin.diagnoses.organization')" :options="$organizations" />
        <x-admin.filter name="level" :label="__('admin.diagnoses.level')" :options="$levels" />
        <x-admin.filter name="type" :label="__('admin.diagnoses.type')" :options="$types" />
        <x-admin.filter name="creator_kind" :label="__('admin.diagnoses.creator')" :options="$creators" />
        <x-admin.filter name="status" :label="__('admin.diagnoses.status')" :options="$statuses" />
    </x-admin.list-toolbar>

    <x-admin.table :paginator="$diagnoses">
        <x-slot:head>
            <x-admin.th field="name" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.diagnoses.name') }}</x-admin.th>
            <x-admin.th field="organization" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.diagnoses.organization') }}</x-admin.th>
            <x-admin.th field="level" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.diagnoses.level') }}</x-admin.th>
            <x-admin.th field="type" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.diagnoses.type') }}</x-admin.th>
            <x-admin.th field="date" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.diagnoses.date') }}</x-admin.th>
            <x-admin.th field="creator" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.diagnoses.creator') }}</x-admin.th>
            <x-admin.th field="status" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.diagnoses.status') }}</x-admin.th>
        </x-slot:head>

        @forelse ($diagnoses as $diagnosis)
            <tr wire:key="diagnosis-{{ $diagnosis->id }}">
                <td class="px-3 py-2"><a href="{{ route('admin.diagnoses.show', $diagnosis) }}" class="font-semibold text-charcoal">{{ $diagnosis->name }}</a></td>
                <td class="px-3 py-2">{{ $diagnosis->organization->name }}</td>
                <td class="px-3 py-2">{{ $diagnosis->subjectLabel() }}</td>
                <td class="px-3 py-2">{{ $diagnosis->type->name }}</td>
                <td class="px-3 py-2 whitespace-nowrap">{{ $diagnosis->dateLabel() }}</td>
                <td class="px-3 py-2">
                    {{ $diagnosis->creator_kind->label() }}
                    @if ($diagnosis->creator)
                        <span class="block text-sm text-grey-600">{{ $diagnosis->creator->name }}</span>
                    @endif
                </td>
                <td class="px-3 py-2"><x-admin.diagnosis-status :status="$diagnosis->status" /></td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-3 py-6 text-center text-grey-600">{{ __('admin.common.empty') }}</td></tr>
        @endforelse
    </x-admin.table>
</div>
