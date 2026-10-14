<?php

namespace App\Services\Report;

use App\Models\ChapterUpdate;
use App\Models\MainDiagnosis;
use Closure;
use Illuminate\View\View;

/**
 * Renders a page of the report from its data (27): the client reads the
 * frozen snapshot, the admin's preview the live data (21 · 21.1). The same
 * views either way; only the links differ.
 */
class ReportPages
{
    public const PAGES = ['show', 'summary', 'internal', 'chapter', 'item', 'compare', 'closing', 'appendix'];

    /**
     * @param  array<string, mixed>  $data
     * @param  Closure(string, array<int, mixed>=): string  $url
     */
    public function render(string $page, MainDiagnosis $main, array $data, ?string $chapter, ?int $row, Closure $url, ?string $homeUrl, ?int $viewerId, ?string $mode, bool $preview): View
    {
        abort_unless(in_array($page, self::PAGES, true), 404);
        $snapshot = $data['meta']['snapshot'];
        abort_if($snapshot && in_array($page, ['summary', 'internal', 'compare'], true), 404);

        $extra = [];

        if (in_array($page, ['chapter', 'item'], true)) {
            $key = $chapter === null ? null : ReportService::chapterFromSlug($chapter);
            abort_if($key === null || ! isset($data['chapters'][$key]), 404);
            $extra['chapter'] = $data['chapters'][$key];

            if ($page === 'item') {
                $rows = collect($data['chapters'][$key]['rows']);
                $position = $rows->search(fn (array $candidate) => $candidate['id'] === $row);
                abort_if($position === false, 404);
                $extra['item'] = $rows->get($position);
                $extra['next'] = $rows->get($position + 1);
            }
        }

        $view = match ($page) {
            'show' => $snapshot ? 'client.report.snapshot' : 'client.report.answer',
            default => 'client.report.'.$page,
        };

        return view($view, [
            'url' => $url,
            'homeUrl' => $homeUrl,
            'preview' => $preview,
            'organization' => $main->diagnosis->organization,
            'diagnosis' => $main->diagnosis,
            'main' => $main,
            'report' => $data,
            'mode' => in_array($mode, ['egyen', 'csapat', 'szervezet'], true) ? $mode : 'csapat',
            'individual' => collect($data['views']['individual'][$viewerId] ?? []),
            'organizationView' => collect($data['views']['organization'] ?? []),
            'chapterKeys' => array_keys($data['chapters']),
            'updates' => ChapterUpdate::query()->where('main_diagnosis_id', $main->id)->latest('id')->get()->unique('chapter')->keyBy('chapter'),
            ...$extra,
        ]);
    }
}
