<?php

use App\Enums\DiagnosisStep;
use App\Enums\ExperienceState;
use App\Enums\MainDiagnosisStage;
use App\Enums\MirrorStatus;
use App\Enums\StepMark;
use App\Enums\ValueOrigin;
use App\Livewire\Client\Diagnosis\Brand;
use App\Livewire\Client\Diagnosis\Experience;
use App\Livewire\Client\Diagnosis\OrganizationValues;
use App\Livewire\Client\Diagnosis\OwnValues;
use App\Livewire\Client\Diagnosis\Processes;
use App\Livewire\Client\Diagnosis\Visibility;
use App\Mail\DiagnosisPeerInvitationMail;
use App\Models\Diagnosis;
use App\Models\DiagnosisType;
use App\Models\Material;
use App\Models\Membership;
use App\Models\MirrorSession;
use App\Models\Organization;
use App\Models\PersonalValueSet;
use App\Models\Product;
use App\Models\User;
use App\Models\Value;
use App\Services\Auth\LandingResolver;
use App\Services\MainDiagnoses\ExperienceService;
use App\Services\MainDiagnoses\StepProgress;
use App\Services\Values\ValueService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

function leaderParams(array $setup, $main): array
{
    return ['organization' => $setup['organization'], 'diagnosis' => $main->diagnosis];
}

it('shows the leader what awaits and moves the diagnosis to filling on the first view', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);

    // The lead lines are in the header of the layout, as in the prototype.
    $this->get(route('client.diagnosis.show', leaderParams($setup, $main)))
        ->assertOk()
        ->assertSee('Első belépés')
        ->assertSee('Mi vár rád')
        ->assertSee('Hat lépés, és indul a diagnózis.')
        ->assertSee('kb. 13 perc · '.$main->starts_on->format('m.d.').'-ig')
        ->assertSee('Saját értékek')
        ->assertSee('A márkád alapja')
        ->assertSee('Ki lát mit')
        ->assertSee('Kezdjük');

    expect($main->refresh()->stage)->toBe(MainDiagnosisStage::Filling);
});

it('keeps V1 "Kezdjük" while only the admin-filled organization values and brand are done', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    $main->leaderParticipant()->update(['opened_at' => now()->subDay()]);
    actingAsCustomer($setup['leader']);

    $this->get(route('client.diagnosis.show', leaderParams($setup, $main)))
        ->assertOk()
        ->assertSee('Hat lépés, és indul a diagnózis.')
        ->assertSee('Kezdjük')
        ->assertDontSee('Innen folytatod.');
});

it('greets the leader with "Innen folytatod" once a step is done', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    $main->leaderParticipant()->update(['opened_at' => now()->subDay()]);
    confirmOwnValues($setup['leader']);
    actingAsCustomer($setup['leader']);

    // The organization values and the brand base were given already: those steps are done too.
    $this->get(route('client.diagnosis.show', leaderParams($setup, $main)))
        ->assertOk()
        ->assertSee('Innen folytatod.')
        ->assertSee('3 / 6 kész')
        ->assertSee('Folytatom');
});

it('keeps people who are not in the diagnosis out', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    $outsider = customerOf($setup['organization']);

    actingAsCustomer($outsider);
    $this->get(route('client.diagnosis.show', leaderParams($setup, $main)))->assertForbidden();

    actingAsCustomer($setup['peers'][0]);
    // Peers come in when the diagnosis runs (25 · 25.1).
    $this->get(route('client.diagnosis.show', leaderParams($setup, $main)))->assertNotFound();
});

it('lands the leader on the diagnosis after login', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);

    expect(app(LandingResolver::class)->urlFor($setup['leader']))->toBe(route('client.diagnosis.show', leaderParams($setup, $main)));
});

