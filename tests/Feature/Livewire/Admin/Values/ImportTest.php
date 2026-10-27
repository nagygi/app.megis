<?php

use App\Livewire\Admin\Values\Import;
use App\Models\User;
use App\Models\Value;
use App\Models\ValueImport;
use App\Services\Values\ValueImporter;
use App\Services\Values\ValueImportResult;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

/**
 * @param  list<list<string>>  $rows  the first row is the header
 */
function valueImportSheet(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'ertekimport').'.xlsx';
    $writer = new Writer;
    $writer->openToFile($path);

    foreach ($rows as $row) {
        $writer->addRow(Row::fromValues($row));
    }

    $writer->close();

    return $path;
}

/**
 * @return list<list<string>>
 */
function valueImportRows(string $definition = 'A félelem ellenére is kimondja.'): array
{
    return [
        ['Azonosító', 'Név', 'Kategória', 'Definíció', '6 · Mikor teljesül nagyon?', '1 · Mikor nagyon nem teljesül?'],
        ['B1', 'Bátorság', 'Önállóság', $definition, 'Kimondja a kényes kérdést', 'Hallgat, ha baj van'],
    ];
}

function valueImportUpload(string $path, string $name = 'ertekek.xlsx'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, file_get_contents($path));
}

it('previews the load without writing, then loads it as v1 (03 · 3.6)', function () {
    $superadmin = actingAsAdmin(User::factory()->superadmin()->create());

    $component = Livewire::test(Import::class)
        ->set('file', valueImportUpload(base_path('docs/ertektar-import-minta.xlsx'), 'ertektar-2026-10.xlsx'))
        ->call('showPreview')
        ->assertHasNoErrors()
        ->assertSee('3 új · 0 változott · 0 változatlan')
        ->assertSee('Felelősségvállalás')
        ->assertSee('Új kategóriák')
        ->assertSeeHtml('wire:click="load"');

    expect(Value::query()->count())->toBe(0)
        ->and(ValueImport::query()->count())->toBe(0);

    $component->call('load')
        ->assertHasNoErrors()
        ->assertSet('preview', null)
        ->assertSee('ertektar-2026-10.xlsx');

    expect(Value::query()->count())->toBe(3)
        ->and(ValueImport::query()->sole())
        ->file_name->toBe('ertektar-2026-10.xlsx')
        ->created_count->toBe(3)
        ->updated_count->toBe(0)
        ->created_by->toBe($superadmin->id)
        ->and(Value::query()->firstWhere('name', 'Felelősségvállalás')->latestVersion)
        ->number->toBe(1)
        ->note->toBe('Betöltés táblázatból (ertektar-2026-10.xlsx), '.now()->format('Y.m.d.'));
});

it('shows the changed fields and loads them as a new version', function () {
    actingAsAdmin(User::factory()->superadmin()->create());
    app(ValueImporter::class)->import(valueImportSheet(valueImportRows()));

    Livewire::test(Import::class)
        ->set('file', valueImportUpload(valueImportSheet(valueImportRows('Kimondja, amit gondol.'))))
        ->call('showPreview')
        ->assertSee('0 új · 1 változott · 0 változatlan')
        ->assertSee('Változott értékek és mezőik')
        ->assertSee('Definíció')
        ->call('load');

    expect(Value::query()->sole())
        ->definition->toBe('Kimondja, amit gondol.')
        ->and(Value::query()->sole()->latestVersion->number)->toBe(2);
});

it('says when the file changes nothing', function () {
    actingAsAdmin(User::factory()->superadmin()->create());
    app(ValueImporter::class)->import(valueImportSheet(valueImportRows()));

    Livewire::test(Import::class)
        ->set('file', valueImportUpload(valueImportSheet(valueImportRows())))
        ->call('showPreview')
        ->assertSee('0 új · 0 változott · 1 változatlan')
        ->assertSee('Nincs változás a tárhoz képest.')
        ->assertDontSeeHtml('wire:click="load"');
});

it('loads nothing while a row is invalid', function () {
    actingAsAdmin(User::factory()->superadmin()->create());
    $rows = valueImportRows();
    $rows[] = ['B1', 'Kíváncsiság', 'Önállóság', 'Mindenre kíváncsi.', 'Kérdez', 'Nem kérdez'];

    $component = Livewire::test(Import::class)
        ->set('file', valueImportUpload(valueImportSheet($rows)))
        ->call('showPreview')
        ->assertSee('Hibás sorok miatt semmi nem kerül be.')
        ->assertSee('3. sor: a(z) „B1” azonosító kétszer szerepel a táblázatban.', false)
        ->assertDontSeeHtml('wire:click="load"');

    $component->call('load');

    expect(Value::query()->count())->toBe(0)
        ->and(ValueImport::query()->count())->toBe(0);
});

it('refuses a file that is not a table', function () {
    actingAsAdmin(User::factory()->superadmin()->create());

    Livewire::test(Import::class)
        ->set('file', UploadedFile::fake()->create('ertekek.pdf', 10, 'application/pdf'))
        ->call('showPreview')
        ->assertHasErrors('file');

    expect(ValueImport::query()->count())->toBe(0);
});

it('opens inactive for an admin, who may not load', function () {
    actingAsAdmin();

    Livewire::test(Import::class)
        ->assertOk()
        ->assertSee('A betöltéshez szuperadmin jog kell.')
        ->assertSeeHtml('disabled="disabled"')
        ->assertSee('Mintafájl letöltése (xlsx)')
        ->set('file', valueImportUpload(valueImportSheet(valueImportRows())))
        ->call('showPreview')
        ->assertForbidden();

    Livewire::test(Import::class)
        ->set('file', valueImportUpload(valueImportSheet(valueImportRows())))
        ->set('preview', (new ValueImportResult)->toArray())
        ->call('load')
        ->assertForbidden();

    expect(Value::query()->count())->toBe(0);
});

it('is active for a superadmin', function () {
    actingAsAdmin(User::factory()->superadmin()->create());

    Livewire::test(Import::class)
        ->assertDontSee('A betöltéshez szuperadmin jog kell.')
        ->assertDontSeeHtml('disabled="disabled"');
});

it('lists the earlier loads, the command\'s as the system\'s', function () {
    app(ValueImporter::class)->import(valueImportSheet(valueImportRows()), false, 'parancssor.xlsx');
    actingAsAdmin();

    Livewire::test(Import::class)
        ->assertSee('parancssor.xlsx')
        ->assertSee('rendszer')
        ->assertSee('1 új · 0 változott · 0 változatlan')
        ->assertSee('1 új kategória');
});

it('gives the sample file to every admin', function () {
    $this->get(route('admin.values.import.sample'))->assertRedirect(route('admin.login'));

    actingAsAdmin();

    $this->get(route('admin.values.import.sample'))->assertOk()->assertDownload('ertektar-import-minta.xlsx');
});

it('keeps the Érték Import for the admins, with a button on the list', function () {
    $this->get(route('admin.values.import'))->assertRedirect(route('admin.login'));

    actingAsAdmin();

    $this->get(route('admin.values.index'))->assertOk()->assertSee('Érték Import')->assertSee(route('admin.values.import'));
});
