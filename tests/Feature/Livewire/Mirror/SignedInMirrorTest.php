<?php

use App\Enums\MaterialContext;
use App\Livewire\Mirror\Record;
use App\Livewire\Mirror\Result;
use App\Mail\MirrorInterestMail;
use App\Models\MirrorSession;
use App\Models\Organization;
use App\Services\Ai\AiGateway;
use App\Services\Values\ValueService;
use Illuminate\Support\Facades\Mail;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StructuredTextResponse;
use Livewire\Livewire;

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

it('needs no Turnstile and goes to the active organization from the own menu', function () {
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
    $organization = Organization::factory()->create();
    actingAsCustomer(customerOf($organization));
    $session = signedInMirror($organization);

    Livewire::test(Result::class, ['session' => $session, 'organization' => $organization])
        ->call('showFull')
        ->assertDontSee('Nézd meg a leveled')
        ->assertDontSee('https://foglalas.example.com')
        ->set('curiosity', ['gap'])
        ->call('finish')
        ->assertRedirect(route('client.values', $organization));

    expect($session->fresh()->curiosity)->toBe(['gap']);
});

it('mails the questions in the user’s name, with a link to their datasheet', function () {
    Mail::fake();
    $organization = Organization::factory()->create();
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
    $organization = Organization::factory()->create();
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

it('does not show someone else’s mirror', function () {
    $organization = Organization::factory()->create();
    actingAsCustomer(customerOf($organization));
    $session = signedInMirror($organization);

    actingAsCustomer(customerOf($organization));

    $this->get(route('client.mirror.result', [$organization, $session]))->assertNotFound();
});
