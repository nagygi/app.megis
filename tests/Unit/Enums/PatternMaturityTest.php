<?php

use App\Enums\PatternMaturity;
use App\Enums\PatternOrigin;
use App\Enums\PatternValidity;

it('computes the maturity like the formula of the mégis Sheet (NM-19)', function (PatternValidity $validity, int $clients, ?PatternOrigin $origin, PatternMaturity $expected) {
    expect(PatternMaturity::compute($validity, $clients, $origin))->toBe($expected);
})->with([
    'rejected first, even with clients' => [PatternValidity::Rejected, 3, PatternOrigin::OwnCase, PatternMaturity::Rejected],
    'obsolete' => [PatternValidity::Obsolete, 2, PatternOrigin::OwnCase, PatternMaturity::Obsolete],
    'two clients: used' => [PatternValidity::Valid, 2, PatternOrigin::External, PatternMaturity::Used],
    'one client: seen once' => [PatternValidity::Valid, 1, PatternOrigin::External, PatternMaturity::SeenOnce],
    'external source: literature' => [PatternValidity::Valid, 0, PatternOrigin::External, PatternMaturity::HypothesisLiterature],
    'both: literature' => [PatternValidity::Valid, 0, PatternOrigin::Both, PatternMaturity::HypothesisLiterature],
    'own case: own observation' => [PatternValidity::Valid, 0, PatternOrigin::OwnCase, PatternMaturity::HypothesisOwn],
    'no origin: own observation' => [PatternValidity::Valid, 0, null, PatternMaturity::HypothesisOwn],
]);

it('knows the hypotheses, whose first finding a person approves (NY-26)', function () {
    expect(PatternMaturity::HypothesisLiterature->isHypothesis())->toBeTrue()
        ->and(PatternMaturity::HypothesisOwn->isHypothesis())->toBeTrue()
        ->and(PatternMaturity::SeenOnce->isHypothesis())->toBeFalse();
});
