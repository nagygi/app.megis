<?php

use App\Enums\DiagnosisStatus;
use App\Enums\MaterialContext;
use App\Livewire\Mirror\Record;
use App\Livewire\Mirror\Result;
use App\Mail\MirrorInterestMail;
use App\Models\Diagnosis;
use App\Models\MirrorSession;
use App\Models\Organization;
use App\Services\Ai\AiGateway;
use App\Services\Mirror\MirrorService;
use App\Services\Values\ValueService;
use Illuminate\Support\Facades\Mail;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StructuredTextResponse;
use Livewire\Livewire;

function mirrorAnswerFor(array $data): StructuredTextResponse
{
    return new StructuredTextResponse($data, json_encode($data), new TextUsage(10, 5), new Meta('anthropic', 'claude-test'));
}

function signedInMirror(Organization $organization): MirrorSession
{
    $answer = fn (array $data) => new StructuredTextResponse($data, json_encode($data), new TextUsage(10, 5), new Meta('anthropic', 'claude-test'));
    app(ValueService::class)->create(['name' => 'Egyenesség', 'meanings' => ['Kimondom.'], 'high_examples' => ['Mindig.'], 'low_examples' => ['Soha.']]);
    AiGateway::fake([
        $answer(['detections' => [['speaker' => 'Beszélő 1', 'quote' => 'azonnal szóltam', 'free_name' => 'Egyenesség', 'reasoning' => 'Szól.', 'strength' => 5]]]),
        $answer(['results' => [['detection' => 'D1', 'matches' => [['ref' => 'E1', 'confidence' => 0.9]]]]]),
        $answer(['scores' => [['ref' => 'E1', 'state' => 'scored', 'score' => 4, 'reasoning' => 'Néha.', 'detections' => ['D1']]]]),
    ]);

    Livewire::test(Record::class, ['organization' => $organization])
        ->call('setMode', 'text')
        ->set('text', str_repeat('A héten azonnal szóltam a csúszásról, nem vártam péntekig. ', 2))
        ->call('submitText')
        ->assertHasNoErrors();

    return MirrorSession::query()->sole();
}

it('needs no Turnstile, has no daily limit and goes to the active organization from the own menu', function () {
    config(['megis.mirror.daily_per_email' => 0]);
    $organization = Organization::factory()->create();
    $user = actingAsCustomer(customerOf($organization));

    $session = signedInMirror($organization);

    expect($session->user_id)->toBe($user->id)
        ->and($session->expires_at)->toBeNull()
        ->and($session->material->context)->toBe(MaterialContext::Mirror)
        ->and($session->diagnosis->organization_id)->toBe($organization->id);
});

it('records in the user’s name as well', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $customer = customerOf($organization);
    $this->post(route('admin.impersonate', $customer));

    $this->get(route('client.mirror.record', $organization))->assertOk();

    expect(signedInMirror($organization)->user_id)->toBe($customer->id);
});

it('shows no account step and no booking, and keeps the curiosity questions when finishing', function () {
    config(['megis.booking_url' => 'https://foglalas.example.com']);
    $organization = Organization::factory()->individual()->create();
    actingAsCustomer(customerOf($organization));
    $session = signedInMirror($organization);

    Livewire::test(Result::class, ['session' => $session, 'organization' => $organization])
        ->call('showValue', 1)
        ->call('approve')
        ->call('showFull')
        ->assertSee('Hogyan tovább?')
        ->assertSee('Kell egy vízió, ami')
        ->assertSee('Befejezem')
        ->assertDontSee('Nézd meg a leveled')
        ->assertDontSee('https://foglalas.example.com')
        ->set('curiosity', ['gap'])
        ->call('finish')
        ->assertRedirect(route('client.values', $organization));

    expect($session->fresh()->curiosity)->toBe(['gap']);
});

it('mails the questions in the user’s name, with a link to their datasheet', function () {
    Mail::fake();
    $organization = Organization::factory()->individual()->create();
    $user = actingAsCustomer(customerOf($organization));
    $session = signedInMirror($organization);

    Livewire::test(Result::class, ['session' => $session, 'organization' => $organization])
        ->set('curiosity', ['capabilities'])
        ->call('sendInterest')
        ->assertHasNoErrors();

    Mail::assertQueued(MirrorInterestMail::class, function (MirrorInterestMail $mail) use ($user) {
        $mail->assertHasSubject('Érdeklődés az Értéktükörből: '.$user->name)
            ->assertSeeInHtml(route('admin.clients.show', $user));

        return $mail->hasReplyTo($user->email);
    });
});

it('ends an invited member’s first mirror with their values, nothing more to do and the organization’s tree', function () {
    Mail::fake();
    $organization = Organization::factory()->create();
    actingAsCustomer(customerOf($organization));
    $session = signedInMirror($organization);

    $result = Livewire::test(Result::class, ['session' => $session, 'organization' => $organization])
        ->call('showValue', 1)
        ->call('approve')
        ->call('showFull')
        ->assertSeeHtmlInOrder(['Az értékeid:', 'Most nincs több dolgod', 'A szervezetetek is olyan, mint egy fa', 'Mit jelentenek a fa részei?', 'Vissza a kezdőlapra'])
        ->assertDontSee('Hogyan tovább?')
        ->assertDontSee('Kell egy vízió')
        ->assertDontSee('a te értékeid')
        ->assertDontSee('Befejezem')
        ->assertDontSee('Kíváncsi vagy?')
        ->assertDontSee('Elküldöm')
        ->assertSeeHtml(route('client.sample-report', $organization))
        ->assertSeeHtml(route('client.wisdom.show', [$organization, 'kuldetesfa']));

    $result->call('finish')
        ->assertRedirect(route('client.home', $organization));

    $result->set('curiosity', ['gap'])->assertForbidden();
    Livewire::test(Result::class, ['session' => $session, 'organization' => $organization])->call('sendInterest')->assertForbidden();

    Mail::assertNothingQueued();
});