it('adds own values: a new one goes to the Értéktár as the user\'s own', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);

    $page = route('client.diagnosis.sajat-ertekek', leaderParams($setup, $main));

    // Each screen has its own address, so the header follows it (prototype V2, V2·b, V2·c).
    Livewire::test(OwnValues::class, leaderParams($setup, $main))
        ->assertSet('screen', 'from')
        ->call('show', 'add')
        ->assertRedirect($page.'?lap=uj')
        ->set('draft.name', 'Kíváncsiság')
        ->call('addDraft')
        ->assertHasErrors(['draft.meaning'])
        ->set('draft.meaning', 'Megkérdezem, mi történt.')
        ->call('addDraft')
        ->assertHasNoErrors()
        ->assertSet('screen', 'list')
        ->assertRedirect($page);

    $this->get($page.'?lap=uj')->assertOk()->assertSee('Érték hozzáadása')->assertSee('Mit jelent számodra?');
    $this->get($page)->assertOk()->assertSee('Ha továbblépsz, ezek lesznek a saját értékeid.')->assertSeeHtml('href="'.$page.'?lap=honnan"');

    Livewire::test(OwnValues::class, leaderParams($setup, $main))
        ->assertSee('Kíváncsiság')
        ->assertSee('még nincs beépítve')
        ->assertSee('A szervezet értékei')
        ->call('next')
        ->assertRedirect(route('client.diagnosis.szervezet-ertekei', leaderParams($setup, $main)));

    $value = Value::query()->where('name', 'Kíváncsiság')->sole();
    expect($value->origin)->toBe(ValueOrigin::User)
        ->and($value->origin_user_id)->toBe($setup['leader']->id)
        ->and($value->reviewed_at)->toBeNull()
        ->and(PersonalValueSet::query()->where('user_id', $setup['leader']->id)->sole()->latestVersion)->not->toBeNull();
});

it('picks an own value from the Értéktár, prefilled and rewritable', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    $value = app(ValueService::class)->create(['name' => 'Egyenesség', 'meanings' => ['Kimondom.'], 'low_examples' => ['Hallgatok.'], 'high_examples' => ['Mindig.']]);
    actingAsCustomer($setup['leader']);

    Livewire::test(OwnValues::class, leaderParams($setup, $main))
        ->call('show', 'add')
        ->set('librarySearch', 'egyen')
        ->assertSee('Egyenesség')
        ->call('pickFromLibrary', $value->id)
        ->assertSet('draft.meaning', 'Kimondom.')
        ->set('draft.meaning', 'Kimondom, amit gondolok.')
        ->call('addDraft')
        ->assertHasNoErrors();

    $item = PersonalValueSet::query()->where('user_id', $setup['leader']->id)->sole()->items()->sole();
    expect($item->value_id)->toBe($value->id)
        ->and($item->meaning)->toBe('Kimondom, amit gondolok.')
        ->and($value->refresh()->meanings)->toBe(['Kimondom.']);
});

it('brings the values of the Értéktükör, leaving out "nem az enyém"', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    $value = app(ValueService::class)->create(['name' => 'Egyenesség', 'meanings' => ['Kimondom.']]);
    $other = app(ValueService::class)->create(['name' => 'Humor', 'meanings' => ['Nevetünk.']]);
    $material = Material::factory()->create(['user_id' => $setup['leader']->id, 'organization_id' => $setup['organization']->id, 'status' => 'processed']);
    $kept = $material->detections()->create(['speaker' => 'Beszélő 1', 'quote' => 'inkább most mondom', 'free_name' => 'Egyenesség', 'reasoning' => 'R']);
    $kept->matches()->create(['value_id' => $value->id, 'confidence' => 0.9]);
    $excluded = $material->detections()->create(['speaker' => 'Beszélő 1', 'quote' => 'nevettünk', 'free_name' => 'Humor', 'reasoning' => 'R', 'excluded_at' => now()]);
    $excluded->matches()->create(['value_id' => $other->id, 'confidence' => 0.9]);
    MirrorSession::factory()->create(['material_id' => $material->id, 'user_id' => $setup['leader']->id, 'status' => MirrorStatus::Ready, 'claimed_at' => now()]);
    actingAsCustomer($setup['leader']);

    Livewire::test(OwnValues::class, leaderParams($setup, $main))
        ->assertSee('Az Értéktükörből hozom')
        ->call('importMirror')
        ->assertSet('screen', 'list')
        ->assertRedirect(route('client.diagnosis.sajat-ertekek', leaderParams($setup, $main)));

    Livewire::test(OwnValues::class, leaderParams($setup, $main))
        ->assertSee('Egyenesség')
        ->assertSee('inkább most mondom')
        ->assertSee('erősen jelen')
        ->assertDontSee('Humor');
});

it('lets a non-leader of the organization only read the organization values', function () {
    $setup = diagnosisTeam(organizationLeader: false);
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);

    Livewire::test(OrganizationValues::class, leaderParams($setup, $main))
        ->assertSet('items', fn ($items) => count($items) === 3)
        ->assertDontSee('Az Értéktárból választom')
        ->call('next')
        ->assertRedirect(route('client.diagnosis.markad-alapja', leaderParams($setup, $main)));

    expect($main->stepMarks()->where('step', DiagnosisStep::OrganizationValues)->value('mark'))->toBe(StepMark::Viewed);

    Livewire::test(OrganizationValues::class, leaderParams($setup, $main))->call('remove', 'x')->assertForbidden();
});

