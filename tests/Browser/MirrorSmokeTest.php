<?php

use App\Services\Ai\AiGateway;
use App\Services\Values\ValueService;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StructuredTextResponse;

it('walks the public Értéktükör from the opening to the full picture', function () {
    fakeTurnstile();
    app(ValueService::class)->create(['name' => 'Egyenesség', 'meanings' => ['Kimondom.'], 'high_examples' => ['Mindig.'], 'low_examples' => ['Soha.']]);
    $answer = fn (array $data) => new StructuredTextResponse($data, json_encode($data), new TextUsage(10, 5), new Meta('anthropic', 'claude-test'));
    AiGateway::fake([
        $answer(['detections' => [
            ['speaker' => 'Beszélő 1', 'quote' => 'inkább most mondom, mint csütörtökön', 'free_name' => 'Egyenesség', 'reasoning' => 'Rögtön kimondja.', 'strength' => 5],
            ['speaker' => 'Beszélő 1', 'quote' => 'a végén nevettünk egyet', 'free_name' => 'Játékosság', 'reasoning' => 'Humor.', 'strength' => 3],
        ]]),
        $answer(['results' => [['detection' => 'D1', 'matches' => [['ref' => 'E1', 'confidence' => 0.9]]], ['detection' => 'D2', 'matches' => []]]]),
        $answer(['scores' => [['ref' => 'E1', 'state' => 'scored', 'score' => 5, 'reasoning' => 'Így tesz.', 'detections' => ['D1']]]]),
    ]);

    $page = visit('/');
    $page->assertSee('Belenézek a tükörbe')->assertNoAccessibilityIssues();

    $page->click('Belenézek a tükörbe');
    $page->assertPathIs('/ertektukor')->assertNoAccessibilityIssues();

    $page->click('Inkább leírom');
    $page->fill('text', 'A héten inkább most mondom, mint csütörtökön, hogy csúszunk. A végén nevettünk egyet.');
    $page->script("() => window.Livewire.all()[0].\$wire.set('turnstileToken', 'test-token')");
    $page->press('Kész · mutasd a tükröt');

    $page->assertSee('Az értékeid')
        ->assertSee('Egyenesség')
        ->assertSee('Játékosság')
        ->assertNoAccessibilityIssues();

    $page->press('A teljes kép');
    $page->assertSee('Kíváncsi vagy?')
        ->assertSee('Létrehozom a fiókot')
        ->assertNoAccessibilityIssues();
});
