<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Diagnosis;
use App\Models\User;
use App\Services\Report\ReportBuilder;
use App\Services\Report\ReportPages;
use App\Services\Report\StandaloneReportBuilder;
use App\Services\Report\TakeawayBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * The admin's preview of what goes out (21 · 21.1): the leader's report and
 * a peer's Útravaló, from the live data, in the same views, clickable; the
 * report of a standalone diagnosis before publishing (04 · 4.6).
 */
class ReportPreviewController extends Controller
{
    public function report(Request $request, Diagnosis $diagnosis, ReportBuilder $builder, StandaloneReportBuilder $standalone, ReportPages $pages, ?string $page = null, ?int $row = null): View
    {
        Gate::authorize('update', $diagnosis);
        $main = $diagnosis->main;
        // A subdiagnosis has no report of its own.
        abort_if($main === null && $diagnosis->parent_id !== null, 404);

        // A chapter slug in place of a page name opens the chapter or one of its rows.
        [$name, $chapter] = match (true) {
            $page === null => ['show', null],
            in_array($page, ['osszefoglalo', 'belso-mukodes', 'osszevetes', 'zarolap', 'melleklet'], true) => [['osszefoglalo' => 'summary', 'belso-mukodes' => 'internal', 'osszevetes' => 'compare', 'zarolap' => 'closing', 'melleklet' => 'appendix'][$page], null],
            default => [$row === null ? 'chapter' : 'item', $page],
        };

        return $pages->render(
            $name,
            $diagnosis,
            $main === null ? $standalone->build($diagnosis) : $builder->build($main),
            $chapter,
            $row,
            fn (string $target, array $parameters = []) => $this->url($diagnosis, $target, $parameters),
            null,
            $main?->leader_id,
            $request->query('nezet'),
            preview: true,
        );
    }

    public function takeaway(Diagnosis $diagnosis, User $peer, TakeawayBuilder $takeaways, ReportBuilder $builder): View
    {
        Gate::authorize('update', $diagnosis);
        $main = $diagnosis->main;
        abort_if($main === null || ! $takeaways->recipients($main)->contains('id', $peer->id), 404);

        $data = $builder->build($main);

        return view('client.report.takeaway', [
            'organization' => $diagnosis->organization,
            'diagnosis' => $diagnosis,
            'main' => $main,
            'takeaway' => $takeaways->build($main, $peer),
            'recommendation' => $data['texts']['recommendation'] ?? null,
            'publishedOn' => $data['meta']['published_on'],
            'preview' => true,
        ]);
    }

    /**
     * @param  array<int, mixed>  $parameters
     */
    private function url(Diagnosis $diagnosis, string $target, array $parameters): string
    {
        $page = match ($target) {
            'show' => null,
            'summary' => 'osszefoglalo',
            'internal' => 'belso-mukodes',
            'compare' => 'osszevetes',
            'closing' => 'zarolap',
            'appendix' => 'melleklet',
            'chapter', 'item' => $parameters[0] ?? null,
            default => null,
        };

        return route('admin.main-diagnoses.preview', array_filter([$diagnosis, $page, $target === 'item' ? ($parameters[1] ?? null) : null], fn ($value) => $value !== null));
    }
}
