<?php

use App\Enums\CapabilityConfidence;
use App\Enums\CapabilityLevel;
use App\Enums\CapabilityState;
use App\Enums\FindingKind;
use App\Enums\FindingStatus;
use App\Enums\PatternSubtype;
use App\Enums\PreconditionValue;
use App\Enums\TraceDirection;
use App\Enums\TraceDirectness;
use App\Services\Capabilities\Measurement\CapabilityStateCalculator;
use App\Services\Capabilities\Measurement\FindingInput;
use App\Services\Capabilities\Measurement\PreconditionCalculator;
use App\Services\Capabilities\Measurement\StateResult;
use App\Services\Capabilities\Measurement\SubjectContext;
use App\Services\Capabilities\Measurement\TraceInput;

function stateTrace(int $id, string $person = 'p1', string $occasion = 'o1', array $overrides = []): TraceInput
{
    return new TraceInput(
        id: $id,
        personKey: $person,
        method: $overrides['method'] ?? 'conversation',
        occasionKey: $occasion,
        isRaw: $overrides['isRaw'] ?? true,
        directness: $overrides['directness'] ?? TraceDirectness::Observed,
        direction: $overrides['direction'] ?? TraceDirection::Positive,
        flags: $overrides['flags'] ?? [],
        externalVoice: $overrides['externalVoice'] ?? false,
    );
}

function stateFinding(?PatternSubtype $subtype, array $traces, FindingStatus $status = FindingStatus::Confirmed, array $overrides = []): FindingInput
{
    return new FindingInput(
        kind: $overrides['kind'] ?? FindingKind::Pattern,
        subtype: $subtype,
        status: $status,
        traces: $traces,
        needsFirstApproval: $overrides['needsFirstApproval'] ?? false,
    );
}

function stateOf(array $findings, array $context = []): StateResult
{
    return (new CapabilityStateCalculator)->calculate($findings, new SubjectContext(
        level: $context['level'] ?? CapabilityLevel::Team,
        tempting: $context['tempting'] ?? false,
        organizationSize: $context['organizationSize'] ?? 12,
        sourcesExisted: $context['sourcesExisted'] ?? true,
        auditSensitive: $context['auditSensitive'] ?? false,
    ));
}

function ruleKeys(StateResult $result): array
{
    return array_map(fn (array $rule) => $rule['rule'].':'.$rule['key'], $result->basis);
}

it('says "nincs nyoma" or "nincs adat" without a counting finding (AL-01)', function () {
    expect(stateOf([])->state)->toBe(CapabilityState::NoTrace)
        ->and(stateOf([], ['sourcesExisted' => false])->state)->toBe(CapabilityState::NoData)
        ->and(stateOf([])->confidence)->toBeNull();
});

it('is present with a presence signal and absent with an absence or anti-pattern (AL-02, AL-03)', function () {
    expect(stateOf([stateFinding(PatternSubtype::Presence, [stateTrace(1)])])->state)->toBe(CapabilityState::Present)
        ->and(stateOf([stateFinding(PatternSubtype::Absence, [stateTrace(1)])])->state)->toBe(CapabilityState::Absent)
        ->and(stateOf([stateFinding(PatternSubtype::AntiPattern, [stateTrace(1)])])->state)->toBe(CapabilityState::Absent);
});

it('leaves out rejected and borderline findings', function () {
    $result = stateOf([
        stateFinding(PatternSubtype::Presence, [stateTrace(1)], FindingStatus::Rejected),
        stateFinding(PatternSubtype::Borderline, [stateTrace(2)]),
    ]);

    expect($result->state)->toBe(CapabilityState::NoTrace);
});

