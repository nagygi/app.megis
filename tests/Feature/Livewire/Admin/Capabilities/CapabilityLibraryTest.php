<?php

use App\Enums\CapabilityLevel;
use App\Enums\CapabilityNature;
use App\Enums\CapabilityStatus;
use App\Enums\VersionEventType;
use App\Livewire\Admin\Capabilities\Form;
use App\Livewire\Admin\Capabilities\Import;
use App\Livewire\Admin\Capabilities\Index;
use App\Livewire\Admin\Capabilities\Versions;
use App\Livewire\Admin\CapabilityGroups\Index as Groups;
use App\Models\Capability;
use App\Models\CapabilityGroup;
use App\Models\CapabilityImport;
use App\Models\Precondition;
use App\Services\Capabilities\CapabilityImporter;
use App\Services\Capabilities\CapabilityService;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

/**
 * A synthetic export of the mégis tár: sheet name => rows, the first row is the header.
 *
 * @param  array<string, list<list<string>>>  $sheets
 */
function capabilitySpreadsheet(array $sheets): string
{
    $path = tempnam(sys_get_temp_dir(), 'kepessegtar').'.xlsx';
    $writer = new Writer;
    $writer->openToFile($path);
    $first = true;

    foreach ($sheets as $name => $rows) {
        $sheet = $first ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName($name);
        $first = false;

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
    }

    $writer->close();

    return $path;
}

function capabilityHeader(): array
{
    return ['azonosító', 'név (magyar)', 'név (angol)', 'elsődleges szint', 'csoport', 'státusz', 'jelleg', 'rövid definíció', 'mi nem ez', 'átfedés', 'szükséges előfeltételek', 'csábító tétel', 'falszifikálás', 'mérőeszköz', 'minták száma'];
}

/**
 * Three loadable capabilities, one broken row, details, and a case sheet in front that must never be read.
 */
function capabilityExport(array $override = []): string
{
    $rows = [
        'RC-01' => ['RC-01', 'Visszajelzés kérése', 'Asking for feedback', 'csapat', 'Teaming & Participation', 'használt', 'képesség', 'A csapat rendszeresen visszajelzést kér a munkájára.', "- Nem a dicséret begyűjtése.\n- Nem a panaszkezelés.", 'RC-02: ott a döntés a lényeg.', 'E3 (idő a visszanézésre), ÚJ-01 (a döntési jog, EF-11)', 'igen (szinte bármire ráhúzható)', 'Ha a kérés után semmi nem változik.', 'nincs még', '3'],
        'RC-02' => ['RC-02', 'Döntés előkészítése', '', 'szervezet', 'Purpose & Strategy', 'jelölt', '[JAVASLAT] készség-jellegű', 'A döntés előtt összegyűjtik, ami kell hozzá.', '', '', 'E1 (a fájdalom és a döntési jog egy helyen; M-015) és E6 (mozgástér): ha a fogadó nem tud változtatni, a hang elhal.', 'nem', '', '', ''],
        'ÚJ-01' => ['ÚJ-01', 'Döntési jog tisztázása', 'Decision rights', 'egyén', 'Kapcsolati intelligencia', 'félretett', '', 'Tisztázzák, ki dönthet miről.', '', '', '', 'nincs még (később)', '', '', ''],
        'RC-03' => ['RC-03', 'Hibás sor', '', 'galaxis', 'Systems & Complexity', 'jelölt', 'képesség', 'Valami.', '', '', '', 'nem', '', '', ''],
    ];

    foreach ($override as $code => $row) {
        if ($row === null) {
            unset($rows[$code]);
        } else {
            $rows[$code] = $row;
        }
    }

    return capabilitySpreadsheet([
        'Találat' => [['azonosító', 'név (magyar)', 'idézet'], ['RC-01', 'Titkos név', 'ESETADAT-NE-JOJJON-BE']],
        'Képesség' => [capabilityHeader(), ...array_values($rows)],
        'Képesség-részletek' => [
            ['képesség', 'mező', 'érték'],
            ['RC-01', 'kimondható forma', 'Mostantól kérünk visszajelzést, félelem nélkül.'],
            ['RC-01', 'megjelenés: csapat', 'A retrón a csapat maga kér visszajelzést.'],
            ['RC-01', 'érettség 1: Csak szavak', 'Beszélünk róla.'],
            ['RC-01', 'érettség 6: Továbbadjuk', 'Másnak is megtanítjuk.'],
            ['RC-01', 'nyomáspróba: bukási jel', 'Elmarad a kérés.'],
            ['RC-01', 'jellemző források', 'nincs még'],
            ['RC-99', 'kimondható forma', 'Ilyen képesség nincs.'],
        ],
        'Bizonyíték' => [['azonosító', 'idézet'], ['RC-01', 'ESETADAT-NE-JOJJON-BE']],
    ]);
}

