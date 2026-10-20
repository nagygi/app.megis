@php
    [$sortField, $sortDirection] = $this->currentSort();
    // An empty list says "Nincs találat." only when a search or filter hid the rows.
    $filtering = filled($search) || collect($filters)->filter(fn ($filter) => filled($filter))->isNotEmpty();
@endphp

<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="__('admin.diagnoses.title')">
        <x-slot:actions>
            <x-ui.button tone="outline" icon="upload" :href="route('admin.diagnoses.upload')">{{ __('admin.upload.create') }}</x-ui.button>
            {{-- Two ways in: start a diagnosis (A1) or record an earlier one (17 · 17.2, 04 · 4.3) --}}
            <x-ui.dropdown
                id="new-diagnosis-menu"
                :label="__('admin.diagnoses.create')"
                :width="288"
                trigger-class="press inline-flex min-h-11 items-center gap-2 rounded-pill bg-charcoal px-5 text-cream font-semibold hover:bg-green"
            >
                <x-slot:trigger>
                    <x-megis.icon name="plus" />
                    {{-- The name comes from the dropdown's label, so it is not read twice. --}}
                    <span aria-hidden="true">{{ __('admin.diagnoses.create') }}</span>
                </x-slot:trigger>

                <x-ui.dropdown-item :href="route('admin.main-diagnoses.create')">
                    <span class="flex flex-col">
                        <span class="font-semibold">{{ __('admin.main_diagnoses.start') }}</span>
                        <span class="text-sm text-grey-700">{{ __('admin.main_diagnoses.start_help') }}</span>
                    </span>
                </x-ui.dropdown-item>
                <x-ui.dropdown-item :href="route('admin.diagnoses.create')">
                    <span class="flex flex-col">
                        <span class="font-semibold">{{ __('admin.diagnoses.record_earlier') }}</span>
                        <span class="text-sm text-grey-700">{{ __('admin.diagnoses.record_earlier_help') }}</span>
                    </span>
                </x-ui.dropdown-item>
            </x-ui.dropdown>
        </x-slot:actions>
    </x-admin.page-header>

    <x-ui.segmented :label="__('admin.garden.title')" class="mb-4">
        <x-ui.segment :href="route('admin.diagnoses.index')">{{ __('admin.garden.view_garden') }}</x-ui.segment>
        <x-ui.segment selected :href="route('admin.diagnoses.index', ['nezet' => 'lista'])">{{ __('admin.garden.view_list') }}</x-ui.segment>
    </x-ui.segmented>

    <x-admin.list-toolbar>
        <x-admin.filter name="organization" :label="__('admin.diagnoses.organization')" :options="$organizations" />
        <x-admin.filter name="level" :label="__('admin.diagnoses.level')" :options="$levels" />
        <x-admin.filter name="type" :label="__('admin.diagnoses.type')" :options="$types" />
        <x-admin.filter name="creator_kind" :label="__('admin.diagnoses.creator')" :options="$creators" />
        <x-admin.filter name="status" :label="__('admin.diagnoses.status')" :options="$statuses" />
        <x-admin.filter name="stage" :label="__('admin.main_diagnoses.stage')" :options="$stages" />
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
            <x-admin.th field="stage" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.main_diagnoses.stage') }}</x-admin.th>
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
                        <span class="block text-sm text-grey-700">{{ $diagnosis->creator->name }}</span>
                    @endif
                </td>
                <td class="px-3 py-2"><x-admin.diagnosis-status :status="$diagnosis->status" /></td>
                <td class="px-3 py-2">{{ $diagnosis->main?->stage->label() ?? '–' }}</td>
            </tr>
        @empty
            <x-admin.empty-row :colspan="8">
                {{ $filtering ? __('admin.common.empty') : __('admin.diagnoses.empty') }}
                @unless ($filtering)
                    <x-slot:action>
                        <x-ui.button tone="outline" icon="plus" :href="route('admin.main-diagnoses.create')">{{ __('admin.diagnoses.create') }}</x-ui.button>
                    </x-slot:action>
                @endunless
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>
</div>
