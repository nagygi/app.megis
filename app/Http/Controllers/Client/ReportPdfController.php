<?php

namespace App\Http\Controllers\Client;

use App\Enums\MainDiagnosisStage;
use App\Http\Controllers\Controller;
use App\Models\Diagnosis;
use App\Models\Organization;
use App\Services\Report\ReportService;
use App\Services\Report\StandaloneReportBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

/**
 * The PDFs (28 · 28.3): the report with its appendix, and the Útravaló, from
 * the frozen snapshot, rendered on the server. Whoever sees the page may
 * download it.
 */
class ReportPdfController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function report(Request $request, Organization $organization, Diagnosis $diagnosis, StandaloneReportBuilder $standalone): PdfBuilder
    {
        abort_if($diagnosis->organization_id !== $organization->id, 404);
        $main = $diagnosis->main;

        if ($main === null) {
            // A published standalone diagnosis (04 · 4.6).
            abort_if($diagnosis->parent_id !== null, 404);
            abort_unless($this->reports->canViewStandalone($request->user(), $diagnosis), 403);
            $data = $standalone->build($diagnosis);
        } else {
            abort_unless($this->reports->canView($request->user(), $main), 403);
            $snapshot = $this->reports->snapshot($main);
            abort_if($snapshot === null, 404);
            $data = $snapshot->data;
        }

        $date = Carbon::parse($data['meta']['published_on']);

        return $this->pdf('pdf.report', ['report' => $data], $data['meta']['name'], $date, $data['meta']['subject_name'])
            ->download('megis-diagnozis-'.Str::slug($data['meta']['subject_name']).'-'.$date->format('Y-m-d').'.pdf');
    }

    public function takeaway(Request $request, Organization $organization, Diagnosis $diagnosis): PdfBuilder
    {
        $main = $diagnosis->main;
        abort_if($main === null || $diagnosis->organization_id !== $organization->id || $main->stage !== MainDiagnosisStage::Published, 404);
        $snapshot = $this->reports->snapshot($main);
        $takeaway = $snapshot?->data['takeaways'][$request->user()->id] ?? null;
        abort_if($takeaway === null, 404);

        $date = Carbon::parse($snapshot->data['meta']['published_on']);

        return $this->pdf('pdf.takeaway', [
            'takeaway' => $takeaway,
            'recommendation' => $snapshot->data['texts']['recommendation'] ?? null,
        ], __('takeaway.title'), $date, $takeaway['subject_name'])
            ->download('megis-utravalo-'.$date->format('Y-m-d').'.pdf');
    }

    /**
     * Logo, name and date in the header; "Bizalmas · {Csapat}" in the footer (28 · 28.3).
     *
     * @param  array<string, mixed>  $data
     */
    private function pdf(string $view, array $data, string $title, Carbon $date, string $subject): PdfBuilder
    {
        return Pdf::view($view, [...$data, 'title' => $title, 'date' => $date])
            ->headerView('pdf.header', ['title' => $title, 'date' => $date])
            ->footerView('pdf.footer', ['subject' => $subject])
            ->format('a4')
            ->margins(22, 16, 20, 16);
    }
}
