<?php

use Illuminate\Support\Facades\Route;

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
