<?php

namespace App\Services\Capabilities\Measurement;

use App\Enums\CapabilityConfidence;
use App\Enums\CapabilityLevel;
use App\Enums\CapabilityState;
use App\Enums\FindingKind;
use App\Enums\FindingStatus;
use App\Enums\PatternSubtype;
use App\Enums\TraceDirection;
use App\Enums\TraceDirectness;

/**
 * The state and the confidence of a capability at a level, from its
 * findings, by the rules of the Szabálykönyv (AL-01 – AL-04, KO-02 – KO-07).
 * Pure: no database, no translation, so every rule has a unit test.
 */
final class CapabilityStateCalculator
{
    /**
     * Marks that make a trace only confirm (6.1); an audit only for the
     * capabilities where it is no presence signal on its own.
     */
    private const WEAK_FLAGS = [
        'led_answer', 'third_person', 'old_event', 'self_presentation', 'absence_trace',
        'positive_prompt', 'echoed_opinion', 'ai_corrected',
    ];

    /**
     * @param  list<FindingInput>  $findings
     */
    public function calculate(array $findings, SubjectContext $context): StateResult
    {
        $basis = [];
        $pending = 0;
        $positive = [];
        $negative = [];

        foreach ($findings as $finding) {
            // A rejected proposal and a borderline case say nothing about the capability.
            if ($finding->status === FindingStatus::Rejected || $finding->subtype === PatternSubtype::Borderline) {
                continue;
            }

            // NY-26 and the interpretation of a capability without patterns: a person approves first.
            if ($finding->status === FindingStatus::Proposed && ($finding->needsFirstApproval || $finding->kind === FindingKind::Interpretation)) {
                $pending++;

                continue;
            }

            match ($this->sign($finding)) {
                TraceDirection::Positive => $positive[] = $finding,
                TraceDirection::Negative => $negative[] = $finding,
                default => null,
            };
        }

        if ($pending > 0) {
            $basis[] = self::rule('NY-26', 'pending', ['count' => $pending]);
        }

        $positiveTraces = $this->traces($positive);
        $negativeTraces = $this->traces($negative);

        if ($positive === [] && $negative === []) {
            $basis[] = self::rule('AL-01', $context->sourcesExisted ? 'no_trace' : 'no_data');

            return new StateResult(
                $context->sourcesExisted ? CapabilityState::NoTrace : CapabilityState::NoData,
                null,
                $basis,
                $this->counts([], [], [], $pending),
            );
        }

        // Both directions (AL-04): the stronger traces decide, the confidence is disputed.
        if ($positive !== [] && $negative !== []) {
            $positiveStrength = $this->strength($positive, $positiveTraces, $context);
            $negativeStrength = $this->strength($negative, $negativeTraces, $context);
            $state = $positiveStrength > $negativeStrength ? CapabilityState::Present : CapabilityState::Absent;
            $basis[] = self::rule('AL-04', 'both_directions', ['positive' => count($positiveTraces), 'negative' => count($negativeTraces)]);
            $basis[] = self::rule('KO-04', 'disputed');
            $winning = $state === CapabilityState::Present ? $positiveTraces : $negativeTraces;

            return new StateResult($state, CapabilityConfidence::Disputed, $basis, $this->counts($positiveTraces, $negativeTraces, $winning, $pending, $context));
        }

        $state = $positive !== [] ? CapabilityState::Present : CapabilityState::Absent;
        $traces = $positive !== [] ? $positiveTraces : $negativeTraces;
        $basis[] = self::rule($state === CapabilityState::Present ? 'AL-02' : 'AL-03', $state === CapabilityState::Present ? 'present' : 'absent');

        return new StateResult(
            $state,
            $this->confidence($traces, $context, $basis),
            $basis,
            $this->counts($positiveTraces, $negativeTraces, $traces, $pending, $context),
        );
    }

