<?php

use App\Services\Ai\AiGateway;
use App\Services\Values\ValueService;
use Illuminate\Support\Facades\Mail;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StructuredTextResponse;

it('walks the public Értéktükör from the opening through each value to the full picture', function () {
    Mail::fake();
    fakeTurnstile();
    config(['megis.mirror.deepening' => true]);
    app(ValueService::class)->create(['name' => 'Egyenesség', 'meanings' => ['Kimondom.'], 'high_examples' => ['Mindig.'], 'low_examples' => ['Soha.']]);
    $answer = fn (array $data) => new StructuredTextResponse($data, json_encode($data), new TextUsage(10, 5), new Meta('anthropic', 'claude-test'));
    AiGateway::fake([
        $answer(['detections' => [
            ['speaker' => 'Beszélő 1', 'quote' => 'inkább most mondom, mint csütörtökön', 'free_name' => 'Egyenesség', 'reasoning' => 'Rögtön kimondja.', 'strength' => 5],
            ['speaker' => 'Beszélő 1', 'quote' => 'a végén nevettünk egyet', 'free_name' => 'Játékosság', 'reasoning' => 'Humor.', 'strength' => 3],
        ]]),
        $answer(['results' => [['detection' => 'D1', 'matches' => [['ref' => 'E1', 'confidence' => 0.9]]], ['detection' => 'D2', 'matches' => []]]]),
        $answer(['scores' => [['ref' => 'E1', 'state' => 'scored', 'score' => 5, 'reasoning' => 'Így tesz.', 'detections' => ['D1']]]]),
        $answer(['values' => [
            ['ref' => 'V1', 'meaning' => 'Kimondom, amit gondolok: annak, akire tartozik, most.', 'signs' => ['szólok, ha csúszik', 'nem szépítem', 'felfelé is'], 'low' => 'Bólintok a meetingen, aztán a folyosón mondom el.', 'high' => 'Ott, akkor, annak, indoklással.'],
            ['ref' => 'V2', 'meaning' => 'A nehézben is nevetek egyet.', 'signs' => ['humorral oldom'], 'low' => 'Elfelejtek nevetni.', 'high' => 'A végén együtt nevetünk.'],
        ]]),
    ]);

    $page = visit('/');
    $page->assertSee('Belenézek a tükörbe')->assertNoAccessibilityIssues();

    $page->click('Belenézek a tükörbe');
    // The recorder's Alpine component starts (its config came through as JS)
    $page->assertPathIs('/ertektukor')
        ->assertSee('Hova küldjük a tükröd?')
        ->assertNoAccessibilityIssues()
        ->assertNoJavaScriptErrors();

    // The button under the e-mail part waits for a recording, and says why
    $page->assertButtonDisabled('Kész · mutasd a tükröt')
        ->assertSee('Előbb vedd fel, amit mesélnél.');

    // A finished recording (set in place of the microphone) goes only once the e-mail part is right
    $page->script("() => { const recorder = window.Alpine.\$data(document.querySelector('[x-data^=\"mirrorRecorder\"]')); recorder.blob = new Blob(['hang'], { type: 'audio/webm' }); recorder.seconds = 75; recorder.state = 'recorded'; }");
    $page->assertButtonEnabled('Kész · mutasd a tükröt')
        ->assertDontSee('Előbb vedd fel, amit mesélnél.');
    $page->press('Kész · mutasd a tükröt');
    $page->assertSee('A tükörhöz el kell fogadnod az ÁSZF-et és az adatvédelmi szabályzatot.')
        ->assertVisible('#email-error')
        ->assertDontSee('Feltöltés')
        ->assertButtonEnabled('Kész · mutasd a tükröt')
        ->assertNoJavaScriptErrors();

    $page->click('Inkább leírom');
    $page->fill('text', 'A héten inkább most mondom, mint csütörtökön, hogy csúszunk. A végén nevettünk egyet.');
    $page->fill('email', 'anna@example.com');
    $page->check('#mirror-consent');
    $page->script("() => window.Livewire.all()[0].\$wire.set('turnstileToken', 'test-token')");
    $page->press('Kész · mutasd a tükröt');

    $page->assertSee('Az értékeid')
        ->assertSee('Egyenesség')
        ->assertSee('Játékosság')
        ->assertNoAccessibilityIssues();

    // "nem az enyém" strikes the value through in its place; it can be taken back
    $page->click('[aria-label="Játékosság: nem az enyém"]');
    $page->assertVisible('[aria-label="Játékosság: mégis az enyém"]')
        ->assertVisible('h3.line-through')
        ->assertNoJavaScriptErrors();
    $page->click('[aria-label="Játékosság: mégis az enyém"]');
    $page->assertVisible('[aria-label="Játékosság: nem az enyém"]')
        ->assertMissing('h3.line-through');

    // ET4, value by value
    $page->click('h3 [wire\\:click="showValue(1)"]');
    $page->assertSee('Egyenesség · 1 / 2')
        ->assertSee('Kimondom, amit gondolok: annak, akire tartozik, most.')
        ->assertSee('szólok, ha csúszik')
        ->assertSee('Ott, akkor, annak, indoklással.')
        ->assertNoAccessibilityIssues();

    $page->press('Ez az');
    $page->assertSee('Ez lesz a te definíciód.');

    $page->click('[wire\\:click="showValue(2)"]');
    $page->assertSee('Játékosság · 2 / 2')->assertSee('A nehézben is nevetek egyet.');

    // ET5, without an e-mail field or a booking
    $page->press('A teljes kép');
    $page->assertSee('Kíváncsi vagy?')
        ->assertSee('micélium')
        ->assertSee('Nézd meg a leveled')
        ->assertDontSee('Időpontot foglalok')
        ->assertNoAccessibilityIssues()
        ->assertNoJavaScriptErrors();

    // "Elküldöm" goes once; "Hamarosan" stands in its place
    $page->check('input[value="gap"]');
    $page->press('Elküldöm');
    $page->assertSee('Hamarosan')
        ->assertDontSee('Elküldöm')
        ->assertNoAccessibilityIssues();

    $page->resize(390, 844);
    expect($page->script('() => document.documentElement.scrollWidth'))->toBeLessThanOrEqual(390);
});
