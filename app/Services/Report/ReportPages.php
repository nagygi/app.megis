<?php

namespace App\Services\Report;

use App\Models\ChapterUpdate;
use App\Models\Diagnosis;
use App\Models\MainDiagnosis;
use App\Models\User;
use App\Services\Capabilities\CapabilityOverview;
use Closure;
use Illuminate\View\View;

/**
 * Renders a page of the report from its data (27): the client reads the
 * frozen snapshot, the admin's preview the live data (21 · 21.1). The same
 * views either way; only the links differ. A standalone diagnosis has no
 * main diagnosis, so no summary, internal working or comparison (04 · 4.6).
 * The pages share the prototype's frame: the chapter dots under the title
 * and the footer with the next page.
 */
class ReportPages
{
    public const PAGES = ['show', 'summary', 'internal', 'chapter', 'item', 'compare', 'closing', 'appendix'];

    /**
     * The chapters of the prototype, R0–R8: their dots and their numbers in the footer.
     */
    public const STEPS = ['answer', 'summary', 'values', 'processes', 'areas', 'brand', 'brand_report', 'capability', 'bridge'];

    /**
     * Not built yet: their dots stay without a link, "hamarosan".
     */
    private const SOON = ['brand_report', 'capability', 'bridge'];

    /**
     * @param  array<string, mixed>  $data
     * @param  Closure(string, array<int, mixed>=): string  $url
     */
    public function render(string $page, Diagnosis $diagnosis, array $data, ?string $chapter, ?int $row, Closure $url, ?string $homeUrl, ?int $viewerId, ?string $mode, bool $preview): View
    {
        abort_unless(in_array($page, self::PAGES, true), 404);
        $main = $diagnosis->main;
        $snapshot = $data['meta']['snapshot'];
        $hasSummary = ! $snapshot && ! ($data['meta']['standalone'] ?? false);
        abort_if(! $hasSummary && in_array($page, ['summary', 'internal', 'compare'], true), 404);

        $extra = [];
        $key = null;

        if (in_array($page, ['chapter', 'item'], true)) {
            $key = $chapter === null ? null : ReportService::chapterFromSlug($chapter);
            abort_if($key === null || ! isset($data['chapters'][$key]), 404);
            $extra['chapter'] = $data['chapters'][$key];

            if ($page === 'item') {
                $rows = collect($data['chapters'][$key]['rows']);
                $position = $rows->search(fn (array $candidate) => $candidate['id'] === $row);
                abort_if($position === false, 404);
                $extra['item'] = $rows->get($position);
                $extra['position'] = ['current' => $position + 1, 'total' => $rows->count()];
            }
        }

        $view = match ($page) {
            'show' => $snapshot ? 'client.report.snapshot' : 'client.report.answer',
            default => 'client.report.'.$page,
        };
        $current = match ($page) {
            'show' => $snapshot ? null : 'answer',
            'summary' => 'summary',
            'chapter' => $snapshot ? null : $key,
            default => null,
        };

        return view($view, [
            'url' => $url,
            'homeUrl' => $homeUrl,
            'preview' => $preview,
            'organization' => $diagnosis->organization,
            'diagnosis' => $diagnosis,
            'main' => $main,
            'report' => $data,
            // A standalone report has the one view only.
            'mode' => in_array($mode, ['egyen', 'csapat', 'szervezet'], true) && ! ($data['meta']['standalone'] ?? false) ? $mode : 'csapat',
            'individual' => collect($data['views']['individual'][$viewerId] ?? []),
            'organizationView' => collect($data['views']['organization'] ?? []),
            'chapterKeys' => array_keys($data['chapters']),
            'updates' => $main === null ? collect() : ChapterUpdate::query()->where('main_diagnosis_id', $main->id)->latest('id')->get()->unique('chapter')->keyBy('chapter'),
            'dots' => $current === null ? null : $this->dots($data, $url, $current, $hasSummary),
            // The Képesség card of R0 and R1 lists the approved capabilities of a published diagnosis (NM-12).
            'capabilities' => $this->capabilities($main, $viewerId, $preview),
            'nav' => $this->nav($page, $data, $url, $key, $extra, $hasSummary),
            ...$extra,
        ]);
    }

