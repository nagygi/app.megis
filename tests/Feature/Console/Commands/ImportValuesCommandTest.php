<?php

use App\Enums\ValueGrade;
use App\Enums\ValueOrigin;
use App\Enums\ValueSvsStatus;
use App\Models\Value;
use App\Models\ValueCategory;
use App\Services\Values\ValueService;
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

/**
 * The header of the mégis table (docs/data/ÉRTÉKEK KITÖLTÖTT.xlsx).
 */
function megisValuesHeader(): array
{
    return [
        'id', 'name_en', 'name_hu', 'higher_order', 'category_en', 'category_hu', 'definition_en', 'definition_hu',
        'mit_jelent', 'mit_nem_jelent', 'mikor_teljesul_nagyon', 'mikor_nagyon_nem_teljesul', 'peldak', 'hamis_pozitivok',
        'atfedesek', 'szinonimak', 'szakirodalom', 'alatamasztottsag', 'transzponalhatosag', 'svs_index',
    ];
}

/**
 * A row of the mégis table, in the order of its header.
 *
 * @param  array<string, mixed>  $cells  by header
 * @return list<mixed>
 */
function megisValuesRow(array $cells = []): array
{
    $row = [
        'id' => 1,
        'name_en' => 'Curious',
        'name_hu' => 'Kíváncsiság',
        'higher_order' => 'Nyitottság a változásra (Openness to change)',
        'category_en' => 'Self-direction',
        'category_hu' => 'Önállóság',
        'definition_en' => 'Interested in everything, exploring',
        'definition_hu' => 'Érdeklődés mindenre; a megismerés önmagáért',
        'mit_jelent' => 'A nem tudás megengedése, kérdezés',
        'mit_nem_jelent' => 'Nem arousal-keresés (Stimulation) · nem benchmarking',
        'mikor_teljesul_nagyon' => 'A „nem tudom, nézzük meg” elfogadott mondat · Van védett idő a megértésre',
        'mikor_nagyon_nem_teljesul' => 'Kérdezni egyenlő az inkompetencia bevallásával',
        'peldak' => 'Learning day · Journal club · Journal club',
        'hamis_pozitivok' => 'Kötelező e-learning óraszám (Conformity) · Piackutatás eladási célból',
        'atfedesek' => 'Creativity (SDT) · Wisdom',
        'szinonimak' => 'érdeklődés · exploráció',
        'szakirodalom' => 'SVS: CURIOUS · Kashdan CEI-II; Amabile KEYS',
        'alatamasztottsag' => 'erős',
        'transzponalhatosag' => 'Közepes',
        'svs_index' => 'nem (X-kódolt)',
        ...$cells,
    ];

    return array_map(fn (string $header) => $row[$header], megisValuesHeader());
}

