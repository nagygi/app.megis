<?php

namespace App\Http\Controllers\Client;

use App\Enums\MainDiagnosisStage;
use App\Http\Controllers\Controller;
use App\Models\Diagnosis;
use App\Models\Organization;
use App\Services\Report\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * T6 · Útravaló (29): the peer's page from the frozen snapshot, only for the
 * peer it was made for; gone while the diagnosis is withdrawn (21 · 21.5).
 */
class TakeawayController extends Controller
{
    public function __invoke(Request $request, Organization $organization, Diagnosis $diagnosis, ReportService $reports): View
    {
        $main = $diagnosis->main;
        abort_if($main === null || $diagnosis->organization_id !== $organization->id, 404);
        abort_unless($main->stage === MainDiagnosisStage::Published, 404);

        $snapshot = $reports->snapshot($main);
        $takeaway = $snapshot?->data['takeaways'][$request->user()->id] ?? null;
        abort_if($takeaway === null, 404);

        return view('client.report.takeaway', [
            'organization' => $organization,
            'diagnosis' => $diagnosis,
            'main' => $main,
            'takeaway' => $takeaway,
            'recommendation' => $snapshot->data['texts']['recommendation'] ?? null,
            'publishedOn' => $snapshot->data['meta']['published_on'],
            'preview' => false,
        ]);
    }
}
