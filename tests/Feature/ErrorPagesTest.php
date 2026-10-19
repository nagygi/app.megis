<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Livewire\Livewire;

it('renders the branded not found page in Hungarian', function () {
    $response = $this->get('/nincs/ilyen/oldal');

    $response->assertNotFound()
        ->assertSee('Nincs ilyen oldal')
        ->assertSee('Vissza a kezdőlapra');
});

it('renders the branded forbidden page in Hungarian', function () {
    Route::get('/_test/forbidden', fn () => abort(403));

    $response = $this->get('/_test/forbidden');

    $response->assertForbidden()->assertSee('Nincs jogosultságod');
});

it('renders the branded server error page without debug details', function () {
    config(['app.debug' => false]);
    Route::get('/_test/server-error', fn () => throw new RuntimeException('internal detail'));

    $response = $this->get('/_test/server-error');

    $response->assertInternalServerError()
        ->assertSee('Valami elromlott')
        ->assertDontSee('internal detail');
});

it('renders the branded page for a client error without its own page', function () {
    // The Livewire update endpoint takes POST only; opening it in a browser is a 405.
    config(['app.debug' => false]);

    $response = $this->get(Livewire::getUpdateUri());

    $response->assertMethodNotAllowed()
        ->assertSee('Ez a kérés nem teljesíthető')
        ->assertSee('Vissza a kezdőlapra');
});

it('renders the maintenance page while a deploy rebuilds the assets', function () {
    // update.sh runs `npm run build` while the site is down, and the build
    // directory is empty for a while.
    config(['app.debug' => false, 'megis.alerts.recipients' => ['dev@megis.hu']]);
    Mail::fake();
    $this->withVite();
    Vite::useBuildDirectory('_no-such-build');
    Vite::useHotFile(storage_path('framework/_no-such-hot-file'));
    Route::get('/_test/maintenance', fn () => abort(503));

    $response = $this->get('/_test/maintenance');

    $response->assertServiceUnavailable()->assertSee('Karbantartás');
    Mail::assertNothingSent();
});