it('loads the table of the mégis with every field of the value card', function () {
    $path = valuesSpreadsheet([
        megisValuesHeader(),
        megisValuesRow(),
        megisValuesRow(['id' => '2.0', 'name_en' => 'Creativity', 'name_hu' => 'Kreativitás', 'alatamasztottsag' => 'GYENGE', 'svs_index' => 'igen']),
    ]);

    $this->artisan('megis:values:import', ['file' => $path])
        ->expectsOutputToContain('Új kategória: Önállóság (Self-direction)')
        ->expectsOutputToContain('2 érték bekerült az Értéktárba, mindegyik v1-ként.')
        ->assertSuccessful();

    $curious = Value::query()->firstWhere('code', '1');
    expect($curious)
        ->name->toBe('Kíváncsiság')
        ->name_en->toBe('Curious')
        ->definition->toBe('Érdeklődés mindenre; a megismerés önmagáért')
        ->definition_en->toBe('Interested in everything, exploring')
        ->meanings->toBe(['A nem tudás megengedése, kérdezés'])
        ->non_meanings->toBe(['Nem arousal-keresés (Stimulation)', 'nem benchmarking'])
        ->high_examples->toBe(['A „nem tudom, nézzük meg” elfogadott mondat', 'Van védett idő a megértésre'])
        ->examples->toBe(['Learning day', 'Journal club'])
        ->false_positives->toBe(['Kötelező e-learning óraszám (Conformity)', 'Piackutatás eladási célból'])
        ->overlaps->toBe(['Creativity (SDT)', 'Wisdom'])
        ->keywords->toBe(['érdeklődés', 'exploráció'])
        ->literature->toBe(['SVS: CURIOUS', 'Kashdan CEI-II; Amabile KEYS'])
        ->evidence->toBe(ValueGrade::Strong)
        ->transposability->toBe(ValueGrade::Medium)
        ->svs_status->toBe(ValueSvsStatus::XCoded)
        ->origin->toBe(ValueOrigin::Admin)
        ->and($curious->category)
        ->name->toBe('Önállóság')
        ->name_en->toBe('Self-direction')
        ->higher_order->toBe('Nyitottság a változásra (Openness to change)')
        ->and($curious->latestVersion->note)->toStartWith('Betöltés táblázatból (')
        ->and(Value::query()->firstWhere('code', '2'))
        ->name->toBe('Kreativitás')
        ->evidence->toBe(ValueGrade::Weak)
        ->svs_status->toBe(ValueSvsStatus::Yes)
        ->and(ValueCategory::query()->count())->toBe(1);
});

it('updates a value by its code with a new version, and changes nothing on a second run', function () {
    $this->artisan('megis:values:import', ['file' => valuesSpreadsheet([megisValuesHeader(), megisValuesRow()])])->assertSuccessful();
    $path = valuesSpreadsheet([
        megisValuesHeader(),
        megisValuesRow(['name_hu' => 'Kíváncsiság, érdeklődés', 'definition_hu' => 'A megismerés önmagáért.']),
    ]);

    $this->artisan('megis:values:import', ['file' => $path])
        ->expectsOutputToContain('Változik: Kíváncsiság, érdeklődés (Név, Definíció)')
        ->expectsOutputToContain('0 érték bekerült')
        ->expectsOutputToContain('1 érték új verzióval frissült, 0 változatlan maradt.')
        ->assertSuccessful();

    $value = Value::query()->sole();
    expect($value)
        ->name->toBe('Kíváncsiság, érdeklődés')
        ->slug->toBe('kivancsisag')
        ->and($value->latestVersion)
        ->number->toBe(2)
        ->changed_fields->toBe(['name', 'definition']);

    $this->artisan('megis:values:import', ['file' => $path])
        ->expectsOutputToContain('0 érték új verzióval frissült, 1 változatlan maradt.')
        ->doesntExpectOutputToContain('Kategória frissítve')
        ->assertSuccessful();

    expect($value->versions()->count())->toBe(2);
});

it('gives the code of the file to a value matched by its name', function () {
    $value = Value::factory()->create(['name' => 'Kíváncsiság']);

    $this->artisan('megis:values:import', ['file' => valuesSpreadsheet([megisValuesHeader(), megisValuesRow(['id' => 7])])])
        ->expectsOutputToContain('Változik: Kíváncsiság (Azonosító,')
        ->assertSuccessful();

    expect($value->refresh()->code)->toBe('7')
        ->and(Value::query()->count())->toBe(1);
});

