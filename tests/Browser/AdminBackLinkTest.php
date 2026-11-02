<?php

use App\Models\Diagnosis;
use App\Models\MainDiagnosis;
use App\Models\User;

it('leads up from a subdiagnosis through the breadcrumbs to the diagnoses of the organization, after a Livewire update too', function () {
    $main = MainDiagnosis::factory()->create();
    $main->diagnosis->update(['name' => 'Alma Kft · Teljes']);
    Diagnosis::factory()->create(['organization_id' => $main->diagnosis->organization_id, 'parent_id' => $main->diagnosis_id, 'name' => 'Kovács Anna részdiagnózisa']);

    $page = adminLoginInBrowser(User::factory()->admin()->create());
    $diagnoses = route('admin.organizations.diagnoses', $main->diagnosis->organization, false);
    $page->navigate($diagnoses)
        ->click('Alma Kft · Teljes')
        ->click('Részdiagnózisok')
        ->click('Kovács Anna részdiagnózisa')
        ->press('Betöltöm az aggregált adatokat')
        ->assertSee('Előnézet')
        ->click('header a[href$="/reszdiagnozisok"]');

    $page->assertPathIs(route('admin.main-diagnoses.subdiagnoses', $main->diagnosis, false))
        ->click('header a[href$="/diagnozisok"]');

    $page->assertPathIs($diagnoses)
        ->assertNoJavaScriptErrors();
});
