<?php

namespace App\Livewire\Client;

use App\Models\MainDiagnosis;
use App\Models\Organization;
use App\Services\Capabilities\CapabilityOverview;
use App\Services\Report\ReportService;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Tárak → Képességtár (M3, NM-12): which capability is there and which is
 * missing, from the viewer's latest published report that says so. State,
 * confidence, how many traces, the meaning and one quote; no code, no step.
 */
class CapabilityLibrary extends Component
{
    public Organization $organization;

    public ?int $openId = null;

    public function mount(Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function toggle(int $capabilityId): void
    {
        $this->openId = $this->openId === $capabilityId ? null : $capabilityId;
    }

    public function render(): View
    {
        [$main, $overview] = self::latestFor($this->organization) ?? [null, ['rows' => []]];

        return view('livewire.client.capability-library', [
            'main' => $main,
            'rows' => $overview['rows'],
        ])->layout('layouts::client', [
            'title' => __('capabilities.library.title'),
            'subtitle' => __('capabilities.library.question'),
            'note' => $main === null ? null : trans_choice('capabilities.library.note', count($overview['rows']), [
                'count' => count($overview['rows']),
                // "a 2026.10.26-i diagnózis": no dot before the suffix.
                'date' => $main->published_at?->format('Y.m.d'),
            ]),
            'color' => 'cream-line',
            'symbol' => 'fire',
            'label' => __('capabilities.library.title'),
            'back' => route('client.home', $this->organization),
            'backLabel' => __('client.menu.home'),
        ])->title(__('capabilities.library.title'));
    }

    /**
     * The viewer's latest published report with capabilities to show, and what it shows.
     *
     * @return array{0: MainDiagnosis, 1: array{rows: list<array<string, mixed>>, strengths: list<array<string, mixed>>, gaps: list<array<string, mixed>>}}|null
     */
    public static function latestFor(Organization $organization): ?array
    {
        $user = auth('web')->user();

        if ($user === null) {
            return null;
        }

        $overviews = app(CapabilityOverview::class);

        foreach (app(ReportService::class)->reportsFor($user, $organization) as $main) {
            $overview = $overviews->for($main, $user);

            if ($overview['rows'] !== []) {
                return [$main, $overview];
            }
        }

        return null;
    }
}