it('ties an own value to an Értéktár value of the same name the user may not choose', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    $taken = app(ValueService::class)->createElsewhere('Egyenesség', ValueOrigin::Ai, Organization::factory()->create(), ['meanings' => ['Egy másik szervezet AI-szövege.']]);
    actingAsCustomer($setup['leader']);

    Livewire::test(OwnValues::class, leaderParams($setup, $main))
        ->call('show', 'add')
        ->set('draft.name', 'egyenesség')
        ->set('draft.meaning', 'Kimondom, amit gondolok.')
        ->call('addDraft')
        ->assertHasNoErrors();

    Livewire::test(OwnValues::class, leaderParams($setup, $main))
        ->assertSee('Kimondom, amit gondolok.')
        ->assertDontSee('Egy másik szervezet AI-szövege.');

    $item = PersonalValueSet::query()->where('user_id', $setup['leader']->id)->sole()->items()->sole();
    expect($item->value_id)->toBe($taken->id)
        ->and($item->name)->toBe('Egyenesség')
        ->and($item->meaning)->toBe('Kimondom, amit gondolok.')
        ->and(Value::query()->where('name', 'Egyenesség')->count())->toBe(1);
});

it('ties a new organization value to an Értéktár value of the same name not choosable here', function () {
    $setup = diagnosisTeam(withValues: false);
    $main = startMainDiagnosis($setup, invite: false);
    $main->update(['stage' => MainDiagnosisStage::Invited]);
    $taken = app(ValueService::class)->createElsewhere('Egyenesség', ValueOrigin::Ai, Organization::factory()->create(), ['meanings' => ['Egy másik szervezet AI-szövege.']]);
    actingAsCustomer($setup['leader']);

    Livewire::test(OrganizationValues::class, leaderParams($setup, $main))
        ->set('newValueName', 'Egyenesség')
        ->call('addNew')
        ->assertHasNoErrors()
        ->assertSet('items.0.value_id', $taken->id)
        ->assertSet('items.0.meaning', '')
        ->set('newValueName', 'egyenesség')
        ->call('addNew')
        ->assertHasErrors(['newValueName']);
});

it('edits the organization values with 3–5 values and records the version on "Tovább"', function () {
    $setup = diagnosisTeam(withValues: false);
    $main = startMainDiagnosis($setup, invite: false);
    $main->update(['stage' => MainDiagnosisStage::Invited]);
    $values = collect(range(1, 3))->map(fn ($index) => app(ValueService::class)->create([
        'name' => 'Közös '.$index, 'meanings' => ['M'], 'low_examples' => ['Alig'], 'high_examples' => ['Nagyon'],
    ]));
    actingAsCustomer($setup['leader']);

    $component = Livewire::test(OrganizationValues::class, leaderParams($setup, $main))
        ->set('pickedValueId', $values[0]->id)
        ->call('addFromLibrary')
        ->call('next')
        ->assertHasErrors(['items']);

    $component->set('pickedValueId', $values[1]->id)->call('addFromLibrary')
        ->set('pickedValueId', $values[2]->id)->call('addFromLibrary')
        ->set('items.0.high_example', 'Mindig, mindenkinek.')
        ->call('next')
        ->assertHasNoErrors()
        ->assertRedirect();

    $set = $setup['organization']->valueSet()->first();
    expect($set->items()->count())->toBe(3)
        ->and($set->latestVersion->snapshot['values'][0]['high_example'])->toBe('Mindig, mindenkinek.')
        ->and(app(StepProgress::class)->forLeader($main->refresh(), $setup['leader'])->get(DiagnosisStep::OrganizationValues)->done)->toBeTrue();
});

it('greys out the adding of organization values at five', function () {
    $setup = diagnosisTeam(withValues: false);
    $main = startMainDiagnosis($setup, invite: false);
    $main->update(['stage' => MainDiagnosisStage::Invited]);
    $values = collect(range(1, 6))->map(fn ($index) => app(ValueService::class)->create([
        'name' => 'Közös '.$index, 'meanings' => ['M'], 'low_examples' => ['Alig'], 'high_examples' => ['Nagyon'],
    ]));
    actingAsCustomer($setup['leader']);

    $component = Livewire::test(OrganizationValues::class, leaderParams($setup, $main))
        ->assertDontSee('Legfeljebb 5 érték lehet.');

    foreach ($values as $value) {
        $component->set('pickedValueId', $value->id)->call('addFromLibrary');
    }

    $component->assertSet('items', fn ($items) => count($items) === 5)
        ->assertSee('Az Értéktárból választom')
        ->assertSee('Legfeljebb 5 érték lehet.')
        ->assertSeeHtml('wire:model="newValueName" disabled="disabled"');
});

