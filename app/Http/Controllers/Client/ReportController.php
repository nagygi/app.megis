<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Diagnosis;
use App\Models\Organization;
use App\Services\Report\ReportPages;
use App\Services\Report\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The report of a published main diagnosis (27): the answer, the summary,
 * the internal working, the four chapters with their deepening pages, the
 * comparison and the closing page; the snapshot product on one page (S1).
 * Every page reads the frozen snapshot (21 · 21.3).
 */
class ReportController extends Controller
{
    public function __construct(
        private ReportService $reports,
        private ReportPages $pages,
    ) {}

    public function answer(Request $request, Organization $organization, Diagnosis $diagnosis): View
    {
        return $this->page($request, $organization, $diagnosis, 'show');
    }

    public function summary(Request $request, Organization $organization, Diagnosis $diagnosis): View
    {
        return $this->page($request, $organization, $diagnosis, 'summary');
    }

    public function internal(Request $request, Organization $organization, Diagnosis $diagnosis): View
    {
        return $this->page($request, $organization, $diagnosis, 'internal');
    }

    public function chapter(Request $request, Organization $organization, Diagnosis $diagnosis, string $chapter): View
    {
        return $this->page($request, $organization, $diagnosis, 'chapter', $chapter);
    }

    public function item(Request $request, Organization $organization, Diagnosis $diagnosis, string $chapter, int $row): View
    {
        return $this->page($request, $organization, $diagnosis, 'item', $chapter, $row);
    }

    public function compare(Request $request, Organization $organization, Diagnosis $diagnosis): View
    {
        return $this->page($request, $organization, $diagnosis, 'compare');
    }

    public function closing(Request $request, Organization $organization, Diagnosis $diagnosis): View
    {
        return $this->page($request, $organization, $diagnosis, 'closing');
    }

    /**
     * RM · Melléklet: honnan tudjuk (28 · 28.2).
     */
    public function appendix(Request $request, Organization $organization, Diagnosis $diagnosis): View
    {
        return $this->page($request, $organization, $diagnosis, 'appendix');
    }

    private function page(Request $request, Organization $organization, Diagnosis $diagnosis, string $page, ?string $chapter = null, ?int $row = null): View
    {
        $main = $diagnosis->main;
        abort_if($main === null || $diagnosis->organization_id !== $organization->id, 404);
        abort_unless($this->reports->canView($request->user(), $main), 403);

        $snapshot = $this->reports->snapshot($main);
        abort_if($snapshot === null, 404);

        return $this->pages->render(
            $page,
            $main,
            $snapshot->data,
            $chapter,
            $row,
            fn (string $name, array $parameters = []) => route('client.report.'.$name, [$organization, $diagnosis, ...$parameters]),
            route('client.diagnosis.show', [$organization, $diagnosis]),
            $request->user()->id,
            $request->query('nezet'),
            preview: false,
        );
    }
}
