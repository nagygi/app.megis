<?php

namespace App\Livewire\Admin\Mirrors;

use App\Models\MirrorSession;
use App\Models\User;
use App\Services\Mirror\MirrorService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * One mirror on the admin side (Értékmérések): whose it is, the values with
 * their quotes as on ET3, the meanings of ET4 and the curiosity of ET5.
 */
class Show extends Component
{
    public MirrorSession $mirror;

    public function mount(MirrorSession $mirror): void
    {
        Gate::authorize('view', $mirror);

        $this->mirror = $mirror;
    }

    public function render(MirrorService $mirrors): View
    {
        $this->mirror->refresh()->load(['user', 'organization', 'material', 'diagnosis', 'insights']);

        return view('livewire.admin.mirrors.show', [
            'groups' => $mirrors->groups($this->mirror),
            'insights' => $this->mirror->insights->keyBy('group_key'),
            // Before the confirmation the account waits under the e-mail, not on the mirror.
            'account' => $this->mirror->user ?? ($this->mirror->email === null ? null : User::query()->where('email', $this->mirror->email)->first()),
        ])->layout('layouts::admin')->title(__('admin.mirrors.show_title', ['date' => $this->mirror->created_at->format('Y.m.d. H:i')]));
    }
}