it('saves the brand base boxes and needs all four for "Tovább"', function () {
    $setup = diagnosisTeam(withValues: false);
    organizationValues($setup['organization'], withBrand: false);
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);

    $component = Livewire::test(Brand::class, leaderParams($setup, $main))
        ->assertSee('1 / 4 kész', false)
        ->set('purpose', 'Hogy emberi léptékű legyen.');

    expect($setup['organization']->valueSet()->first()->purpose)->toBe('Hogy emberi léptékű legyen.');

    $component->call('next')->assertHasErrors(['value_proposition', 'impact'])
        ->set('value_proposition', 'Egy csapatot.')
        ->set('impact', 'Ügyfelek, akik maradnak.')
        ->call('next')
        ->assertHasNoErrors()
        ->assertRedirect(route('client.diagnosis.megeles', leaderParams($setup, $main)));
});

it('opens a filled step again from V10 and goes back home from it', function () {
    Queue::fake();
    [$setup, $main] = runningDiagnosis();
    actingAsCustomer($setup['leader']);
    $home = route('client.diagnosis.show', leaderParams($setup, $main));

    $this->get(route('client.diagnosis.megeles', leaderParams($setup, $main)))
        ->assertOk()
        ->assertSeeHtml('href="'.$home.'"')
        ->assertSee('Diagnózis');

    $keys = collect(app(ExperienceService::class)->rows($main->valueSetVersion->snapshot))->pluck('key');
    $component = Livewire::test(Experience::class, leaderParams($setup, $main));

    foreach ($keys as $key) {
        $component->set('answers.'.$key, '3');
    }

    $component->call('submit')->assertHasNoErrors()->assertRedirect($home);
});

it('sends the experience as a Megélés subdiagnosis with the value set version', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);

    $component = Livewire::test(Experience::class, leaderParams($setup, $main))
        ->assertSee('0 / 7 megválaszolva')
        ->assertSee(['Alig 1', 'Nagyon 1'])
        ->assertDontSeeHtml('aria-expanded');

    $keys = collect($setup['organization']->valueSet->items)->map(fn ($item) => 'value:'.$item->key)
        ->merge(['tree:root', 'tree:trunk', 'tree:crown', 'tree:shadow']);

    $component->set('answers.'.$keys[0], '5');
    expect($main->refresh()->leaderParticipant()->experience_draft)->toBe([$keys[0] => '5']);

    $component->call('submit')->assertHasErrors(['answers']);

    foreach ($keys->slice(1) as $key) {
        $component->set('answers.'.$key, $key === 'tree:shadow' ? 'dont_know' : '4');
    }

    $component->call('submit')->assertHasNoErrors()->assertRedirect(route('client.diagnosis.folyamatok', leaderParams($setup, $main)));

    $experience = Diagnosis::query()->where('parent_id', $main->diagnosis_id)->sole();
    expect($experience->type->key)->toBe(DiagnosisType::EXPERIENCE)
        ->and($experience->user_id)->toBe($setup['leader']->id)
        ->and($experience->value_set_version_id)->toBe($setup['organization']->valueSet->latestVersion->id)
        ->and($experience->valueRows()->count())->toBe(3)
        ->and($experience->treeRows()->where('tree_element', 'shadow')->first()->experience_state)->toBe(ExperienceState::DontKnow)
        ->and($main->refresh()->leaderParticipant()->experience_draft)->toBeNull();
});