it('shows what would change on a dry run and writes nothing', function () {
    $this->artisan('megis:values:import', ['file' => valuesSpreadsheet([megisValuesHeader(), megisValuesRow()])])->assertSuccessful();
    $path = valuesSpreadsheet([
        megisValuesHeader(),
        megisValuesRow(['definition_hu' => 'A megismerés önmagáért.', 'higher_order' => 'Nyitottság']),
        megisValuesRow(['id' => 2, 'name_hu' => 'Kreativitás', 'name_en' => 'Creativity', 'higher_order' => 'Nyitottság']),
    ]);

    $this->artisan('megis:values:import', ['file' => $path, '--dry-run' => true])
        ->expectsOutputToContain('Kategória frissítve: Önállóság (Self-direction)')
        ->expectsOutputToContain('Változik: Kíváncsiság (Definíció)')
        ->expectsOutputToContain('Ellenőrzés kész: 1 érték kerülne be újként, 1 változna, 0 változatlan maradna.')
        ->assertSuccessful();

    expect(Value::query()->sole())
        ->definition->toBe('Érdeklődés mindenre; a megismerés önmagáért')
        ->and(Value::query()->sole()->versions()->count())->toBe(1)
        ->and(ValueCategory::query()->sole()->higher_order)->toBe('Nyitottság a változásra (Openness to change)');
});

it('refuses a name or a code held by another value, and loads nothing', function () {
    Value::factory()->create(['code' => '1', 'name' => 'Kíváncsiság']);
    Value::factory()->create(['code' => '2', 'name' => 'Kreativitás']);
    Value::factory()->create(['code' => '9', 'name' => 'Szabadság']);

    $this->artisan('megis:values:import', ['file' => valuesSpreadsheet([
        megisValuesHeader(),
        megisValuesRow(['name_hu' => 'Kreativitás']),
        megisValuesRow(['id' => 3, 'name_hu' => 'Szabadság', 'name_en' => 'Freedom']),
        megisValuesRow(['id' => 4, 'name_hu' => 'Bátorság', 'name_en' => 'Daring']),
    ])])
        ->expectsOutputToContain('2. sor: a(z) „Kreativitás” név már egy másik értéké (azonosító: 2).')
        ->expectsOutputToContain('3. sor: a(z) „Szabadság” érték azonosítója 9, a táblázatban 3.')
        ->expectsOutputToContain('Hibás sorok miatt semmi nem került be.')
        ->assertFailed();

    expect(Value::query()->count())->toBe(3)
        ->and(Value::query()->firstWhere('code', '1')->name)->toBe('Kíváncsiság')
        ->and(ValueCategory::query()->count())->toBe(0);
});

it('refuses repeated codes, disagreeing categories and unknown ratings, and loads nothing', function () {
    $this->artisan('megis:values:import', ['file' => valuesSpreadsheet([
        megisValuesHeader(),
        megisValuesRow(),
        megisValuesRow(['name_hu' => 'Kreativitás']),
        megisValuesRow(['id' => 3, 'name_hu' => 'Szabadság', 'higher_order' => 'Megőrzés (Conservation)']),
        megisValuesRow(['id' => 4, 'name_hu' => 'Bátorság', 'alatamasztottsag' => 'nagyon erős']),
        megisValuesRow(['id' => 5, 'name_hu' => 'Önfegyelem', 'category_hu' => '']),
    ])])
        ->expectsOutputToContain('3. sor: a(z) „1” azonosító kétszer szerepel a táblázatban.')
        ->expectsOutputToContain('4. sor: a(z) „Önállóság” kategória „Felsőbb szint” mezője eltér a(z) 2. sorétól.')
        ->expectsOutputToContain('5. sor: ismeretlen érték a(z) „Szakirodalmi alátámasztottság” oszlopban: „nagyon erős”.')
        ->expectsOutputToContain('6. sor: angol kategórianév vagy felsőbb szint csak magyar kategórianévvel adható meg.')
        ->assertFailed();

    expect(Value::query()->count())->toBe(0)
        ->and(ValueCategory::query()->count())->toBe(0);
});

it('takes literature items up to 500 characters and other items up to 255', function () {
    $this->artisan('megis:values:import', ['file' => valuesSpreadsheet([megisValuesHeader(), megisValuesRow(['peldak' => str_repeat('a', 256)])])])
        ->expectsOutputToContain('2. sor:')
        ->assertFailed();

    $this->artisan('megis:values:import', ['file' => valuesSpreadsheet([megisValuesHeader(), megisValuesRow(['szakirodalom' => str_repeat('a', 500)])])])
        ->assertSuccessful();

    expect(Value::query()->sole()->literature)->toBe([str_repeat('a', 500)]);
});

