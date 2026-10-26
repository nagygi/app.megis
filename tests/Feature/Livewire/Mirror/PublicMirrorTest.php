<?php

use App\Ai\AiContext;
use App\Enums\InboxItemType;
use App\Enums\LegalDocumentType;
use App\Enums\MirrorStatus;
use App\Enums\OrganizationType;
use App\Enums\TokenPurpose;
use App\Livewire\Mirror\Record;
use App\Livewire\Mirror\Result;
use App\Mail\LoginCodeMail;
use App\Mail\MirrorConfirmationMail;
use App\Models\InboxItem;
use App\Models\LegalAcceptance;
use App\Models\LegalDocumentVersion;
use App\Models\MirrorSession;
use App\Models\User;
use App\Services\Ai\AiGateway;
use App\Services\Auth\TokenService;
use App\Services\Mirror\MirrorService;
use App\Services\Transcription\FakeTranscriber;
use App\Services\Transcription\Transcriber;
use App\Services\Transcription\TranscriptionResult;
use App\Services\Values\ValueService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StructuredTextResponse;
use Livewire\Livewire;

function mirrorAiAnswers(): void
{
    $answer = fn (array $data) => new StructuredTextResponse($data, json_encode($data), new TextUsage(10, 5), new Meta('anthropic', 'claude-test'));

    AiGateway::fake([
        $answer(['detections' => [
            ['speaker' => 'Beszélő 1', 'quote' => 'inkább most mondom, mint csütörtökön', 'free_name' => 'Egyenesség', 'reasoning' => 'Rögtön kimondja.', 'strength' => 5],
        ]]),
        $answer(['results' => [['detection' => 'D1', 'matches' => [['ref' => 'E1', 'confidence' => 0.9]]]]]),
        $answer(['scores' => [['ref' => 'E1', 'state' => 'scored', 'score' => 5, 'reasoning' => 'Így tesz.', 'detections' => ['D1']]]]),
    ]);
}

function submitTextMirror(): MirrorSession
{
    fakeTurnstile();
    mirrorAiAnswers();
    app(ValueService::class)->create(['name' => 'Egyenesség', 'meanings' => ['Kimondom.'], 'high_examples' => ['Mindig.'], 'low_examples' => ['Soha.']]);

    Livewire::test(Record::class)
        ->call('setMode', 'text')
        ->set('text', str_repeat('Múlt héten inkább most mondom, mint csütörtökön, hogy csúszunk. ', 2))
        ->set('turnstileToken', 'token')
        ->call('submitText')
        ->assertHasNoErrors();

    return MirrorSession::query()->sole();
}

it('opens with the manifesto and the two ways in', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Az érték')
        ->assertSee('Belenézek a tükörbe')
        ->assertSee('Van fiókom');
});

it('keeps the support address off the home page', function () {
    config(['megis.support_email' => 'support@megis.hu']);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('support@megis.hu');
});

it('reads a written situation and shows the values with quotes in the same browser only', function () {
    $session = submitTextMirror();

    expect($session->status)->toBe(MirrorStatus::Ready)
        ->and($session->expires_at->isFuture())->toBeTrue();

    $this->get(route('mirror.result', $session))->assertOk()->assertSee('Egyenesség')->assertSee('inkább most mondom, mint csütörtökön');

    session()->forget('mirror_tokens');
    $this->get(route('mirror.result', $session))->assertNotFound();
});

it('requires Turnstile and limits public submissions per IP', function () {
    fakeTurnstile(false);

    Livewire::test(Record::class)
        ->call('setMode', 'text')
        ->set('text', str_repeat('Egy helyzet a hétről. ', 5))
        ->call('submitText')
        ->assertHasErrors(['turnstileToken']);

    expect(MirrorSession::query()->count())->toBe(0);
});

it('asks the widget for a new token once a token has been checked', function () {
    fakeTurnstile(false);

    Livewire::test(Record::class)
        ->call('setMode', 'text')
        ->set('text', str_repeat('Egy helyzet a hétről. ', 5))
        ->call('submitText')
        ->assertHasErrors(['turnstileToken'])
        ->assertNotDispatched('turnstile-reset')
        ->set('turnstileToken', 'token')
        ->call('submitText')
        ->assertHasErrors(['turnstileToken'])
        ->assertSet('turnstileToken', '')
        ->assertDispatched('turnstile-reset');
});

