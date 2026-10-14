<?php

use App\Enums\MaterialContext;
use App\Livewire\Client\Profile;
use App\Models\Material;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('lists the own recordings and deletes only the audio', function () {
    Storage::fake('materials');
    $organization = Organization::factory()->create();
    $user = actingAsCustomer(customerOf($organization));
    Storage::disk('materials')->put('rec/audio.webm', 'hang');
    $material = Material::factory()->recording('rec/audio.webm', 42)->transcribed([['speaker' => 'Beszélő 1', 'text' => 'Megmarad.']])->for($user)->create(['context' => MaterialContext::Mirror]);
    $someoneElses = Material::factory()->recording('other/audio.webm')->for(User::factory()->create())->create();

    Livewire::test(Profile::class, ['organization' => $organization])
        ->assertSee('Felvételeim')
        ->assertSee('0:42')
        ->call('deleteRecording', $material->uuid)
        ->assertSee('A hang törölve.');

    expect($material->fresh()->hasAudio())->toBeFalse()
        ->and($material->fresh()->text)->toBe('Megmarad.');
    Storage::disk('materials')->assertMissing('rec/audio.webm');

    Livewire::test(Profile::class, ['organization' => $organization])
        ->call('deleteRecording', $someoneElses->uuid)
        ->assertNotFound();
});

it('does not delete recordings in the user’s name', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $customer = customerOf($organization);
    $material = Material::factory()->recording()->for($customer)->create();
    $this->post(route('admin.impersonate', $customer));

    Livewire::test(Profile::class, ['organization' => $organization])
        ->call('deleteRecording', $material->uuid)
        ->assertForbidden();
});