it('counts a hypothesis finding and an interpretation only after a person approved them (NY-26)', function () {
    $proposed = stateOf([
        stateFinding(PatternSubtype::Presence, [stateTrace(1)], FindingStatus::Proposed, ['needsFirstApproval' => true]),
        stateFinding(null, [stateTrace(2)], FindingStatus::Proposed, ['kind' => FindingKind::Interpretation]),
    ]);
    $approved = stateOf([stateFinding(null, [stateTrace(2)], FindingStatus::Confirmed, ['kind' => FindingKind::Interpretation])]);

    expect($proposed->state)->toBe(CapabilityState::NoTrace)
        ->and($proposed->counts['pending'])->toBe(2)
        ->and(ruleKeys($proposed))->toContain('NY-26:pending')
        ->and($approved->state)->toBe(CapabilityState::Present);
});

it('lets the stronger side decide when both directions show, with a disputed confidence (AL-04, KO-04)', function () {
    $result = stateOf([
        stateFinding(PatternSubtype::Presence, [stateTrace(1, 'p1', 'o1')]),
        stateFinding(PatternSubtype::Absence, [stateTrace(2, 'p2', 'o1', ['direction' => TraceDirection::Negative]), stateTrace(3, 'p3', 'o2', ['direction' => TraceDirection::Negative])]),
    ]);

    expect($result->state)->toBe(CapabilityState::Absent)
        ->and($result->confidence)->toBe(CapabilityConfidence::Disputed)
        ->and(ruleKeys($result))->toContain('AL-04:both_directions', 'KO-04:disputed');
});

it('gives low for one source and medium for two independent ones, never high (KO-02, KO-03)', function () {
    $sameSource = stateOf([stateFinding(PatternSubtype::Presence, [stateTrace(1, 'p1', 'o1'), stateTrace(2, 'p1', 'o1')])]);
    $independent = stateOf([stateFinding(PatternSubtype::Presence, [stateTrace(1, 'p1', 'o1'), stateTrace(2, 'p2', 'o2')])]);
    $many = stateOf([stateFinding(PatternSubtype::Presence, [stateTrace(1, 'p1'), stateTrace(2, 'p2'), stateTrace(3, 'p3'), stateTrace(4, 'p4', 'o2')])]);

    expect($sameSource->confidence)->toBe(CapabilityConfidence::Low)
        ->and($independent->confidence)->toBe(CapabilityConfidence::Medium)
        ->and($many->confidence)->toBe(CapabilityConfidence::Medium);
});

it('gives low when only processed, marked or self-reported traces found it (KO-03, 6.1)', function () {
    $processed = stateOf([stateFinding(PatternSubtype::Presence, [stateTrace(1, 'p1', 'o1', ['isRaw' => false]), stateTrace(2, 'p2', 'o2', ['isRaw' => false])])]);
    $marked = stateOf([stateFinding(PatternSubtype::Presence, [stateTrace(1, 'p1', 'o1', ['flags' => ['led_answer']]), stateTrace(2, 'p2', 'o2', ['flags' => ['third_person']])])]);
    $selfReport = stateOf([stateFinding(PatternSubtype::Presence, [stateTrace(1, 'p1', 'o1', ['directness' => TraceDirectness::SelfReport]), stateTrace(2, 'p2', 'o2', ['directness' => TraceDirectness::SelfReport])])]);

    expect($processed->confidence)->toBe(CapabilityConfidence::Low)
        ->and(ruleKeys($processed))->toContain('KO-03:only_weak')
        ->and($marked->confidence)->toBe(CapabilityConfidence::Low)
        ->and($selfReport->confidence)->toBe(CapabilityConfidence::Low)
        ->and(ruleKeys($selfReport))->toContain('KO-03:only_self_report');
});

it('lets a processed trace confirm a raw one, but not found a statement alone (6.1)', function () {
    $confirmed = stateOf([stateFinding(PatternSubtype::Presence, [stateTrace(1, 'p1', 'o1'), stateTrace(2, 'p2', 'o2', ['isRaw' => false])])]);

    expect($confirmed->confidence)->toBe(CapabilityConfidence::Medium);
});