it('keeps the Turnstile token while the account form has a mistake', function () {
    Mail::fake();
    $session = submitTextMirror();

    Livewire::test(Result::class, ['session' => $session])
        ->call('showFull')
        ->set('email', 'anna@example.com')
        ->set('turnstileToken', 'token')
        ->call('createAccount', false)
        ->assertHasErrors(['consent' => 'accepted'])
        ->assertSet('turnstileToken', 'token')
        ->assertNotDispatched('turnstile-reset')
        ->set('consent', true)
        ->call('createAccount', false)
        ->assertHasNoErrors()
        ->assertDispatched('turnstile-reset');

    Mail::assertQueued(MirrorConfirmationMail::class);
});

it('asks for at least a few sentences when typing', function () {
    fakeTurnstile();

    Livewire::test(Record::class)
        ->call('setMode', 'text')
        ->set('text', 'Rövid.')
        ->call('submitText')
        ->assertHasErrors(['text' => 'min']);
});

it('refuses a .doc file and asks for docx', function () {
    fakeTurnstile();

    Livewire::test(Record::class)
        ->call('setMode', 'file')
        ->set('file', UploadedFile::fake()->createWithContent('helyzet.doc', 'régi formátum'))
        ->set('turnstileToken', 'token')
        ->call('submitFile')
        ->assertHasErrors(['file']);
});

it('takes a browser recording to the private disk', function () {
    Storage::fake('materials');
    fakeTurnstile();
    config(['megis.transcriber' => 'fake']);
    mirrorAiAnswers();

    Livewire::test(Record::class)
        ->set('recording', UploadedFile::fake()->create('felvetel.webm', 200, 'audio/webm'))
        ->set('turnstileToken', 'token')
        ->call('submitRecording', 75)
        ->assertHasNoErrors();

    $material = MirrorSession::query()->sole()->material;
    expect($material->audio_seconds)->toBe(75)
        ->and($material->segments)->not->toBeEmpty();
    Storage::disk('materials')->assertExists($material->audio_path);
});

it('transcribes a failed recording again without sending it again', function () {
    Storage::fake('materials');
    fakeTurnstile();
    mirrorAiAnswers();
    app(ValueService::class)->create(['name' => 'Egyenesség', 'meanings' => ['Kimondom.'], 'high_examples' => ['Mindig.'], 'low_examples' => ['Soha.']]);
    app()->instance(Transcriber::class, new class implements Transcriber
    {
        public function transcribe(string $path, AiContext $context): TranscriptionResult
        {
            return new TranscriptionResult('', []);
        }
    });

    Livewire::test(Record::class)
        ->set('recording', UploadedFile::fake()->create('felvetel.webm', 200, 'audio/webm'))
        ->set('turnstileToken', 'token')
        ->call('submitRecording', 75);

    $session = MirrorSession::query()->sole();
    $audioPath = $session->material->audio_path;
    expect($session->status)->toBe(MirrorStatus::Failed)
        ->and(InboxItem::query()->open()->sole()->type)->toBe(InboxItemType::ProcessingError);

    app()->instance(Transcriber::class, new FakeTranscriber);

    Livewire::test(Result::class, ['session' => $session])
        ->assertSee('Amit küldtél, megvan, nem kell újra beküldened.')
        ->call('retry')
        ->assertSee('Egyenesség');

    expect($session->refresh()->status)->toBe(MirrorStatus::Ready)
        ->and(MirrorSession::query()->count())->toBe(1)
        ->and($session->material->audio_path)->toBe($audioPath)
        ->and($session->material->text)->not->toBeEmpty()
        ->and(InboxItem::query()->open()->count())->toBe(0);
});

it('tries a failed mirror three times in an hour, then asks to start over', function () {
    $session = submitTextMirror();
    $session->update(['status' => MirrorStatus::Failed]);
    RateLimiter::increment('mirror-retry:'.$session->id, 3600, 3);

    Livewire::test(Result::class, ['session' => $session])
        ->call('retry')
        ->assertSee('Most többször nem próbálhatjuk újra.')
        ->assertSee('Inkább újrakezdem');

    expect($session->refresh()->status)->toBe(MirrorStatus::Failed);
});

