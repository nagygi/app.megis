<?php

namespace App\Livewire\Admin\System;

use Illuminate\View\View;
use Livewire\Component;

/**
 * Rendszer (01 · 1.4). The AI pages (16) are added here later.
 */
class Overview extends Component
{
    public function render(): View
    {
        return view('livewire.admin.system.overview')
            ->layout('layouts::admin')
            ->title(__('admin.system.title'));
    }
}
