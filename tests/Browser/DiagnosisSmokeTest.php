<?php

use App\Enums\ConsentKind;
use App\Enums\StepMark;
use App\Models\Consent;
use App\Services\MainDiagnoses\ExperienceService;
use App\Services\MainDiagnoses\MainDiagnosisService;
use App\Services\Organizations\OrganizationValueSetService;

/**
 * Sets every answer of the experience page through Livewire; the radios are visually hidden.
 */
function answerExperience(mixed $page, array $keys): void
{
    foreach ($keys as $key) {
        $page->script("() => window.Livewire.all()[0].\$wire.set('answers.{$key}', '4')");
    }
}

it('walks the leader from V1 to "Indul a diagnózis"', function () {
    $setup = diagnosisTeam(peers: 1);
    $main = startMainDiagnosis($setup);
    $keys = collect($setup['organization']->valueSet->items)->map(fn ($item) => 'value:'.$item->key)->merge(['tree:root', 'tree:trunk', 'tree:crown', 'tree:shadow'])->all();

    $page = loginInBrowser($setup['leader']);
    $page->assertSee('Mi vár rád')->assertSee('6 lépés, és indul a diagnózis.')->assertNoAccessibilityIssues();

    $page->click('Kezdjük');
    $page->assertSee('Miben nem alkuszol')->assertNoAccessibilityIssues();

    $page->click('Hozzáadom vagy kiválasztom');
    $page->assertSee('Érték hozzáadása');
    $page->fill('draft-name', 'Kíváncsiság')->fill('draft-meaning', 'Megkérdezem, mi történt.');
    $page->press('Hozzáadom');
    $page->assertSee('Ha továbblépsz, ezek lesznek a saját értékeid.')->assertNoAccessibilityIssues();

    $page->press('Tovább');
    $page->assertSee('Mit mondtok ki közösen?')->assertNoAccessibilityIssues();
    $page->press('Tovább');
    $page->assertSee('Mit mond rólatok a fa?')->assertNoAccessibilityIssues();
    $page->press('Tovább');

    $page->assertSee('Hogyan látjátok ma a márkát?')->assertNoAccessibilityIssues();
    answerExperience($page, $keys);
    $page->assertSee('7 / 7 megválaszolva');
    $page->press('Tovább');

    $page->assertSee('Milyen folyamataid vannak?')->assertNoAccessibilityIssues();
    $page->press('Kihagyom');

    $page->assertSee('Egy lap, a diagnózis végéig érvényes.')->assertNoAccessibilityIssues();
    $page->press('Indul a diagnózis');

    // V10: the home while it runs (26 · 26.1).
    $page->assertSee('Hol tartunk')->assertNoAccessibilityIssues();
});

it('saves an empty brand as the leader leaves each field and lets the leader go on (V4)', function () {
    $setup = diagnosisTeam(peers: 1, withValues: false);
    $set = organizationValues($setup['organization'], withBrand: false);
    $main = startMainDiagnosis($setup);

    $page = loginInBrowser($setup['leader']);
    $page->navigate(route('client.diagnosis.markad-alapja', [$setup['organization'], $main->diagnosis]));
    $page->assertSee('1 / 4 kész')->assertButtonDisabled('Tovább');

    $page->fill('brand-purpose', 'Hogy a csapat kiszámítható legyen.')->keys('#brand-purpose', 'Tab');
    $page->fill('brand-value_proposition', 'Gyors, őszinte visszajelzés.')->keys('#brand-value_proposition', 'Tab');
    $page->fill('brand-impact', 'Kevesebb újramunka.')->keys('#brand-impact', 'Tab');

    $page->waitForText('4 / 4 kész')->assertButtonEnabled('Tovább')->assertNoJavaScriptErrors();
    expect($set->refresh()->only(['purpose', 'value_proposition', 'impact']))->toBe([
        'purpose' => 'Hogy a csapat kiszámítható legyen.',
        'value_proposition' => 'Gyors, őszinte visszajelzés.',
        'impact' => 'Kevesebb újramunka.',
    ]);
});

it('walks a peer from the consent to the experience', function () {
    $setup = diagnosisTeam(peers: 1);
    $main = startMainDiagnosis($setup);
    confirmOwnValues($setup['leader']);
    $version = app(OrganizationValueSetService::class)->forOrganization($setup['organization'])->latestVersion;
    $rows = app(ExperienceService::class)->rows($version->snapshot);
    app(ExperienceService::class)->submit($main, $setup['leader'], $version, collect($rows)->mapWithKeys(fn ($row) => [$row['key'] => '4'])->all());
    $main->stepMarks()->create(['user_id' => $setup['leader']->id, 'step' => 'processes', 'mark' => StepMark::Skipped]);
    app(MainDiagnosisService::class)->start($main->refresh(), $setup['leader']);

    $page = loginInBrowser($setup['peers'][0]);
    $page->assertSee('Hogyan látod te?')->assertNoAccessibilityIssues();

    $page->click('#answersConsent');
    $page->press('Kezdem');
    $page->assertSee('Két lépés most, egy, amíg fut.')->assertNoAccessibilityIssues();

    $page->click('Kezdjük');
    $page->click('Hozzáadom vagy kiválasztom');
    $page->fill('draft-name', 'Türelem')->fill('draft-meaning', 'Kivárom a végét.');
    $page->press('Hozzáadom');
    $page->press('Tovább');

    $page->assertSee('Hogyan látjátok ma a márkát?')->assertNoAccessibilityIssues();
    answerExperience($page, array_column($rows, 'key'));
    $page->press('Tovább');

    $page->assertSee('Amit eddig behoztál')->assertSee('Türelem')->assertNoAccessibilityIssues();
    expect(Consent::query()->where('user_id', $setup['peers'][0]->id)->value('kind'))->toBe(ConsentKind::Answers);
});