function capabilityUpload(string $path): UploadedFile
{
    return UploadedFile::fake()->createWithContent('kepessegrendszer-tarak.xlsx', file_get_contents($path));
}

it('comes with the 12 groups and the preconditions E1–E9 (07 · 7.4, 7.6)', function () {
    expect(CapabilityGroup::query()->count())->toBe(12)
        ->and(CapabilityGroup::query()->firstWhere('name_en', 'Relational Intelligence')->label())->toBe('Kapcsolati intelligencia (Relational Intelligence)')
        ->and(Precondition::query()->orderBy('code')->pluck('code')->all())->toBe(['E1', 'E2', 'E3', 'E4', 'E5', 'E6', 'E7', 'E8', 'E9'])
        ->and(Precondition::query()->firstWhere('code', 'E2')->name)->toBe('Gazda');
});

it('previews the load without writing, then loads it as v1 (07 · 7.6)', function () {
    actingAsAdmin();

    $component = Livewire::test(Import::class)
        ->set('file', capabilityUpload(capabilityExport()))
        ->call('showPreview')
        ->assertSee('3 új · 0 változott · 0 változatlan')
        ->assertSee('RC-03')
        ->assertSee('galaxis')
        ->assertSee('ismeretlen képesség: „RC-99”', false)
        ->assertSee('minták száma');

    expect(Capability::query()->count())->toBe(0);

    $component->call('load')->assertHasNoErrors();

    expect(Capability::query()->count())->toBe(3)
        ->and(Capability::query()->where('code', 'RC-03')->exists())->toBeFalse()
        ->and(CapabilityImport::query()->sole())
        ->created_count->toBe(3)
        ->skipped_count->toBe(1)
        ->file_name->toBe('kepessegrendszer-tarak.xlsx');

    $feedback = Capability::query()->firstWhere('code', 'RC-01');
    expect($feedback)
        ->name->toBe('Visszajelzés kérése')
        ->name_en->toBe('Asking for feedback')
        ->level->toBe(CapabilityLevel::Team)
        ->status->toBe(CapabilityStatus::Used)
        ->nature->toBe(CapabilityNature::Capability)
        ->tempting->toBeTrue()
        ->non_examples->toBe(['Nem a dicséret begyűjtése.', 'Nem a panaszkezelés.'])
        ->overlaps->toBe(['RC-02: ott a döntés a lényeg.'])
        ->statement->toBe('Mostantól kérünk visszajelzést, félelem nélkül.')
        ->imported_at->not->toBeNull()
        ->and($feedback->appearances)->toBe(['team' => 'A retrón a csapat maga kér visszajelzést.'])
        ->and($feedback->stages[1])->toBe('Beszélünk róla.')
        ->and($feedback->stages[6])->toBe('Másnak is megtanítjuk.')
        ->and($feedback->group->name_en)->toBe('Teaming & Participation')
        ->and($feedback->preconditions->map(fn ($precondition) => [$precondition->code, $precondition->pivot->note])->all())->toBe([['E3', 'idő a visszanézésre']])
        ->and($feedback->dependencies->map(fn ($dependency) => [$dependency->code, $dependency->pivot->note])->all())->toBe([['ÚJ-01', 'a döntési jog, EF-11']])
        ->and(collect($feedback->details)->pluck('value', 'label')->all())->toBe([
            'csábító tétel' => 'szinte bármire ráhúzható',
            'falszifikálás' => 'Ha a kérés után semmi nem változik.',
            'nyomáspróba: bukási jel' => 'Elmarad a kérés.',
        ])
        ->and($feedback->latestVersion->number)->toBe(1)
        ->and($feedback->latestVersion->note)->toBe('Frissítés a mégis tárából, '.now()->format('Y.m.d.'));

    // "[JAVASLAT]" is a mark on the field, the precondition text beyond the codes stays readable.
    $decision = Capability::query()->firstWhere('code', 'RC-02');
    expect($decision)
        ->nature->toBe(CapabilityNature::SkillLike)
        ->tempting->toBeFalse()
        ->name_en->toBeNull()
        ->and($decision->isProposed('nature'))->toBeTrue()
        ->and($decision->preconditions->pluck('pivot.note', 'code')->all())->toBe(['E1' => 'a fájdalom és a döntési jog egy helyen; M-015', 'E6' => 'mozgástér'])
        ->and(collect($decision->details)->firstWhere('label', 'szükséges előfeltételek')['value'])->toContain('a hang elhal');

    // "félretett" comes in archived, "nincs még" is empty with its state, a Hungarian group name matches too.
    $rights = Capability::query()->firstWhere('code', 'ÚJ-01');
    expect($rights)
        ->status->toBe(CapabilityStatus::SetAside)
        ->isArchived()->toBeTrue()
        ->tempting->toBeNull()
        ->and($rights->fieldState('tempting'))->toBe('not_yet')
        ->and($rights->group->name_en)->toBe('Relational Intelligence')
        ->and($rights->versionEvents()->sole()->type)->toBe(VersionEventType::Archived);

    // Nothing of the case sheets comes in.
    expect(Capability::query()->get()->toJson())->not->toContain('ESETADAT')
        ->and(Capability::query()->where('name', 'Titkos név')->exists())->toBeFalse();
});

