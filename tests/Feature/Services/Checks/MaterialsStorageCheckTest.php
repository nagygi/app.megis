<?php

use App\Enums\CheckStatus;
use App\Services\Checks\MaterialsStorageCheck;
use Illuminate\Support\Facades\Storage;

it('creates the probe file on first run and reads it back', function () {
    Storage::fake('materials');

    $result = app(MaterialsStorageCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Ok);
    Storage::disk('materials')->assertExists(MaterialsStorageCheck::PROBE_PATH);
});

it('reports an error when the probe file reads back different content', function () {
    Storage::fake('materials');
    Storage::disk('materials')->put(MaterialsStorageCheck::PROBE_PATH, 'corrupted');

    $result = app(MaterialsStorageCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Error);
});
