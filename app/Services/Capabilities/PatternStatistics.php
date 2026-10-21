<?php

namespace App\Services\Capabilities;

use App\Enums\FindingStatus;
use App\Enums\InboxItemType;
use App\Models\CapabilityFinding;
use App\Models\Pattern;
use App\Models\PatternObservation;
use App\Services\Inbox\InboxService;

/**
 * The product's counters of a pattern (NM-12, NY-27): the clients with a
 * confirmed finding, the proposals and the false alarms. A pattern with many
 * rejected proposals goes to the mégis for review.
 */
class PatternStatistics
{
    public function __construct(private InboxService $inbox) {}

    public function record(CapabilityFinding $finding, FindingStatus $outcome): void
    {
        $finding->loadMissing(['pattern', 'material.organization']);
        $organization = $finding->material?->organization;

        if ($finding->pattern === null || $organization === null) {
            return;
        }

        PatternObservation::query()->create([
            'pattern_id' => $finding->pattern_id,
            'organization_key' => PatternObservation::keyFor($organization),
            'outcome' => $outcome,
            'observed_on' => today(),
        ]);

        $this->refresh($finding->pattern);
    }

    public function refresh(Pattern $pattern): void
    {
        $observations = PatternObservation::query()->where('pattern_id', $pattern->id)->get();
        $suggestions = $observations->count();
        $falseAlarms = $observations->where('outcome', FindingStatus::Rejected)->count();

        $pattern->forceFill([
            'approved_organizations' => $observations->where('outcome', FindingStatus::Confirmed)->pluck('organization_key')->unique()->count(),
            'suggestions' => $suggestions,
            'false_alarms' => $falseAlarms,
        ]);

        $minimum = (int) config('megis.capabilities.false_alarm_review.min_suggestions');
        $ratio = (float) config('megis.capabilities.false_alarm_review.ratio');

        if ($pattern->review_flagged_at === null && $suggestions >= $minimum && $falseAlarms / max(1, $suggestions) >= $ratio) {
            $pattern->review_flagged_at = now();

            $this->inbox->open(
                InboxItemType::PatternReview,
                __('capabilities.inbox.pattern_review', ['code' => $pattern->code, 'name' => $pattern->name, 'false' => $falseAlarms, 'total' => $suggestions]),
                subject: $pattern,
                dedupeKey: 'pattern-review:'.$pattern->id,
            );
        }

        $pattern->save();
    }
}