it('makes a new version only where the file changed something, and keeps what is not in it (07 · 7.3, 7.6)', function () {
    actingAsAdmin();
    app(CapabilityImporter::class)->import(capabilityExport(), 'elso.xlsx');
    $feedback = Capability::query()->firstWhere('code', 'RC-01');
    // The keywords have no column in the Sheet: they stay.
    $feedback->forceFill(['keywords' => ['visszacsatolás']])->save();
    $feedback->recordVersion();

    $changed = capabilityExport([
        'RC-01' => ['RC-01', 'Visszajelzés kérése', 'Asking for feedback', 'csapat', 'Teaming & Participation', 'használt', 'képesség', 'A csapat hetente visszajelzést kér a munkájára.', "- Nem a dicséret begyűjtése.\n- Nem a panaszkezelés.", 'RC-02: ott a döntés a lényeg.', 'E3 (idő a visszanézésre), ÚJ-01 (a döntési jog, EF-11)', 'igen (szinte bármire ráhúzható)', 'Ha a kérés után semmi nem változik.', 'nincs még', '3'],
        'RC-02' => null,
    ]);

    Livewire::test(Import::class)
        ->set('file', capabilityUpload($changed))
        ->call('showPreview')
        ->assertSee('0 új · 1 változott · 1 változatlan')
        ->assertSee('Rövid definíció')
        ->call('load');

    $feedback->refresh();
    expect($feedback->definition)->toBe('A csapat hetente visszajelzést kér a munkájára.')
        ->and($feedback->keywords)->toBe(['visszacsatolás'])
        ->and($feedback->latestVersion->number)->toBe(3)
        ->and($feedback->latestVersion->changed_fields)->toBe(['definition'])
        ->and(Capability::query()->where('code', 'RC-02')->exists())->toBeTrue()
        ->and(Capability::query()->firstWhere('code', 'RC-02')->versions()->count())->toBe(1);
});

it('refuses a file that is not an xlsx export of the tár', function () {
    actingAsAdmin();

    Livewire::test(Import::class)
        ->set('file', UploadedFile::fake()->createWithContent('tar.xlsx', 'nem xlsx'))
        ->call('showPreview')
        ->assertHasErrors(['file']);

    Livewire::test(Import::class)
        ->set('file', capabilityUpload(capabilitySpreadsheet(['Minta' => [['azonosító'], ['RC-01']]])))
        ->call('showPreview')
        ->assertSee('A fájlban nincs „Képesség” fül.', false);

    expect(Capability::query()->count())->toBe(0);
});

it('saves a change as a new version with its note and drops the Sheet marks of the changed field (07 · 7.2, 7.3)', function () {
    actingAsAdmin();
    app(CapabilityImporter::class)->import(capabilityExport(), 'elso.xlsx');
    $decision = Capability::query()->firstWhere('code', 'RC-02');

    Livewire::test(Form::class, ['capability' => $decision])
        ->assertSee('a következő betöltés felülírja')
        ->assertSee('javaslat')
        ->set('code', 'RC-77')
        ->set('nature', CapabilityNature::Capability->value)
        ->set('keywords', ['döntés-előkészítés'])
        ->set('note', 'A jelleget a mégis megerősítette.')
        ->call('save')
        ->assertHasNoErrors();

    $decision->refresh();
    expect($decision->code)->toBe('RC-02')
        ->and($decision->nature)->toBe(CapabilityNature::Capability)
        ->and($decision->isProposed('nature'))->toBeFalse()
        ->and($decision->latestVersion->note)->toBe('A jelleget a mégis megerősítette.')
        ->and($decision->latestVersion->changed_fields)->toBe(['nature', 'keywords'])
        // The notes of the preconditions from the Sheet stay.
        ->and($decision->preconditions->pluck('pivot.note', 'code')->all())->toBe(['E1' => 'a fájdalom és a döntési jog egy helyen; M-015', 'E6' => 'mozgástér']);

    Livewire::test(Versions::class, ['capability' => $decision])
        ->assertSeeInOrder(['v2', 'Jelleg, Szinonimák, kulcsszavak', 'A jelleget a mégis megerősítette.', 'v1', 'Frissítés a mégis tárából']);
});

