<?php

namespace App\Livewire\Admin\Diagnoses;

use App\Models\Diagnosis;
use App\Services\Report\ReportPdfStore;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Egyedi riport-PDF (28 · 28.3): "Letöltöm · PDF" gives it instead of the
 * generated one. On the Publikálás tab and on a standalone diagnosis; it can
 * be replaced after publishing too.
 */
class ReportPdf extends Component
{
    use WithFileUploads;

    public Diagnosis $diagnosis;

    public ?TemporaryUploadedFile $file = null;

    public function mount(Diagnosis $diagnosis): void
    {
        Gate::authorize('view', $diagnosis);

        $this->diagnosis = $diagnosis;
    }

    public function save(ReportPdfStore $uploads): void
    {
        Gate::authorize('update', $this->diagnosis);
        $this->validate(['file' => ['required', 'file', 'mimes:pdf', 'max:20480']], attributes: ['file' => __('admin.report_pdf.file')]);

        $uploads->store($this->diagnosis, $this->file);
        $this->reset('file');
        session()->flash('status', __('admin.report_pdf.uploaded'));
    }

    public function remove(ReportPdfStore $uploads): void
    {
        Gate::authorize('update', $this->diagnosis);

        $uploads->remove($this->diagnosis);
        session()->flash('status', __('admin.report_pdf.removed'));
    }

    public function render(): View
    {
        return view('livewire.admin.diagnoses.report-pdf');
    }
}
