<?php

use App\Models\Organization;
use App\Models\Value;
use App\Services\Engine\ValueCatalog;

it('describes a value by its definition and boundaries, never by a name', function () {
    Value::factory()->create([
        'name' => 'Kíváncsiság',
        'name_en' => 'Curious',
        'definition' => 'Érdeklődés mindenre.',
        'false_positives' => ['Kötelező e-learning (Conformity)'],
        'overlaps' => ['Creativity (SDT, ugyanaz a pólus)', 'Wisdom'],
        'literature' => ['Kashdan CEI-II'],
        'keywords' => ['exploráció'],
    ]);
    Value::factory()->create(['name' => 'Kreativitás', 'name_en' => 'Creativity']);
    Value::factory()->archived()->create(['name' => 'Bölcsesség', 'name_en' => 'Wisdom']);

    expect(ValueCatalog::forOrganization(null)->forPrompt())
        ->toContain("[E1]\nDefiníció: Érdeklődés mindenre.\nMit jelent: ")
        ->toContain("\nHamis pozitívok (hasonlít, de nem ez): Kötelező e-learning (Conformity)\n")
        ->toContain("\nÁtfedések más értékekkel: [E2] (SDT, ugyanaz a pólus) | egy másik érték\n")
        ->toContain("\nJellemző kifejezések: exploráció")
        ->not->toContain('Kíváncsiság')
        ->not->toContain('Curious')
        ->not->toContain('Creativity')
        ->not->toContain('Wisdom')
        ->not->toContain('Kashdan');
});

it('leaves ordinary words that look like a value name alone', function () {
    Value::factory()->create(['name_en' => 'Clean', 'examples' => ['clean desk policy', 'Cleanliness drive', 'Clean (SE)']]);

    expect(ValueCatalog::forOrganization(null)->forPrompt())->toContain('Példák: clean desk policy | Cleanliness drive | [E1] (SE)');
});

it('puts the organization’s own words in place of the definition, with the boundaries', function () {
    $organization = Organization::factory()->create();
    $value = Value::factory()->create([
        'definition' => 'Érdeklődés mindenre.',
        'false_positives' => ['Kötelező e-learning.'],
        'keywords' => ['exploráció'],
    ]);

    $catalog = ValueCatalog::forDiagnosis($organization, ['values' => [['value_id' => $value->id, 'meaning' => 'Nálunk kérdezünk.']]]);

    expect($catalog->forScoring([$value->id]))
        ->toBe("[E1]\nMit jelent: Nálunk kérdezünk.\nHamis pozitívok (hasonlít, de nem ez): Kötelező e-learning.\nJellemző kifejezések: exploráció")
        ->and($catalog->resolve('[e1]')?->is($value))->toBeTrue();
});
