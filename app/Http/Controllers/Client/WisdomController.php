<?php

namespace App\Http\Controllers\Client;

use App\Enums\TreeElement;
use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\OrganizationValueSet;
use App\Services\Report\ReportService;
use App\Services\Wisdom\WisdomRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Bölcsességtár (32): the mégis's method in one place, BT1 and one page per
 * topic; "Nálatok:" for those who see the organization's values, "A ti
 * %-otok" for those who see the report.
 */
class WisdomController extends Controller
{
    public function __construct(
        private WisdomRepository $topics,
        private ReportService $reports,
    ) {}

    public function index(Organization $organization): View
    {
        return view('client.wisdom.index', ['organization' => $organization, 'topics' => $this->topics->all()]);
    }

    public function show(Request $request, Organization $organization, string $topic): View
    {
        $data = $this->topics->find($topic);
        abort_if($data === null, 404);

        $position = array_search($topic, WisdomRepository::TOPICS, true);
        $next = WisdomRepository::TOPICS[$position + 1] ?? null;
        $report = $this->reports->reportsFor($request->user(), $organization)->first();

        return view('client.wisdom.show', [
            'organization' => $organization,
            'topic' => $data,
            'position' => $position + 1,
            'next' => $next === null ? null : $this->topics->find($next),
            'areas' => $topic === 'teruletek' ? Area::query()->orderBy('position')->get() : collect(),
            'ours' => $this->ours($request, $organization, $topic),
            'report' => $report,
            'chapterUrl' => $report === null || $data['chapter'] === null ? null : route('client.report.chapter', [$organization, $report->diagnosis, ReportService::SLUGS[$data['chapter']]]),
        ]);
    }

    /**
     * "Nálatok:" per item: the values at the root, the leader's answers on the
     * trunk, the crown and the shadow (32 · 32.2); for the organization's members.
     *
     * @return array<string, string>
     */
    private function ours(Request $request, Organization $organization, string $topic): array
    {
        if ($topic !== 'kuldetesfa' || ! Membership::query()->whereBelongsTo($organization)->where('user_id', $request->user()->id)->exists()) {
            return [];
        }

        $set = OrganizationValueSet::query()->whereBelongsTo($organization)->with('items')->first();

        if ($set === null) {
            return [];
        }

        return array_filter([
            TreeElement::Root->symbol() => $set->items->pluck('name')->implode(' · '),
            TreeElement::Trunk->symbol() => (string) $set->purpose,
            TreeElement::Crown->symbol() => (string) $set->value_proposition,
            TreeElement::Shadow->symbol() => (string) $set->impact,
        ]);
    }
}
