<?php

namespace App\Livewire\Concerns;

use App\Rules\Turnstile;

/**
 * A Livewire form behind Turnstile (NFR · 5): the widget hands its token to
 * "turnstileToken". Cloudflare accepts a token for one check only, so the
 * form's own fields are validated first, and after a check the widget is
 * asked for a new token, whatever came of it.
 */
trait ChecksTurnstile
{
    public string $turnstileToken = '';

    protected function checkTurnstile(): void
    {
        $spent = $this->turnstileToken !== '';

        try {
            $this->validate(['turnstileToken' => [new Turnstile]]);
        } finally {
            $this->turnstileToken = '';

            if ($spent) {
                $this->dispatch('turnstile-reset');
            }
        }
    }
}