    /**
     * @return array{rows: list<array<string, mixed>>, strengths: list<array<string, mixed>>, gaps: list<array<string, mixed>>}|null
     */
    private function capabilities(?MainDiagnosis $main, ?int $viewerId, bool $preview): ?array
    {
        $viewer = $viewerId === null ? null : User::query()->find($viewerId);

        if ($main === null || $preview || $viewer === null) {
            return null;
        }

        $overview = app(CapabilityOverview::class)->for($main, $viewer);

        return $overview['rows'] === [] ? null : $overview;
    }

    /**
     * R0–R8 under the title of R0, R1 and the chapters: a link where the page
     * exists, none for a missing chapter, "hamarosan" for what is not built.
     *
     * @param  array<string, mixed>  $data
     * @return list<array{label: string, href: ?string, current: bool, soon: bool}>
     */
    private function dots(array $data, Closure $url, string $current, bool $hasSummary): array
    {
        return collect(self::STEPS)->map(fn (string $step) => [
            'label' => __('report.dots.'.$step),
            'href' => match (true) {
                $step === 'answer' => $url('show'),
                $step === 'summary' => $hasSummary ? $url('summary') : null,
                isset(ReportService::SLUGS[$step]) && isset($data['chapters'][$step]) => $url('chapter', [ReportService::SLUGS[$step]]),
                default => null,
            },
            'current' => $step === $current,
            'soon' => in_array($step, self::SOON, true),
        ])->all();
    }

    /**
     * The footer: where the reader is, and the one next page (prototype .chf).
     * After the brand base comes the closing page while R6–R8 are not built.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $extra
     * @return array{status: ?string, next: ?string, nextLabel: ?string}
     */
    private function nav(string $page, array $data, Closure $url, ?string $key, array $extra, bool $hasSummary): array
    {
        $snapshot = $data['meta']['snapshot'];
        $keys = array_keys($data['chapters']);
        $chapterAfter = function (?string $after) use ($keys, $url, $data): array {
            $next = $after === null ? ($keys[0] ?? null) : ($keys[array_search($after, $keys, true) + 1] ?? null);

            return $next === null
                ? [$url('closing'), ReportText::copy('pages.closing', $data['meta'])]
                : [$url('chapter', [ReportService::SLUGS[$next]]), __('report.chapters.'.$next)];
        };
        $step = fn (string $step) => __('report.footer.step', ['current' => array_search($step, self::STEPS, true), 'chapter' => __('report.dots.'.$step)]);

        [$status, [$next, $nextLabel]] = match (true) {
            $page === 'show' && $snapshot => [null, [null, null]],
            $page === 'show' => [$step('answer'), $hasSummary ? [$url('summary'), __('report.dots.summary')] : $chapterAfter(null)],
            $page === 'summary' => [$step('summary'), [$url('internal'), __('report.pages.internal')]],
            $page === 'internal' => [__('report.footer.internal'), $chapterAfter(null)],
            $page === 'compare' => [__('report.footer.compare'), [$url('internal'), __('report.pages.internal')]],
            $page === 'chapter' && $snapshot => [null, [$url('show'), __('report.pages.snapshot')]],
            $page === 'chapter' => [$step($key), $chapterAfter($key)],
            $page === 'item' => [
                __('report.footer.item', ['label' => $extra['item']['label'], 'current' => $extra['position']['current'], 'total' => $extra['position']['total'], 'noun' => __('report.nouns.'.$key)]),
                $this->itemAfter($data, $url, $key, $extra, $chapterAfter),
            ],
            default => [null, [null, null]],
        };

        return ['status' => $status, 'next' => $next, 'nextLabel' => $nextLabel];
    }

    /**
     * The next item of the chapter, then the next chapter (27 · 27.5).
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $extra
     * @return array{0: ?string, 1: ?string}
     */
    private function itemAfter(array $data, Closure $url, string $key, array $extra, Closure $chapterAfter): array
    {
        $following = $data['chapters'][$key]['rows'][$extra['position']['current']] ?? null;

        return match (true) {
            $following !== null => [$url('item', [ReportService::SLUGS[$key], $following['id']]), $following['label']],
            $data['meta']['snapshot'] => [$url('show'), __('report.pages.snapshot')],
            default => $chapterAfter($key),
        };
    }
}