it('sends back to recording when nothing is left to try again', function () {
    $session = submitTextMirror();
    $session->update(['status' => MirrorStatus::Failed]);
    $session->material->update(['text' => null]);

    Livewire::test(Result::class, ['session' => $session])
        ->assertDontSee('Amit küldtél, megvan')
        ->assertSee(route('mirror.record'))
        ->call('retry')
        ->assertForbidden();
});

it('sends back to recording when no value was heard', function () {
    $session = submitTextMirror();
    $session->material->detections()->delete();

    Livewire::test(Result::class, ['session' => $session])
        ->assertSee('Ebből most nem hallottunk ki értéket.')
        ->assertSee('Újra próbálom')
        ->assertSee(route('mirror.record'));
});

it('sends a confirmation link and records the accepted documents with the account', function () {
    Mail::fake();
    $terms = LegalDocumentVersion::factory()->ofType(LegalDocumentType::Terms)->create();
    $privacy = LegalDocumentVersion::factory()->ofType(LegalDocumentType::PrivacyPolicy)->create();
    $session = submitTextMirror();

    Livewire::test(Result::class, ['session' => $session])
        ->call('showFull')
        ->set('curiosity', ['inner', 'capabilities'])
        ->set('email', 'Anna@Example.com')
        ->call('createAccount', false)
        ->assertHasErrors(['consent' => 'accepted'])
        ->set('consent', true)
        ->set('turnstileToken', 'token')
        ->call('createAccount', false)
        ->assertHasNoErrors()
        ->assertSee('Nézd meg a leveled');

    $token = null;
    Mail::assertQueued(MirrorConfirmationMail::class, function (MirrorConfirmationMail $mail) use (&$token) {
        $token = $mail->token;

        return true;
    });

    $this->get(route('mirror.confirmation.show', $token))->assertOk()->assertSee('anna@example.com');
    $this->post(route('mirror.confirmation.store', $token), ['first_name' => 'Anna', 'last_name' => 'Kiss'])
        ->assertRedirect();

    $user = User::query()->firstWhere('email', 'anna@example.com');
    $organization = $user->organizations()->sole();
    $session->refresh();

    $this->assertAuthenticatedAs($user, 'web');
    expect($user->isActivated())->toBeTrue()
        ->and($user->password)->toBeNull()
        ->and($organization->type)->toBe(OrganizationType::Individual)
        ->and($organization->name)->toBe('Kiss Anna')
        ->and($user->memberships()->sole()->is_organization_leader)->toBeTrue()
        ->and(LegalAcceptance::query()->where('user_id', $user->id)->pluck('legal_document_version_id')->sort()->values()->all())->toBe(collect([$terms->id, $privacy->id])->sort()->values()->all())
        ->and($session->status)->toBe(MirrorStatus::Claimed)
        ->and($session->curiosity)->toBe(['inner', 'capabilities'])
        ->and($session->diagnosis->organization_id)->toBe($organization->id);
});

it('lands someone who only made a mirror on their values in the Értéktár', function () {
    Mail::fake();
    $session = submitTextMirror();
    Livewire::test(Result::class, ['session' => $session])
        ->call('showFull')->set('email', 'uj@example.com')->set('consent', true)->set('turnstileToken', 'token')->call('createAccount', false);
    $token = null;
    Mail::assertQueued(MirrorConfirmationMail::class, function ($mail) use (&$token) {
        $token = $mail->token;

        return true;
    });

    $response = $this->post(route('mirror.confirmation.store', $token), ['first_name' => 'Új', 'last_name' => 'Ember']);

    $organization = User::query()->firstWhere('email', 'uj@example.com')->organizations()->sole();
    $response->assertRedirect(route('client.values', $organization));
});

it('does not use the confirmation link by opening it', function () {
    Mail::fake();
    $user = User::create(['email' => 'nyitas@example.com']);
    $issued = app(TokenService::class)->issue($user, TokenPurpose::MirrorConfirmation);

    $this->get(route('mirror.confirmation.show', $issued->plainToken))->assertOk();

    expect($issued->token->fresh()->isPending())->toBeTrue();
});