it('loads the table of the mégis in docs/data, and a second run changes nothing', function () {
    $path = base_path('docs/data/ÉRTÉKEK KITÖLTÖTT.xlsx');

    $this->artisan('megis:values:import', ['file' => $path])
        ->expectsOutputToContain('58 érték bekerült')
        ->assertSuccessful();

    expect(Value::query()->count())->toBe(58)
        ->and(ValueCategory::query()->count())->toBe(10)
        ->and(ValueCategory::query()->distinct()->count('higher_order'))->toBe(5)
        ->and(Value::query()->whereNull('definition')->count())->toBe(0)
        ->and(Value::query()->firstWhere('code', '11'))
        ->name->toBe('Élvezet')
        ->and(Value::query()->firstWhere('code', '11')->category->higher_order)->toContain('(határérték)');

    $this->artisan('megis:values:import', ['file' => $path])
        ->expectsOutputToContain('0 érték új verzióval frissült, 58 változatlan maradt.')
        ->assertSuccessful();
})->skip(fn () => ! is_file(base_path('docs/data/ÉRTÉKEK KITÖLTÖTT.xlsx')), 'A mégis táblázata nincs a docs/data alatt.');

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

it('updates a value already in the Értéktár by its name with a new version, and marks it reviewed', function () {
    $value = app(ValueService::class)->createElsewhere('Bátorság', ValueOrigin::Ai, null, [
        'meanings' => ['Szólok.'],
        'high_examples' => ['A rossz hír aznap kimegy.'],
        'low_examples' => ['Hallgatok.'],
        'false_positives' => ['Hangoskodás.'],
    ]);
    $path = valuesSpreadsheet([
        valuesHeader(),
        ['bátorság', '', 'Kimondom.', '', 'A rossz hír aznap kimegy.', 'Hallgatok.', '', ''],
    ]);

    $this->artisan('megis:values:import', ['file' => $path])
        ->expectsOutputToContain('Változik: bátorság (Név, Mit jelent?)')
        ->expectsOutputToContain('Átnézettnek jelölve: bátorság')
        ->expectsOutputToContain('0 érték bekerült')
        ->expectsOutputToContain('1 érték új verzióval frissült, 0 változatlan maradt.')
        ->assertSuccessful();

    expect($value->refresh())
        ->name->toBe('bátorság')
        ->slug->toBe('batorsag')
        ->meanings->toBe(['Kimondom.'])
        ->origin->toBe(ValueOrigin::Ai)
        ->reviewed_at->not->toBeNull()
        ->code->toBeNull()
        // A column the file does not have keeps what was there.
        ->false_positives->toBe(['Hangoskodás.'])
        ->and($value->latestVersion)
        ->number->toBe(2)
        ->note->toStartWith('Betöltés táblázatból (')
        ->and(Value::query()->count())->toBe(1);

    $this->artisan('megis:values:import', ['file' => $path])
        ->expectsOutputToContain('0 érték új verzióval frissült, 1 változatlan maradt.')
        ->assertSuccessful();

    expect($value->versions()->count())->toBe(2);
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
        ->and(Value::query()->firstWhere('name', 'Biztonság'))
        ->keywords->toBe(['kiszámíthatóság', 'stabilitás', 'rend'])
        ->code->toBe('M3')
        ->false_positives->toBe(['Merev szabálykövetés, amely mögött félelem áll', 'Változásellenesség'])
        ->svs_status->toBe(ValueSvsStatus::ToVerify)
        ->and(Value::query()->firstWhere('name', 'Biztonság')->category->higher_order)->toBe('Megőrzés (Conservation)');
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
