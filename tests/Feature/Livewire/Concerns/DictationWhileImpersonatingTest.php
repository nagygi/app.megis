<?php

use App\Enums\MaterialContext;
use App\Livewire\Client\Diagnosis\Brand;
use App\Livewire\Client\Diagnosis\OwnValues;
use App\Livewire\Client\Diagnosis\ProcessConversation;
use App\Livewire\Client\Profile;
use App\Models\Material;
use App\Models\OrganizationValueSet;
use App\Services\Auth\GuardSession;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * An admin working in the leader's name (14 · 14.6).
 */
function impersonateLeader(array $setup): void
{
    actingAsAdmin($setup['admin']);
    test()->post(route('admin.impersonate', $setup['leader']));
}

it('lets an admin dictate the conversation in the leader\'s name, the recording is the admin\'s (10 · 10.1)', function () {
    Storage::fake('materials');
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    impersonateLeader($setup);
    Queue::fake();

    Livewire::test(ProcessConversation::class, ['organization' => $setup['organization'], 'diagnosis' => $main->diagnosis])
        ->set('dictationAudio', UploadedFile::fake()->create('felvetel.webm', 200, 'audio/webm'))
        ->call('dictate', 'answers.1', 21)
        ->assertOk()
        ->assertSet('dictations', fn (array $dictations) => isset($dictations['answers.1']));

    $material = Material::query()->sole();

    expect($material->context)->toBe(MaterialContext::Dictation)
        ->and($material->user_id)->toBe($setup['admin']->id);
});

it('puts the transcript into the field in the leader\'s name and keeps it off the leader\'s recordings', function () {
    Storage::fake('materials');
    config(['megis.transcriber' => 'fake']);
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    impersonateLeader($setup);
    $params = ['organization' => $setup['organization'], 'diagnosis' => $main->diagnosis];

    Livewire::test(Brand::class, $params)
        ->set('dictationAudio', UploadedFile::fake()->create('felvetel.webm', 200, 'audio/webm'))
        ->call('dictate', 'purpose', 21)
        ->call('pollDictations')
        ->assertSet('purpose', fn (string $text) => str_contains($text, 'csúszott a szállítás'));

    expect(OrganizationValueSet::query()->whereBelongsTo($setup['organization'])->sole()->purpose)->toContain('csúszott a szállítás')
        ->and(Material::query()->sole()->user_id)->toBe($setup['admin']->id);

    // The customer side reads the customer guard, as AuthenticateCustomer sets it.
    Auth::shouldUse(GuardSession::WEB);
    Livewire::test(Profile::class, ['organization' => $setup['organization']])->assertViewHas('recordings', fn ($recordings) => $recordings->isEmpty());
});

it('still records no Értéktükör in the leader\'s name, it is the leader\'s own voice', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    impersonateLeader($setup);

    Livewire::test(OwnValues::class, ['organization' => $setup['organization'], 'diagnosis' => $main->diagnosis])
        ->assertViewHas('canRecord', false);
});