it('starts the 24 hours of the mirror again with the confirmation link', function () {
    Mail::fake();
    $session = submitTextMirror();
    $this->travel(23)->hours();

    Livewire::test(Result::class, ['session' => $session])
        ->call('showFull')->set('email', 'kesei@example.com')->set('consent', true)->set('turnstileToken', 'token')->call('createAccount', false)
        ->assertHasNoErrors();
    $token = null;
    Mail::assertQueued(MirrorConfirmationMail::class, function ($mail) use (&$token) {
        $token = $mail->token;

        return true;
    });

    $this->travel(23)->hours();

    expect(app(MirrorService::class)->pruneExpired())->toBe(0);

    $this->post(route('mirror.confirmation.store', $token), ['first_name' => 'Kata', 'last_name' => 'Kései'])->assertRedirect();

    expect($session->fresh()->status)->toBe(MirrorStatus::Claimed);
});

it('keeps the pending account while a newer mirror waits on the same address', function () {
    Mail::fake();
    $mirrors = app(MirrorService::class);
    $mirrors->requestAccount(MirrorSession::factory()->create(), 'ketszer@example.com', [], false);
    $this->travel(12)->hours();
    $mirrors->requestAccount(MirrorSession::factory()->create(), 'ketszer@example.com', [], false);

    $this->travel(13)->hours();

    expect($mirrors->pruneExpired())->toBe(1)
        ->and(User::query()->where('email', 'ketszer@example.com')->exists())->toBeTrue();

    $this->travel(12)->hours();

    expect($mirrors->pruneExpired())->toBe(1)
        ->and(User::query()->where('email', 'ketszer@example.com')->exists())->toBeFalse();
});

it('lets an existing account sign in on the spot with a code and keeps the mirror', function () {
    Mail::fake();
    $existing = customerOf();
    $session = submitTextMirror();

    $component = Livewire::test(Result::class, ['session' => $session])
        ->call('showFull')
        ->set('email', $existing->email)
        ->set('consent', true)
        ->set('turnstileToken', 'token')
        ->call('createAccount', false)
        ->assertSee('Ehhez az e-mailhez már van fiókod.')
        ->set('loginMode', 'code')
        ->set('turnstileToken', 'token')
        ->call('sendCode');

    $code = null;
    Mail::assertQueued(LoginCodeMail::class, function (LoginCodeMail $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    $component->set('code', $code)->call('loginWithCode')->assertRedirect();

    $this->assertAuthenticatedAs($existing, 'web');
    expect($session->fresh()->diagnosis->organization_id)->toBe($existing->organizations()->first()->id);
    Mail::assertNotQueued(MirrorConfirmationMail::class);
});

it('signs an existing account in with its password', function () {
    $existing = customerOf();
    $existing->forceFill(['password' => Hash::make('jo-jelszo-123')])->save();
    $session = submitTextMirror();

    Livewire::test(Result::class, ['session' => $session])
        ->call('showFull')->set('email', $existing->email)->set('consent', true)->set('turnstileToken', 'token')->call('createAccount', false)
        ->set('password', 'rossz')->set('turnstileToken', 'token')->call('loginWithPassword')
        ->assertHasErrors(['password'])
        ->set('password', 'jo-jelszo-123')->set('turnstileToken', 'token')->call('loginWithPassword')
        ->assertRedirect();

    expect($session->fresh()->user_id)->toBe($existing->id);
});

it('opens a Beérkező row with the curiosity questions when booking', function () {
    Mail::fake();
    $session = submitTextMirror();

    Livewire::test(Result::class, ['session' => $session])
        ->call('showFull')
        ->set('curiosity', ['outer'])
        ->set('email', 'foglal@example.com')
        ->set('consent', true)
        ->set('turnstileToken', 'token')
        ->call('createAccount', true);

    expect(InboxItem::query()->sole())
        ->type->toBe(InboxItemType::MirrorBooking)
        ->summary->toBe('foglal@example.com időpontot foglalna · kíváncsi: kívülről');
});

it('marks a value not mine on ET3', function () {
    $session = submitTextMirror();
    $key = app(MirrorService::class)->groups($session)->first()->key;

    Livewire::test(Result::class, ['session' => $session])
        ->call('toggle', $key)
        ->assertSee('mégis az enyém');

    expect($session->material->detections()->sole()->isExcluded())->toBeTrue();
});
