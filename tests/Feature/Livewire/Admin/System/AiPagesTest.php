<?php

use App\Livewire\Admin\System\AiConfiguration;
use App\Livewire\Admin\System\AiPrompts;
use App\Livewire\Admin\System\AiUsage;
use App\Models\AiCall;
use App\Models\AiModelPrice;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('sums the calls of the period and breaks them down', function () {
    actingAsAdmin(User::factory()->superadmin()->create());
    $organization = Organization::factory()->create(['name' => 'Acme Kft.']);
    AiCall::factory()->create(['organization_id' => $organization->id, 'organization_name' => 'Acme Kft.', 'input_tokens' => 1000, 'output_tokens' => 500, 'cost_usd' => 0.01]);
    AiCall::factory()->create(['task' => 'calendar_naming', 'input_tokens' => 100, 'output_tokens' => 50, 'cost_usd' => null]);
    AiCall::factory()->create(['created_at' => now()->subDays(40)]);

    Livewire::test(AiUsage::class)
        ->assertSee('1 650')
        ->assertSee('$0,0100')
        ->assertSee('1 hívás modelljének nincs egységára')
        ->assertSee('Naptár-jelöltek elnevezése')
        ->assertSee('Acme Kft.')
        ->set('filters.task', 'calendar_naming')
        ->assertSee('150');
});

it('opens a call with its prompt version and error', function () {
    actingAsAdmin(User::factory()->superadmin()->create());
    $call = AiCall::factory()->failed('RuntimeException: Provider timeout')->create();

    Livewire::test(AiUsage::class)
        ->call('toggleCall', $call->id)
        ->assertSee('ertektukor-detektalas · v1')
        ->assertSee('RuntimeException: Provider timeout');
});

it('exports the filtered calls as CSV', function () {
    actingAsAdmin(User::factory()->superadmin()->create());
    AiCall::factory()->create(['task' => 'calendar_naming']);
    AiCall::factory()->create(['task' => 'mirror_scoring']);

    $response = $this->get(route('admin.system.ai.export', ['idoszak' => '7d', 'szuro' => ['task' => 'calendar_naming']]));

    $response->assertOk()->assertDownload();
    $csv = $response->streamedContent();
    expect($csv)->toContain('calendar_naming')->not->toContain('mirror_scoring');
});

it('shows each task with its model, prompt and price status', function () {
    actingAsAdmin(User::factory()->superadmin()->create());
    config(['ai.prices' => ['claude-haiku-5-5' => ['input' => 1.0, 'output' => 5.0]]]);

    Livewire::test(AiConfiguration::class)
        ->assertSee('Értéktükör: szabad detektálás')
        ->assertSee('claude-sonnet-5-5')
        ->assertSee('még nincs prompt')
        ->assertSee('nincs ár')
        ->assertSee('bemenet $1,00 · kimenet $5,00');
});

it('shows the synced price of a model whose name has dots', function () {
    actingAsAdmin(User::factory()->superadmin()->create());
    config(['ai.tasks.calendar_naming.model' => 'gemini-3.5-flash-lite']);
    AiModelPrice::factory()->create([
        'model' => 'gemini-3.5-flash-lite',
        'provider' => 'gemini',
        'input' => 0.3,
        'output' => 2.5,
        'synced_at' => '2026-10-20 03:45:00',
    ]);

    Livewire::test(AiConfiguration::class)
        ->assertSee('bemenet $0,30 · kimenet $2,50')
        ->assertSee('Utolsó frissítés: 2026.10.20. 03:45.');
});

it('shows the versions of a prompt and the text of one', function () {
    actingAsAdmin(User::factory()->superadmin()->create());
    config([
        'ai.prompts_path' => base_path('tests/Fixtures/prompts'),
        'ai.tasks.mirror_detection.prompt' => 'teszt-feladat',
        'ai.tasks.mirror_detection.prompt_version' => 2,
    ]);

    Livewire::test(AiPrompts::class)
        ->assertSee('v2 · aktív')
        ->call('open', 'teszt-feladat', 1)
        ->assertSee('Régi változat. Válaszolj röviden.');
});

it('keeps the AI pages for superadmins', function (string $route) {
    actingAsAdmin();

    $this->get(route($route))->assertForbidden();
})->with(['admin.system.ai.usage', 'admin.system.ai.configuration', 'admin.system.ai.prompts', 'admin.system.ai.export']);
