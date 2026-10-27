<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Diagnosis;
use App\Services\Report\ReportPdfStore;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Opens the report PDF the admin uploaded (28 · 28.3).
 */
class UploadedReportPdfController extends Controller
{
    public function __invoke(Diagnosis $diagnosis, ReportPdfStore $uploads): StreamedResponse
    {
        Gate::authorize('view', $diagnosis);
        abort_unless($diagnosis->hasReportPdf(), 404);

        return $uploads->response($diagnosis, $diagnosis->report_pdf_name, 'inline');
    }
}
