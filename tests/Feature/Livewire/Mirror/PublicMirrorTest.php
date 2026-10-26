<?php

use App\Ai\AiContext;
use App\Enums\InboxItemType;
use App\Enums\LegalDocumentType;
use App\Enums\MirrorInsightStatus;
use App\Enums\MirrorStatus;
use App\Enums\OrganizationType;
use App\Enums\TokenPurpose;
use App\Jobs\AnalyzeMaterial;
use App\Livewire\Mirror\Record;
use App\Livewire\Mirror\Result;
use App\Mail\LoginCodeMail;
use App\Mail\MirrorConfirmationMail;
use App\Mail\MirrorInterestMail;
use App\Models\InboxItem;
use App\Models\LegalAcceptance;
use App\Models\LegalDocumentVersion;
use App\Models\MirrorSession;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserToken;
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
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StructuredTextResponse;
use Livewire\Livewire;

function mirrorAnswer(array $data): StructuredTextResponse
{
    return new StructuredTextResponse($data, json_encode($data), new TextUsage(10, 5), new Meta('anthropic', 'claude-test'));
}

/**
 * The three engine answers, and the mégis Coach's when ET4 is on.
 */
function mirrorAiAnswers(): void
{
    AiGateway::fake([
        mirrorAnswer(['detections' => [
            ['speaker' => 'Beszélő 1', 'quote' => 'inkább most mondom, mint csütörtökön', 'free_name' => 'Egyenesség', 'reasoning' => 'Rögtön kimondja.', 'strength' => 5],
        ]]),
        mirrorAnswer(['results' => [['detection' => 'D1', 'matches' => [['ref' => 'E1', 'confidence' => 0.9]]]]]),
        mirrorAnswer(['scores' => [['ref' => 'E1', 'state' => 'scored', 'score' => 5, 'reasoning' => 'Így tesz.', 'detections' => ['D1']]]]),
        ...(config('megis.mirror.deepening') ? [mirrorAnswer(['values' => [[
            'ref' => 'V1',
            'meaning' => 'Kimondom, amit gondolok: annak, akire tartozik, most.',
            'signs' => ['szólok, ha csúszik', 'nem szépítem'],
            'low' => 'Bólintok a meetingen, aztán a folyosón mondom el.',
            'high' => 'Ott, akkor, annak, indoklással.',
        ]]])] : []),
    ]);
}

/**
 * ET2 with the e-mail and the accepted documents, a written situation.
 */
function submitTextMirror(string $email = 'anna@example.com'): MirrorSession
{
    fakeTurnstile();
    mirrorAiAnswers();
    app(ValueService::class)->create(['name' => 'Egyenesség', 'meanings' => ['Kimondom.'], 'high_examples' => ['Mindig.'], 'low_examples' => ['Soha.']]);

    Livewire::test(Record::class)
        ->call('setMode', 'text')
        ->set('text', str_repeat('Múlt héten inkább most mondom, mint csütörtökön, hogy csúszunk. ', 2))
        ->set('email', $email)
        ->set('consent', true)
        ->set('turnstileToken', 'token')
        ->call('submitText')
        ->assertHasNoErrors();

    return MirrorSession::query()->sole();
}

