{{-- Egyedi riport-PDF (28 · 28.3): "Letöltöm · PDF" gives it instead of the generated one. --}}
<x-ui.card as="section" bordered class="flex flex-col gap-3" aria-labelledby="report-pdf-title">
    <x-ui.flash />

    <div class="flex flex-col gap-1">
        <h2 id="report-pdf-title" class="text-lg font-semibold">{{ __('admin.report_pdf.title') }}</h2>
        <p class="text-sm text-grey-700">{{ __('admin.report_pdf.help') }}</p>
    </div>

    @unless (config('megis.pdf_downloads'))
        <x-ui.notice tone="warning">{{ __('admin.report_pdf.downloads_off') }}</x-ui.notice>
    @endunless

    @if ($diagnosis->hasReportPdf())
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <p>{{ __('admin.report_pdf.current', ['name' => $diagnosis->report_pdf_name, 'date' => $diagnosis->report_pdf_uploaded_at->format('Y.m.d. H:i')]) }}</p>
            <x-ui.button tone="ghost" size="sm" icon="document" :href="route('admin.diagnoses.report-pdf', $diagnosis)" target="_blank">{{ __('admin.report_pdf.open') }}</x-ui.button>
            <x-ui.button
                tone="danger"
                size="sm"
                wire:click="remove"
                wire:confirm="{{ __('admin.report_pdf.confirm_remove') }}"
                data-confirm-tone="danger"
                :data-confirm-label="__('admin.report_pdf.remove')"
            >{{ __('admin.report_pdf.remove') }}</x-ui.button>
        </div>
    @else
        <p class="text-sm text-grey-700">{{ __('admin.report_pdf.none') }}</p>
    @endif

    <form wire:submit="save" class="flex flex-col gap-3">
        <div class="flex flex-col gap-1.5">
            <x-form.file name="file" id="report-pdf-file" wire:model="file" accept="application/pdf" :label="__('admin.report_pdf.file')" :help="__('admin.report_pdf.file_help')" required />
            <div wire:loading wire:target="file" class="text-sm text-grey-700" role="status">{{ __('admin.upload.uploading') }}</div>
        </div>
        <x-ui.button type="submit" size="sm" icon="upload" class="self-start" wire:loading.attr="disabled" wire:target="file,save">
            {{ $diagnosis->hasReportPdf() ? __('admin.report_pdf.replace') : __('admin.report_pdf.upload') }}
        </x-ui.button>
    </form>
</x-ui.card>
