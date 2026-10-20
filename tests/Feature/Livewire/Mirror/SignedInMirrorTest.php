<?php

use App\Enums\InboxItemType;
use App\Enums\MaterialContext;
use App\Livewire\Mirror\Record;
use App\Livewire\Mirror\Result;
use App\Models\InboxItem;
use App\Models\MirrorSession;
use App\Models\Organization;
use App\Services\Ai\AiGateway;
use App\Services\Values\ValueService;
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

it('shows no e-mail step and keeps the curiosity questions when finishing', function () {
    $organization = Organization::factory()->create();
    actingAsCustomer(customerOf($organization));
    $session = signedInMirror($organization);

    Livewire::test(Result::class, ['session' => $session, 'organization' => $organization])
        ->call('showFull')
        ->assertDontSee('Létrehozom a fiókot')
        ->set('curiosity', ['gap'])
        ->call('finish')
        ->assertRedirect(route('client.values', $organization));

    expect($session->fresh()->curiosity)->toBe(['gap']);
});

it('opens a booking row from the own menu', function () {
    config(['megis.booking_url' => 'https://foglalas.example.com']);
    $organization = Organization::factory()->create();
    actingAsCustomer(customerOf($organization));
    $session = signedInMirror($organization);

    Livewire::test(Result::class, ['session' => $session, 'organization' => $organization])
        ->call('showFull')
        ->assertSee('https://foglalas.example.com')
        ->call('book');

    expect(InboxItem::query()->sole()->type)->toBe(InboxItemType::MirrorBooking);
});

it('does not show someone else’s mirror', function () {
    $organization = Organization::factory()->create();
    actingAsCustomer(customerOf($organization));
    $session = signedInMirror($organization);

    actingAsCustomer(customerOf($organization));

    $this->get(route('client.mirror.result', [$organization, $session]))->assertNotFound();
});