it('skips the processes step and starts the diagnosis from V9', function () {
    Mail::fake();
    $setup = diagnosisTeam(peers: 1);
    $main = startMainDiagnosis($setup);
    confirmOwnValues($setup['leader']);
    actingAsCustomer($setup['leader']);

    Livewire::test(Visibility::class, leaderParams($setup, $main))
        ->assertSee('Még hiányzik: A megélésed.')
        ->call('startDiagnosis');

    expect($main->refresh()->stage)->toBe(MainDiagnosisStage::Invited);

    $keys = collect($setup['organization']->valueSet->items)->map(fn ($item) => 'value:'.$item->key)->merge(['tree:root', 'tree:trunk', 'tree:crown', 'tree:shadow']);
    $experience = Livewire::test(Experience::class, leaderParams($setup, $main));
    foreach ($keys as $key) {
        $experience->set('answers.'.$key, '3');
    }
    $experience->call('submit');

    Livewire::test(Processes::class, leaderParams($setup, $main))
        ->call('skip')
        ->assertRedirect(route('client.diagnosis.ki-lat-mit', leaderParams($setup, $main)));

    Livewire::test(Visibility::class, leaderParams($setup, $main))
        ->assertSee('A társak')
        ->assertSee('a társak idézetei szereppel és dátummal, név nélkül')
        ->call('startDiagnosis')
        ->assertRedirect(route('client.diagnosis.show', leaderParams($setup, $main)));

    expect($main->refresh()->stage)->toBe(MainDiagnosisStage::Running);
    Mail::assertQueued(DiagnosisPeerInvitationMail::class, fn ($mail) => $mail->hasTo($setup['peers'][0]->email));
});

it('reopens the experience when the organization values changed after it was sent', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);

    $keys = collect($setup['organization']->valueSet->items)->map(fn ($item) => 'value:'.$item->key)->merge(['tree:root', 'tree:trunk', 'tree:crown', 'tree:shadow']);
    $experience = Livewire::test(Experience::class, leaderParams($setup, $main));
    foreach ($keys as $key) {
        $experience->set('answers.'.$key, '3');
    }
    $experience->call('submit');

    $progress = app(StepProgress::class);
    expect($progress->forLeader($main->refresh(), $setup['leader'])->get(DiagnosisStep::Experience)->done)->toBeTrue();

    Livewire::test(Brand::class, leaderParams($setup, $main))->set('impact', 'Más marad utánunk.')->call('next');

    expect($progress->forLeader($main->refresh(), $setup['leader'])->get(DiagnosisStep::Experience)->done)->toBeFalse();
});

it('makes a private person\'s own values the organization values', function () {
    $organization = Organization::factory()->individual()->create([
        'product_id' => startingProduct(Product::SNAPSHOT_INTERNAL)->id,
        'contact_admin_id' => User::factory()->admin()->create()->id,
    ]);
    $person = User::factory()->create();
    Membership::factory()->for($organization)->for($person)->organizationLeader()->create();
    $main = startMainDiagnosis(['organization' => $organization, 'team' => null, 'leader' => $person, 'admin' => User::factory()->admin()->create()], overrides: ['team_id' => null, 'leader_id' => null]);
    actingAsCustomer($person);

    $component = Livewire::test(OwnValues::class, ['organization' => $organization, 'diagnosis' => $main->diagnosis]);

    foreach (['Bátorság', 'Nyíltság', 'Felelősség'] as $name) {
        $component->call('show', 'add')
            ->set('draft.name', $name)
            ->set('draft.meaning', 'Jelentés')
            ->set('draft.low_example', 'Alig')
            ->set('draft.high_example', 'Nagyon')
            ->call('addDraft');
    }

    $component->call('next')->assertHasNoErrors()->assertRedirect(route('client.diagnosis.markad-alapja', ['organization' => $organization, 'diagnosis' => $main->diagnosis]));

    expect($organization->valueSet()->first()->items()->pluck('name')->all())->toBe(['Bátorság', 'Nyíltság', 'Felelősség']);
});

it('asks a private person about the brand base in the singular', function () {
    $organization = Organization::factory()->individual()->create([
        'product_id' => startingProduct(Product::SNAPSHOT_INTERNAL)->id,
        'contact_admin_id' => User::factory()->admin()->create()->id,
    ]);
    $person = User::factory()->create();
    Membership::factory()->for($organization)->for($person)->organizationLeader()->create();
    $main = startMainDiagnosis(['organization' => $organization, 'team' => null, 'leader' => $person, 'admin' => User::factory()->admin()->create()], overrides: ['team_id' => null, 'leader_id' => null]);
    actingAsCustomer($person);

    // The question is the header's subtitle (prototype V4).
    $this->get(route('client.diagnosis.markad-alapja', ['organization' => $organization, 'diagnosis' => $main->diagnosis]))
        ->assertOk()
        ->assertSee('Mit mond rólad a fa?')
        ->assertSee('Miért csinálod?')
        ->assertDontSee('Mit mond rólatok a fa?');
});
