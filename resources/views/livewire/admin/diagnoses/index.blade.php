@php([$sortField, $sortDirection] = $this->currentSort())

<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="__('admin.diagnoses.title')">
        <x-slot:actions>
            <x-ui.button tone="outline" :href="route('admin.diagnoses.upload')">{{ __('admin.upload.create') }}</x-ui.button>
            {{-- Two ways in: start a diagnosis (A1) or record an earlier one (17 · 17.2, 04 · 4.3) --}}
            <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false" class="relative">
                <x-ui.button @click="open = ! open" ::aria-expanded="open" aria-controls="new-diagnosis-menu">{{ __('admin.diagnoses.create') }}</x-ui.button>
                <ul id="new-diagnosis-menu" x-show="open" x-cloak class="absolute right-0 z-10 mt-2 flex w-72 flex-col gap-1 rounded-card border border-grey-250 bg-white p-2">
                    <li><a href="{{ route('admin.main-diagnoses.create') }}" class="block rounded-lg px-3 py-2 text-charcoal no-underline hover:bg-grey-100"><span class="block font-semibold">{{ __('admin.main_diagnoses.start') }}</span><span class="block text-sm text-grey-600">{{ __('admin.main_diagnoses.start_help') }}</span></a></li>
                    <li><a href="{{ route('admin.diagnoses.create') }}" class="block rounded-lg px-3 py-2 text-charcoal no-underline hover:bg-grey-100"><span class="block font-semibold">{{ __('admin.diagnoses.record_earlier') }}</span><span class="block text-sm text-grey-600">{{ __('admin.diagnoses.record_earlier_help') }}</span></a></li>
                </ul>
            </div>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mb-4 flex gap-2 text-sm" role="group" aria-label="{{ __('admin.garden.title') }}">
        <a href="{{ route('admin.diagnoses.index') }}" class="rounded-pill border border-charcoal px-3 py-1 font-semibold text-charcoal no-underline">{{ __('admin.garden.view_garden') }}</a>
        <span class="rounded-pill bg-charcoal px-3 py-1 font-semibold text-cream" aria-current="page">{{ __('admin.garden.view_list') }}</span>
    </div>

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
                        <span class="block text-sm text-grey-600">{{ $diagnosis->creator->name }}</span>
                    @endif
                </td>
                <td class="px-3 py-2"><x-admin.diagnosis-status :status="$diagnosis->status" /></td>
                <td class="px-3 py-2">{{ $diagnosis->main?->stage->label() ?? '–' }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="px-3 py-6 text-center text-grey-600">{{ __('admin.common.empty') }}</td></tr>
        @endforelse
    </x-admin.table>
</div>