function confirmationToken(): string
{
    $token = null;
    Mail::assertQueued(MirrorConfirmationMail::class, function (MirrorConfirmationMail $mail) use (&$token) {
        $token = $mail->token;

        return true;
    });

    return $token;
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

it('asks for the e-mail and the accepted documents before the mirror is sent', function () {
    Mail::fake();
    fakeTurnstile();
    $terms = LegalDocumentVersion::factory()->ofType(LegalDocumentType::Terms)->create();
    $privacy = LegalDocumentVersion::factory()->ofType(LegalDocumentType::PrivacyPolicy)->create();

    Livewire::test(Record::class)
        ->assertSee('Hova küldjük a tükröd?')
        ->assertSee(route('legal.show', $terms->type->value))
        ->assertSee(route('legal.show', $privacy->type->value))
        ->call('setMode', 'text')
        ->set('text', str_repeat('Egy helyzet a hétről. ', 5))
        ->set('turnstileToken', 'token')
        ->call('submitText')
        ->assertHasErrors(['email' => 'required', 'consent' => 'accepted'])
        ->set('email', 'nem-email')
        ->call('submitText')
        ->assertHasErrors(['email' => 'email']);

    expect(MirrorSession::query()->count())->toBe(0);
    Mail::assertNothingQueued();
});

it('asks for no e-mail from the own menu', function () {
    $organization = Organization::factory()->create();
    actingAsCustomer(customerOf($organization));

    Livewire::test(Record::class, ['organization' => $organization])
        ->assertDontSee('Hova küldjük a tükröd?');
});

it('requires Turnstile and limits public submissions per IP', function () {
    fakeTurnstile(false);

    Livewire::test(Record::class)
        ->call('setMode', 'text')
        ->set('text', str_repeat('Egy helyzet a hétről. ', 5))
        ->set('email', 'anna@example.com')
        ->set('consent', true)
        ->call('submitText')
        ->assertHasErrors(['turnstileToken']);

    expect(MirrorSession::query()->count())->toBe(0);
});

it('asks the widget for a new token once a token has been checked', function () {
    fakeTurnstile(false);

    Livewire::test(Record::class)
        ->call('setMode', 'text')
        ->set('text', str_repeat('Egy helyzet a hétről. ', 5))
        ->set('email', 'anna@example.com')
        ->set('consent', true)
        ->call('submitText')
        ->assertHasErrors(['turnstileToken'])
        ->assertNotDispatched('turnstile-reset')
        ->set('turnstileToken', 'token')
        ->call('submitText')
        ->assertHasErrors(['turnstileToken'])
        ->assertSet('turnstileToken', '')
        ->assertDispatched('turnstile-reset');
});

it('keeps the Turnstile token while the e-mail part has a mistake', function () {
    Mail::fake();
    fakeTurnstile();
    mirrorAiAnswers();

    Livewire::test(Record::class)
        ->call('setMode', 'text')
        ->set('text', str_repeat('Múlt héten inkább most mondom, mint csütörtökön. ', 2))
        ->set('email', 'anna@example.com')
        ->set('turnstileToken', 'token')
        ->call('submitText')
        ->assertHasErrors(['consent' => 'accepted'])
        ->assertSet('turnstileToken', 'token')
        ->assertNotDispatched('turnstile-reset')
        ->set('consent', true)
        ->call('submitText')
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
        ->set('email', 'anna@example.com')
        ->set('consent', true)
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
        ->set('email', 'anna@example.com')
        ->set('consent', true)
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

it('sends the confirmation link with the mirror and records the accepted documents with the account', function () {
    Mail::fake();
    $terms = LegalDocumentVersion::factory()->ofType(LegalDocumentType::Terms)->create();
    $privacy = LegalDocumentVersion::factory()->ofType(LegalDocumentType::PrivacyPolicy)->create();
    $session = submitTextMirror('Anna@Example.com');

    expect($session->email)->toBe('anna@example.com')
        ->and($session->confirmation_sent_at)->not->toBeNull()
        ->and($session->status)->toBe(MirrorStatus::Ready);

    Livewire::test(Result::class, ['session' => $session])
        ->call('showFull')
        ->set('curiosity', ['inner', 'capabilities'])
        ->assertSee('Nézd meg a leveled')
        ->assertSee('anna@example.com');

    $token = confirmationToken();

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

it('opens the mirror in the account after the confirmation, its values expanded and the full picture', function () {
    Mail::fake();
    $session = submitTextMirror('uj@example.com');

    $response = $this->post(route('mirror.confirmation.store', confirmationToken()), ['first_name' => 'Új', 'last_name' => 'Ember']);

    $organization = User::query()->firstWhere('email', 'uj@example.com')->organizations()->sole();
    $response->assertRedirect(route('client.mirror.result', [$organization, $session]));

    $this->get(route('client.mirror.result', [$organization, $session]))->assertOk()->assertSee('Egyenesség');
    $this->get(route('client.mirror.result', [$organization, $session]).'?lap=full')->assertOk()->assertSee('Teljes kép');
});

it('gets the diagnosis when the account is confirmed while the mirror is still being read', function () {
    Mail::fake();
    Queue::fake([AnalyzeMaterial::class]);
    $session = submitTextMirror('gyors@example.com');

    expect($session->status)->toBe(MirrorStatus::Processing);

    $this->post(route('mirror.confirmation.store', confirmationToken()), ['first_name' => 'Gyors', 'last_name' => 'Kata'])->assertRedirect();

    $session->refresh();
    expect($session->claimed_at)->not->toBeNull()
        ->and($session->diagnosis_id)->toBeNull()
        ->and($session->status)->toBe(MirrorStatus::Processing);

    // The reading ends now; the faked queue would swallow a dispatch.
    app()->call([new AnalyzeMaterial($session->material->fresh()), 'handle']);

    $session->refresh();
    expect($session->status)->toBe(MirrorStatus::Claimed)
        ->and($session->diagnosis->organization_id)->toBe(User::query()->firstWhere('email', 'gyors@example.com')->organizations()->sole()->id);
});

it('does not use the confirmation link by opening it', function () {
    Mail::fake();
    $user = User::create(['email' => 'nyitas@example.com']);
    $issued = app(TokenService::class)->issue($user, TokenPurpose::MirrorConfirmation);

    $this->get(route('mirror.confirmation.show', $issued->plainToken))->assertOk();

    expect($issued->token->fresh()->isPending())->toBeTrue();
});

it('lets the mirror and its confirmation link expire together', function () {
    Mail::fake();
    $session = submitTextMirror('kesei@example.com');
    $token = confirmationToken();

    expect($session->expires_at->equalTo(UserToken::query()->sole()->expires_at))->toBeTrue();

    $this->travel(23)->hours();
    expect(app(MirrorService::class)->pruneExpired())->toBe(0);

    $this->post(route('mirror.confirmation.store', $token), ['first_name' => 'Kata', 'last_name' => 'Kései'])->assertRedirect();

    expect($session->fresh()->status)->toBe(MirrorStatus::Claimed);
});

it('keeps the pending account while a newer mirror waits on the same address', function () {
    Mail::fake();
    $mirrors = app(MirrorService::class);
    $mirrors->requestAccount(MirrorSession::factory()->create(), 'ketszer@example.com');
    $this->travel(12)->hours();
    $mirrors->requestAccount(MirrorSession::factory()->create(), 'ketszer@example.com');

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
    $session = submitTextMirror($existing->email);

    expect($session->belongsToExistingAccount())->toBeTrue();

    $component = Livewire::test(Result::class, ['session' => $session])
        ->call('showFull')
        ->assertSee('Ehhez az e-mailhez már van fiókod.')
        ->assertDontSee('Nézd meg a leveled')
        ->set('loginMode', 'code')
        ->set('turnstileToken', 'token')
        ->call('sendCode');

    $code = null;
    Mail::assertQueued(LoginCodeMail::class, function (LoginCodeMail $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    $organization = $existing->organizations()->first();
    $component->set('code', $code)->call('loginWithCode')->assertRedirect(route('client.mirror.result', [$organization, $session]));

    $this->assertAuthenticatedAs($existing, 'web');
    expect($session->fresh()->diagnosis->organization_id)->toBe($organization->id);
    Mail::assertNotQueued(MirrorConfirmationMail::class);
});

it('signs an existing account in with its password', function () {
    $existing = customerOf();
    $existing->forceFill(['password' => Hash::make('jo-jelszo-123')])->save();
    $session = submitTextMirror($existing->email);

    Livewire::test(Result::class, ['session' => $session])
        ->call('showFull')
        ->set('password', 'rossz')->set('turnstileToken', 'token')->call('loginWithPassword')
        ->assertHasErrors(['password'])
        ->set('password', 'jo-jelszo-123')->set('turnstileToken', 'token')->call('loginWithPassword')
        ->assertRedirect();

    expect($session->fresh()->user_id)->toBe($existing->id);
});

it('does not sign in from a mirror whose address waits for its link', function () {
    Mail::fake();
    $session = submitTextMirror();

    Livewire::test(Result::class, ['session' => $session])
        ->set('loginMode', 'code')
        ->call('sendCode')
        ->assertForbidden();
});

it('asks for no e-mail and offers no booking under the full picture, and keeps the ticked questions at once', function () {
    config(['megis.booking_url' => 'https://foglalas.example.com']);
    Mail::fake();
    $session = submitTextMirror();

    Livewire::test(Result::class, ['session' => $session])
        ->call('showFull')
        ->assertSee('Kíváncsi vagy?')
        ->assertSee('micélium')
        ->assertDontSee('https://foglalas.example.com')
        ->assertDontSee('Időpontot foglalok')
        ->assertDontSeeHtml('type="email"')
        ->set('curiosity', ['outer']);

    expect($session->fresh()->curiosity)->toBe(['outer'])
        ->and(InboxItem::query()->count())->toBe(0);
});

it('mails the ticked questions to the configured address with "Elküldöm", the reply going to the user', function () {
    config(['megis.interest_email' => 'erdeklodes@megis.example']);
    Mail::fake();
    $session = submitTextMirror();

    Livewire::test(Result::class, ['session' => $session])
        ->call('showFull')
        ->call('sendInterest')
        ->assertSee('Jelölj be legalább egy kérdést.')
        ->set('curiosity', ['inner', 'gap'])
        ->call('sendInterest')
        ->assertHasNoErrors()
        ->assertSee('Elküldtük. A mégis hamarosan jelentkezik ide: anna@example.com.');

    Mail::assertQueued(MirrorInterestMail::class, function (MirrorInterestMail $mail) {
        $mail->assertSeeInText('Hogyan jelennek meg a belső működésben?')
            ->assertSeeInText('Hol válik el a kettő?')
            ->assertDontSeeInText('Hogyan látszanak kívülről?')
            ->assertSeeInText('Az értékei: Egyenesség');

        return $mail->hasTo('erdeklodes@megis.example') && $mail->hasReplyTo('anna@example.com');
    });
});

it('mails the questions of one mirror three times in an hour', function () {
    Mail::fake();
    $session = submitTextMirror();

    $component = Livewire::test(Result::class, ['session' => $session])
        ->call('showFull')
        ->set('curiosity', ['outer']);

    foreach (range(1, 3) as $attempt) {
        $component->call('sendInterest')->assertHasNoErrors();
    }

    $component->call('sendInterest')->assertSee('Ezt már elküldted. A mégis hamarosan jelentkezik.');

    Mail::assertQueued(MirrorInterestMail::class, 3);
});

it('marks a value not mine on ET3', function () {
    $session = submitTextMirror();
    $key = app(MirrorService::class)->groups($session)->first()->key;

    Livewire::test(Result::class, ['session' => $session])
        ->assertSeeHtml('wire:click="toggle(\''.$key.'\')"')
        ->call('toggle', $key)
        ->assertSee('mégis az enyém');

    expect($session->material->detections()->sole()->isExcluded())->toBeTrue();
});

it('deepens a value with the mégis Coach from the quotes (ET4)', function () {
    config(['megis.mirror.deepening' => true]);
    $session = submitTextMirror();

    expect($session->insight_status)->toBe(MirrorInsightStatus::Ready);

    Livewire::test(Result::class, ['session' => $session])
        ->call('showValue', 1)
        ->assertSee('Egyenesség · 1 / 1')
        ->assertSee('Így néz ki nálad, a te idézeteidből.')
        ->assertSee('Kimondom, amit gondolok: annak, akire tartozik, most.')
        ->assertSee('szólok, ha csúszik')
        ->assertSee('Bólintok a meetingen, aztán a folyosón mondom el.')
        ->assertSee('Ott, akkor, annak, indoklással.')
        ->assertSee('inkább most mondom, mint csütörtökön')
        ->assertSee('a te szöveged')
        ->assertSee('A teljes kép');
});

it('takes the Coach’s meaning, or the user’s own sentence instead', function () {
    config(['megis.mirror.deepening' => true]);
    $session = submitTextMirror();
    $insight = $session->insights()->sole();

    $component = Livewire::test(Result::class, ['session' => $session])
        ->call('showValue', 1)
        ->call('accept')
        ->assertSee('Ez lesz a te definíciód.');

    expect($insight->fresh()->accepted_at)->not->toBeNull();

    $component->call('startRewrite')
        ->assertSet('meaning', 'Kimondom, amit gondolok: annak, akire tartozik, most.')
        ->set('meaning', '')
        ->call('saveRewrite')
        ->assertHasErrors(['meaning' => 'required'])
        ->set('meaning', 'Akkor szólok, amikor még számít.')
        ->call('saveRewrite')
        ->assertHasNoErrors()
        ->assertSee('Akkor szólok, amikor még számít.')
        ->assertSee('A te szavaiddal mentettük.');

    expect($insight->fresh())
        ->meaning->toBe('Akkor szólok, amikor még számít.')
        ->edited_at->not->toBeNull();
});

it('waits for the Coach on ET4, and shows the Értéktár’s words when the Coach did not answer', function () {
    $session = submitTextMirror();
    $session->update(['insight_status' => MirrorInsightStatus::Pending]);

    $component = Livewire::test(Result::class, ['session' => $session])
        ->call('showValue', 1)
        ->assertSee('A mégis Coach a te szavaidra fordítja')
        ->assertDontSee('Soha.');

    $session->update(['insight_status' => MirrorInsightStatus::Failed]);

    $component->call('$refresh')
        ->assertDontSee('A mégis Coach a te szavaidra fordítja')
        ->assertSee('Kimondom.')
        ->assertSee('Soha.')
        ->assertSee('Mindig.')
        ->assertDontSee('Ez az');
});

it('pages through the kept values on ET4', function () {
    fakeTurnstile();
    app(ValueService::class)->create(['name' => 'Egyenesség', 'meanings' => ['Kimondom.'], 'high_examples' => ['Mindig.'], 'low_examples' => ['Soha.']]);
    AiGateway::fake([
        mirrorAnswer(['detections' => [
            ['speaker' => 'Beszélő 1', 'quote' => 'inkább most mondom, mint csütörtökön', 'free_name' => 'Egyenesség', 'reasoning' => 'Kimondja.', 'strength' => 5],
            ['speaker' => 'Beszélő 1', 'quote' => 'a végén azért nevettünk egyet', 'free_name' => 'Játékosság', 'reasoning' => 'Humor.', 'strength' => 3],
        ]]),
        mirrorAnswer(['results' => [['detection' => 'D1', 'matches' => [['ref' => 'E1', 'confidence' => 0.9]]], ['detection' => 'D2', 'matches' => []]]]),
        mirrorAnswer(['scores' => [['ref' => 'E1', 'state' => 'scored', 'score' => 5, 'reasoning' => 'Így tesz.', 'detections' => ['D1']]]]),
    ]);
    Livewire::test(Record::class)
        ->call('setMode', 'text')
        ->set('text', str_repeat('Inkább most mondom, mint csütörtökön, és a végén nevettünk egyet. ', 2))
        ->set('email', 'lapoz@example.com')->set('consent', true)->set('turnstileToken', 'token')
        ->call('submitText');
    $session = MirrorSession::query()->sole();

    $component = Livewire::test(Result::class, ['session' => $session])
        ->assertSee('Egyenesség')
        ->assertSee('Játékosság')
        ->call('showValue', 1)
        ->assertSee('Egyenesség · 1 / 2')
        ->assertSeeHtml('wire:click="showValue(2)"')
        ->call('showValue', 2)
        ->assertSee('Játékosság · 2 / 2')
        ->assertSee('a végén azért nevettünk egyet')
        ->assertDontSeeHtml('wire:click="showValue(3)"');

    // A value taken out on ET3 keeps its place there, struck through, and leaves the pages.
    $component->call('showValues')
        ->call('toggle', app(MirrorService::class)->groups($session)->firstWhere('name', 'Egyenesség')->key)
        ->assertSeeHtml('line-through">Egyenesség</h3>')
        ->call('showValue', 1)
        ->assertSee('Játékosság · 1 / 1');

    expect(app(MirrorService::class)->groups($session)->pluck('name')->all())->toBe(['Egyenesség', 'Játékosság']);
});
