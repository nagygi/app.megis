<?php

use App\Support\ReservedSlugs;
use Illuminate\Support\Facades\Route;

it('reserves the first segment of every registered route', function () {
    Route::get('/belepes/kod', fn () => 'ok');

    $slugs = app(ReservedSlugs::class)->all();

    expect($slugs)->toContain('health', 'robots.txt', 'sitemap.xml', 'belepes');
});

it('reserves the configured system words', function () {
    $slugs = app(ReservedSlugs::class)->all();

    expect($slugs)->toContain('api', 'login', 'storage');
});

it('does not reserve route parameters', function () {
    Route::get('/{organization}/kezdolap', fn () => 'ok');

    $slugs = app(ReservedSlugs::class)->all();

    expect($slugs)->not->toContain('{organization}');
});

it('matches reserved slugs case-insensitively', function () {
    $reservedSlugs = app(ReservedSlugs::class);

    expect($reservedSlugs->contains('HEALTH'))->toBeTrue()
        ->and($reservedSlugs->contains('acme-kft'))->toBeFalse();
});
