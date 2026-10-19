<?php

/**
 * Livewire's $wire keeps upload(), uploadMultiple(), removeUpload() and
 * cancelUpload() for its own file uploads, so wire:submit="upload" never
 * reaches a component method with the same name.
 */
arch('Livewire components do not name an action after a $wire upload helper')
    ->expect('App\Livewire')
    ->not->toHaveMethods(['upload', 'uploadMultiple', 'removeUpload', 'cancelUpload']);