it('needs two raw traces for medium on a tempting capability (KO-05)', function () {
    $oneRaw = [stateTrace(1, 'p1', 'o1'), stateTrace(2, 'p2', 'o2', ['isRaw' => false])];
    $twoRaw = [stateTrace(1, 'p1', 'o1'), stateTrace(2, 'p2', 'o2')];

    $tempting = stateOf([stateFinding(PatternSubtype::Presence, $oneRaw)], ['tempting' => true]);

    expect($tempting->confidence)->toBe(CapabilityConfidence::Low)
        ->and(ruleKeys($tempting))->toContain('KO-05:tempting')
        ->and(stateOf([stateFinding(PatternSubtype::Presence, $twoRaw)], ['tempting' => true])->confidence)->toBe(CapabilityConfidence::Medium);
});

it('caps the confidence by the level and the coverage (KO-06)', function () {
    $twoOccasionsOnePerson = [stateTrace(1, 'p1', 'o1'), stateTrace(2, 'p1', 'o2')];

    $individual = stateOf([stateFinding(PatternSubtype::Presence, $twoOccasionsOnePerson)], ['level' => CapabilityLevel::Individual]);
    $team = stateOf([stateFinding(PatternSubtype::Presence, $twoOccasionsOnePerson)], ['level' => CapabilityLevel::Team]);
    $organization = stateOf([stateFinding(PatternSubtype::Presence, [stateTrace(1, 'p1'), stateTrace(2, 'p2', 'o2')])], ['level' => CapabilityLevel::Organization]);
    $micro = stateOf([stateFinding(PatternSubtype::Presence, $twoOccasionsOnePerson)], ['level' => CapabilityLevel::Organization, 'organizationSize' => 2]);

    expect($individual->confidence)->toBe(CapabilityConfidence::Low)
        ->and(ruleKeys($individual))->toContain('KO-06:individual_own')
        ->and($team->confidence)->toBe(CapabilityConfidence::Low)
        ->and(ruleKeys($team))->toContain('KO-06:team_members')
        ->and($organization->confidence)->toBe(CapabilityConfidence::Low)
        // In a micro organization the method and the occasion make the sources independent.
        ->and($micro->confidence)->toBe(CapabilityConfidence::Medium)
        ->and(ruleKeys($micro))->toContain('KO-06:one_perspective');
});

it('needs an external voice for medium at the ecosystem level (KO-07)', function () {
    $internal = [stateTrace(1, 'p1', 'o1'), stateTrace(2, 'p2', 'o2')];
    $external = [stateTrace(1, 'p1', 'o1'), stateTrace(2, 'client', 'o2', ['externalVoice' => true])];

    expect(stateOf([stateFinding(PatternSubtype::Presence, $internal)], ['level' => CapabilityLevel::Ecosystem])->confidence)->toBe(CapabilityConfidence::Low)
        ->and(stateOf([stateFinding(PatternSubtype::Presence, $external)], ['level' => CapabilityLevel::Ecosystem])->confidence)->toBe(CapabilityConfidence::Medium);
});

it('takes an audit report as presence only where it may found it (6.1)', function () {
    $traces = [stateTrace(1, 'p1', 'o1', ['flags' => ['audit_report']]), stateTrace(2, 'p2', 'o2', ['flags' => ['audit_report']])];

    expect(stateOf([stateFinding(PatternSubtype::Presence, $traces)])->confidence)->toBe(CapabilityConfidence::Medium)
        ->and(stateOf([stateFinding(PatternSubtype::Presence, $traces)], ['auditSensitive' => true])->confidence)->toBe(CapabilityConfidence::Low);
});

it('reads a precondition from the directions of its traces (6.7)', function (array $directions, PreconditionValue $expected) {
    expect((new PreconditionCalculator)->value($directions))->toBe($expected);
})->with([
    'only for it' => [[TraceDirection::Positive], PreconditionValue::Present],
    'only against it' => [[TraceDirection::Negative, TraceDirection::Negative], PreconditionValue::Absent],
    'both' => [[TraceDirection::Positive, TraceDirection::Negative], PreconditionValue::Partial],
    'mixed' => [[TraceDirection::Mixed], PreconditionValue::Partial],
    'nothing' => [[], PreconditionValue::Unknown],
]);
