<?php

use App\Models\Value;
use App\Models\ValueCategory;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

/**
 * @param  list<list<string>>  $rows  the first row is the header
 */
function valuesSpreadsheet(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'ertektar').'.xlsx';
    $writer = new Writer;
    $writer->openToFile($path);

    foreach ($rows as $row) {
        $writer->addRow(Row::fromValues($row));
    }

    $writer->close();

    return $path;
}

function valuesHeader(): array
{
    return ['Név', 'Kategória', 'Mit jelent?', 'Mit nem jelent?', '6 · Mikor teljesül nagyon?', '1 · Mikor nagyon nem teljesül?', 'Példák', 'Szinonimák, kulcsszavak'];
}

it('loads every value as v1 with its category and multi-line lists', function () {
    $path = valuesSpreadsheet([
        valuesHeader(),
        ['Bátorság', 'Önmeghaladás', "- Kimondom.\n- Vállalom.", '', 'A rossz hír aznap kimegy.', 'Hallgatok.', '', "merészség\nkurázsi"],
        ['Kíváncsiság', '', 'Kérdezek.', '', 'A kérdés az első mondat.', 'Már eldöntöttem.', '', ''],
    ]);

    $this->artisan('megis:values:import', ['file' => $path])
        ->expectsOutputToContain('Új kategória: Önmeghaladás')
        ->expectsOutputToContain('2 érték bekerült az Értéktárba, mindegyik v1-ként.')
        ->assertSuccessful();

    $courage = Value::query()->firstWhere('name', 'Bátorság');
    expect($courage)
        ->meanings->toBe(['Kimondom.', 'Vállalom.'])
        ->keywords->toBe(['merészség', 'kurázsi'])
        ->reviewed_at->not->toBeNull()
        ->and($courage->category->name)->toBe('Önmeghaladás')
        ->and($courage->latestVersion->number)->toBe(1)
        ->and(Value::query()->firstWhere('name', 'Kíváncsiság')->value_category_id)->toBeNull();
});

it('loads nothing when a row misses a scale end', function () {
    $path = valuesSpreadsheet([
        valuesHeader(),
        ['Bátorság', '', 'Kimondom.', '', 'A rossz hír aznap kimegy.', 'Hallgatok.', '', ''],
        ['Kíváncsiság', '', 'Kérdezek.', '', '', 'Már eldöntöttem.', '', ''],
    ]);

    $this->artisan('megis:values:import', ['file' => $path])
        ->expectsOutputToContain('3. sor:')
        ->expectsOutputToContain('Hibás sorok miatt semmi nem került be.')
        ->assertFailed();

    expect(Value::query()->count())->toBe(0)
        ->and(ValueCategory::query()->count())->toBe(0);
});

it('skips values already in the Értéktár, so it can run again', function () {
    Value::factory()->create(['name' => 'Bátorság']);
    $path = valuesSpreadsheet([
        valuesHeader(),
        ['Bátorság', '', 'Kimondom.', '', 'A rossz hír aznap kimegy.', 'Hallgatok.', '', ''],
    ]);

    $this->artisan('megis:values:import', ['file' => $path])
        ->expectsOutputToContain('Már az Értéktárban van, kimarad: Bátorság')
        ->expectsOutputToContain('0 érték bekerült')
        ->assertSuccessful();

    expect(Value::query()->count())->toBe(1);
});

it('only checks the file on a dry run', function () {
    $path = valuesSpreadsheet([
        valuesHeader(),
        ['Bátorság', '', 'Kimondom.', '', 'A rossz hír aznap kimegy.', 'Hallgatok.', '', ''],
    ]);

    $this->artisan('megis:values:import', ['file' => $path, '--dry-run' => true])
        ->expectsOutputToContain('1 érték kerülne be')
        ->assertSuccessful();

    expect(Value::query()->count())->toBe(0);
});

it('loads the sample file in docs', function () {
    $this->artisan('megis:values:import', ['file' => base_path('docs/ertektar-import-minta.xlsx')])
        ->expectsOutputToContain('3 érték bekerült')
        ->assertSuccessful();

    expect(Value::query()->firstWhere('name', 'Felelősségvállalás'))
        ->meanings->toBe(['Vállalom a döntésem következményét.', 'Szólok, ha hibáztam.'])
        ->and(Value::query()->firstWhere('name', 'Biztonság')->keywords)->toBe(['kiszámíthatóság', 'stabilitás', 'rend']);
});

it('refuses a file without the name column', function () {
    $path = valuesSpreadsheet([['Érték', 'Leírás'], ['Bátorság', 'x']]);

    $this->artisan('megis:values:import', ['file' => $path])
        ->expectsOutputToContain('Hiányzik a „Név” oszlop')
        ->assertFailed();
});

it('refuses a missing or unsupported file', function () {
    $this->artisan('megis:values:import', ['file' => 'nincs-ilyen.xlsx'])->assertFailed();
});