it('goes on to ET5 only once every kept value is approved, signed in', function () {
    $organization = Organization::factory()->create();
    actingAsCustomer(customerOf($organization));
    $session = signedInMirror($organization);

    $result = Livewire::test(Result::class, ['session' => $session, 'organization' => $organization])
        ->assertSee('Előbb nézd át és hagyd jóvá minden értékedet.')
        ->call('showFull')
        ->assertSet('step', 'values')
        ->assertHasErrors('approval');

    $this->get(route('client.mirror.result', [$organization, $session]).'?lap=full')
        ->assertOk()
        ->assertDontSee('Az értékeid:');

    $result->call('showValue', 1)
        ->call('approve')
        ->assertSee('1 / 1 jóváhagyva')
        ->assertDontSee('Előbb nézd át és hagyd jóvá minden értékedet.')
        ->call('showFull')
        ->assertSet('step', 'full');
});

it('lets a value taken out stand in for an approved one', function () {
    $organization = Organization::factory()->create();
    actingAsCustomer(customerOf($organization));
    $session = signedInMirror($organization);
    $key = app(MirrorService::class)->groups($session)->first()->key;

    Livewire::test(Result::class, ['session' => $session, 'organization' => $organization])
        ->call('toggle', $key)
        ->call('showFull')
        ->assertSet('step', 'full');
});

it('deepens the values from the own menu too (ET4)', function () {
    $organization = Organization::factory()->create();
    actingAsCustomer(customerOf($organization));
    $session = signedInMirror($organization);

    Livewire::test(Result::class, ['session' => $session, 'organization' => $organization])
        ->call('showValue', 1)
        ->assertSee('Egyenesség · 1 / 1')
        ->assertSee('azonnal szóltam')
        ->assertSee('Soha.');
});

it('lists the earlier mirrors on the Értéktükör page, to open them again', function () {
    $organization = Organization::factory()->individual()->create();
    actingAsCustomer(customerOf($organization));
    $session = signedInMirror($organization);

    $this->get(route('client.mirror.record', $organization))
        ->assertOk()
        ->assertSee('Korábbi tükreid')
        ->assertSee(route('client.mirror.result', [$organization, $session]))
        ->assertSee('Egyenesség');

    actingAsCustomer(customerOf($organization));

    $this->get(route('client.mirror.record', $organization))->assertOk()->assertDontSee('Korábbi tükreid');
});

it('opens an invited member’s one mirror from the menu, and replaces it with a new one', function () {
    $organization = Organization::factory()->create();
    actingAsCustomer(customerOf($organization));
    $first = signedInMirror($organization);
    $firstDiagnosis = $first->diagnosis;

    $this->get(route('client.mirror.record', $organization))
        ->assertRedirect(route('client.mirror.result', [$organization, $first]));

    Livewire::test(Result::class, ['session' => $first, 'organization' => $organization])
        ->assertSee('Újrakezdem')
        ->call('startOver')
        ->assertRedirect(route('client.mirror.record', [$organization, 'uj' => 1]));

    $this->get(route('client.mirror.record', [$organization, 'uj' => 1]))
        ->assertOk()
        ->assertSee('Amikor beküldöd, a mostani tükröd és az értékei törlődnek')
        ->assertDontSee('Korábbi tükreid');

    AiGateway::fake([
        mirrorAnswerFor(['detections' => [['speaker' => 'Beszélő 1', 'quote' => 'megint szóltam', 'free_name' => 'Egyenesség', 'reasoning' => 'Szól.', 'strength' => 5]]]),
        mirrorAnswerFor(['results' => [['detection' => 'D1', 'matches' => []]]]),
        mirrorAnswerFor(['scores' => []]),
    ]);
    Livewire::withQueryParams(['uj' => 1])->test(Record::class, ['organization' => $organization])
        ->call('setMode', 'text')
        ->set('text', str_repeat('A héten megint szóltam a csúszásról, időben. ', 3))
        ->call('submitText')
        ->assertHasNoErrors();

    expect(MirrorSession::query()->whereKey($first->id)->exists())->toBeFalse()
        ->and(Diagnosis::query()->whereKey($firstDiagnosis->id)->exists())->toBeFalse()
        ->and(MirrorSession::query()->where('organization_id', $organization->id)->count())->toBe(1);
});

it('keeps the diagnosis the mégis already published when a new mirror replaces the old one', function () {
    $organization = Organization::factory()->create();
    $user = actingAsCustomer(customerOf($organization));
    $first = signedInMirror($organization);
    $first->diagnosis->update(['status' => DiagnosisStatus::Published]);
    $second = MirrorSession::factory()->ownMenu($user, $organization)->create();

    app(MirrorService::class)->replaceStandalone($user, $organization, $second);

    expect(MirrorSession::query()->whereKey($first->id)->exists())->toBeFalse()
        ->and(Diagnosis::query()->whereKey($first->diagnosis_id)->exists())->toBeTrue();
});

it('lets a private person record a new mirror from the menu, as before', function () {
    $organization = Organization::factory()->individual()->create();
    actingAsCustomer(customerOf($organization));
    signedInMirror($organization);

    $this->get(route('client.mirror.record', $organization))->assertOk()->assertSee('Korábbi tükreid');
});

it('does not show someone else’s mirror', function () {
    $organization = Organization::factory()->create();
    actingAsCustomer(customerOf($organization));
    $session = signedInMirror($organization);

    actingAsCustomer(customerOf($organization));

    $this->get(route('client.mirror.result', [$organization, $session]))->assertNotFound();
});