    /**
     * KO-02, KO-03, KO-05 – KO-07. The machine never gives "magas".
     *
     * @param  list<TraceInput>  $traces
     * @param  list<array{rule: string, key: string, params: array<string, int|string>}>  $basis
     */
    private function confidence(array $traces, SubjectContext $context, array &$basis): CapabilityConfidence
    {
        // A processed or marked trace only confirms (6.1): at least one raw, unmarked trace founds.
        $strong = $this->strong($traces, $context);

        if ($strong === []) {
            $basis[] = self::rule('KO-03', 'only_weak');

            return CapabilityConfidence::Low;
        }

        if (collect($traces)->every(fn (TraceInput $trace) => $trace->directness === TraceDirectness::SelfReport)) {
            $basis[] = self::rule('KO-03', 'only_self_report');

            return CapabilityConfidence::Low;
        }

        $units = $this->units($traces, $context);
        $persons = $this->persons($traces);

        if ($context->isMicro() && $persons === 1) {
            $basis[] = self::rule('KO-06', 'one_perspective');
        }

        if ($units < 2) {
            $basis[] = self::rule('KO-03', 'one_source', ['units' => $units]);

            return CapabilityConfidence::Low;
        }

        $basis[] = self::rule('KO-03', 'independent', ['units' => $units]);

        if ($context->tempting && count($strong) < 2) {
            $basis[] = self::rule('KO-05', 'tempting');

            return CapabilityConfidence::Low;
        }

        $coverage = match (true) {
            $context->isMicro() => null,
            $context->level === CapabilityLevel::Individual && $persons <= 1 => 'individual_own',
            $context->level === CapabilityLevel::Team && $persons < 2 => 'team_members',
            $context->level === CapabilityLevel::Organization && $persons <= 2 => 'organization_coverage',
            default => null,
        };

        if ($coverage !== null) {
            $basis[] = self::rule('KO-06', $coverage, ['persons' => $persons]);

            return CapabilityConfidence::Low;
        }

        if ($context->level === CapabilityLevel::Ecosystem && ! collect($traces)->contains(fn (TraceInput $trace) => $trace->externalVoice)) {
            $basis[] = self::rule('KO-07', 'external_voice');

            return CapabilityConfidence::Low;
        }

        return CapabilityConfidence::Medium;
    }

    /**
     * Raw and unmarked traces: they may found a statement (6.1, NY-02).
     *
     * @param  list<TraceInput>  $traces
     * @return list<TraceInput>
     */
    private function strong(array $traces, SubjectContext $context): array
    {
        $weak = $context->auditSensitive ? [...self::WEAK_FLAGS, 'audit_report'] : self::WEAK_FLAGS;

        return array_values(array_filter($traces, fn (TraceInput $trace) => $trace->isRaw
            && array_intersect($trace->flags, $weak) === []
            && $trace->directness !== TraceDirectness::Interpretation));
    }

    /**
     * Independent sources (KO-02): another person, method or occasion. In a
     * micro organization the method and the occasion give it (KO-06).
     *
     * @param  list<TraceInput>  $traces
     */
    private function units(array $traces, SubjectContext $context): int
    {
        return collect($traces)
            ->map(fn (TraceInput $trace) => $context->isMicro()
                ? $trace->method.'|'.$trace->occasionKey
                : $trace->personKey.'|'.$trace->method.'|'.$trace->occasionKey)
            ->unique()
            ->count();
    }

    /**
     * @param  list<TraceInput>  $traces
     */
    private function persons(array $traces): int
    {
        return collect($traces)->pluck('personKey')->unique()->count();
    }

    /**
     * Which side a finding is on: by its pattern, or by its traces for an interpretation.
     */
    private function sign(FindingInput $finding): ?TraceDirection
    {
        return match ($finding->subtype) {
            PatternSubtype::Presence => TraceDirection::Positive,
            PatternSubtype::Absence, PatternSubtype::AntiPattern => TraceDirection::Negative,
            default => $this->majority($finding->traces),
        };
    }

    /**
     * @param  list<TraceInput>  $traces
     */
    private function majority(array $traces): ?TraceDirection
    {
        $positive = collect($traces)->where('direction', TraceDirection::Positive)->count();
        $negative = collect($traces)->where('direction', TraceDirection::Negative)->count();

        return match (true) {
            $positive > $negative => TraceDirection::Positive,
            $negative > $positive => TraceDirection::Negative,
            default => null,
        };
    }

    /**
     * How strong one side is (AL-04): independent strong sources, then
     * confirmed findings, then traces.
     *
     * @param  list<FindingInput>  $findings
     * @param  list<TraceInput>  $traces
     * @return array{0: int, 1: int, 2: int}
     */
    private function strength(array $findings, array $traces, SubjectContext $context): array
    {
        return [
            $this->units($this->strong($traces, $context), $context),
            collect($findings)->where('status', FindingStatus::Confirmed)->count(),
            count($traces),
        ];
    }

    /**
     * @param  list<FindingInput>  $findings
     * @return list<TraceInput>
     */
    private function traces(array $findings): array
    {
        return collect($findings)->flatMap(fn (FindingInput $finding) => $finding->traces)->unique('id')->values()->all();
    }

    /**
     * @param  list<TraceInput>  $positive
     * @param  list<TraceInput>  $negative
     * @param  list<TraceInput>  $deciding
     * @return array{positive: int, negative: int, units: int, persons: int, pending: int}
     */
    private function counts(array $positive, array $negative, array $deciding, int $pending, ?SubjectContext $context = null): array
    {
        return [
            'positive' => count($positive),
            'negative' => count($negative),
            'units' => $context === null ? 0 : $this->units($deciding, $context),
            'persons' => $this->persons($deciding),
            'pending' => $pending,
        ];
    }

    /**
     * @param  array<string, int|string>  $params
     * @return array{rule: string, key: string, params: array<string, int|string>}
     */
    private static function rule(string $rule, string $key, array $params = []): array
    {
        return ['rule' => $rule, 'key' => $key, 'params' => $params];
    }
}