it('creates a capability with a unique code and name, list items up to 1000 characters (07 · 7.2)', function () {
    actingAsAdmin();
    $group = CapabilityGroup::query()->firstWhere('name_en', 'Systems & Complexity');
    Capability::factory()->create(['code' => 'RC-10', 'name' => 'Rendszerlátás']);

    $form = fn () => Livewire::test(Form::class)
        ->set('name', 'Mintázatok felismerése')
        ->set('capability_group_id', $group->id)
        ->set('level', CapabilityLevel::Organization->value)
        ->set('status', CapabilityStatus::Literature->value)
        ->set('definition', 'Felismerik a visszatérő mintázatokat.');

    $form()->set('code', 'RC-10')->set('name', 'rendszerlátás')->set('non_examples', [str_repeat('a', 1001)])->call('save')
        ->assertHasErrors(['code', 'name', 'non_examples.0']);

    $form()->set('code', 'rc-11')
        ->set('non_examples', [str_repeat('a', 1000)])
        ->set('preconditionIds', [(string) Precondition::query()->firstWhere('code', 'E2')->id])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $capability = Capability::query()->firstWhere('code', 'RC-11');
    expect($capability->slug)->toBe('mintazatok-felismerese')
        ->and($capability->imported_at)->toBeNull()
        ->and($capability->preconditions->pluck('code')->all())->toBe(['E2'])
        ->and($capability->latestVersion->number)->toBe(1);
});

it('filters and searches the list, and archives and restores with an event (07 · 7.1, 7.5)', function () {
    actingAsAdmin();
    $team = Capability::factory()->create(['code' => 'RC-20', 'name' => 'Közös tervezés', 'level' => CapabilityLevel::Team, 'keywords' => ['együtt tervezünk']]);
    $individual = Capability::factory()->create(['code' => 'RC-21', 'name' => 'Önreflexió', 'level' => CapabilityLevel::Individual]);
    $archived = Capability::factory()->archived()->create(['code' => 'RC-22', 'name' => 'Régi tétel']);

    Livewire::test(Index::class)
        ->assertSee('Közös tervezés')->assertSee('Önreflexió')->assertDontSee('Régi tétel')
        ->set('filters.level', CapabilityLevel::Individual->value)
        ->assertDontSee('Közös tervezés')->assertSee('Önreflexió')
        ->set('filters.level', '')
        ->set('search', 'együtt')
        ->assertSee('Közös tervezés')->assertDontSee('Önreflexió')
        ->set('search', 'rc-21')
        ->assertSee('Önreflexió')
        ->set('search', '')
        ->set('filters.state', 'archived')
        ->assertSee('Régi tétel')->assertDontSee('Önreflexió');

    Livewire::test(Index::class)->call('archive', $team->id)->call('restore', $archived->id);

    expect($team->fresh()->isArchived())->toBeTrue()
        ->and($archived->fresh()->isArchived())->toBeFalse()
        ->and($team->versionEvents()->sole()->type)->toBe(VersionEventType::Archived)
        ->and($team->versions()->count())->toBe(0);
});

it('deletes only a group without capabilities (07 · 7.4)', function () {
    actingAsAdmin();
    $used = CapabilityGroup::query()->firstWhere('name_en', 'Purpose & Strategy');
    Capability::factory()->create(['capability_group_id' => $used->id]);
    $empty = CapabilityGroup::factory()->create(['name' => 'Üres csoport']);

    Livewire::test(Groups::class)
        ->call('delete', $used->id)
        ->call('delete', $empty->id);

    expect(CapabilityGroup::query()->whereKey($used->id)->exists())->toBeTrue()
        ->and(CapabilityGroup::query()->whereKey($empty->id)->exists())->toBeFalse();
});

it('keeps the Képességtár for the admins', function () {
    $this->get(route('admin.capabilities.index'))->assertRedirect(route('admin.login'));
    $this->get(route('admin.capabilities.import'))->assertRedirect(route('admin.login'));

    actingAsAdmin();
    $this->get(route('admin.capabilities.index'))->assertOk()->assertSee('Frissítés a mégis tárából');
    $this->get(route('admin.capability-groups.index'))->assertOk()->assertSee('Kapcsolati intelligencia');
});

it('keeps the capability service from linking a capability to itself', function () {
    actingAsAdmin();
    $capability = Capability::factory()->create();

    app(CapabilityService::class)->syncDependencies($capability, [$capability->id => null]);

    expect($capability->dependencies()->count())->toBe(0);
});
