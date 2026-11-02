<?php

namespace App\Support;

use App\Models\Diagnosis;
use App\Models\Material;
use App\Models\Organization;
use App\Models\Process;
use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;

/**
 * The breadcrumbs of an admin page (AdminLocation): the pages above it up to
 * its menu item. A diagnosis sits under its organization's Diagnózisok tab,
 * a subdiagnosis under its main diagnosis' Részdiagnózisok; every other page
 * under the list of its menu item (AdminNavigation).
 *
 * Only the layout asks, on the full page request: the route's parameters are
 * models by then, and a Livewire update does not render the layout again.
 */
class AdminBreadcrumbs
{
    /**
     * The tabs of an organization (02 · 2.3), in the layout's header.
     */
    public const ORGANIZATION_TABS = [
        'admin.organizations.edit',
        'admin.organizations.members',
        'admin.organizations.teams',
        'admin.organizations.values',
        'admin.organizations.processes',
        'admin.organizations.diagnoses',
    ];

    /**
     * The tabs of a main diagnosis (x-admin.main-diagnosis-tabs).
     */
    private const MAIN_DIAGNOSIS_TABS = [
        'admin.diagnoses.show',
        'admin.main-diagnoses.edit',
        'admin.main-diagnoses.run',
        'admin.main-diagnoses.reading',
        'admin.main-diagnoses.publish',
        'admin.main-diagnoses.subdiagnoses',
        'admin.diagnoses.capabilities',
    ];

    /**
     * Library pages whose tabs hang under their first one: route => [parameter, home route].
     */
    private const LIBRARY_HOMES = [
        'admin.values.edit' => ['value', 'admin.values.edit'],
        'admin.values.versions' => ['value', 'admin.values.edit'],
        'admin.capabilities.edit' => ['capability', 'admin.capabilities.edit'],
        'admin.capabilities.patterns' => ['capability', 'admin.capabilities.edit'],
        'admin.capabilities.versions' => ['capability', 'admin.capabilities.edit'],
        'admin.patterns.show' => ['pattern', 'admin.patterns.show'],
        'admin.patterns.versions' => ['pattern', 'admin.patterns.show'],
    ];

    public function __construct(private readonly AdminNavigation $navigation) {}

    public function current(): AdminLocation
    {
        $route = request()->route();

        if ($route?->getName() === null || Livewire::isLivewireRequest()) {
            return new AdminLocation;
        }

        return $this->resolve($route->getName(), $route->parameters(), request()->query());
    }

    /**
     * @param  array<string, mixed>  $parameters  the route's parameters, models or keys
     * @param  array<string, mixed>  $query
     */
    public function resolve(string $routeName, array $parameters = [], array $query = []): AdminLocation
    {
        $organization = $this->model(Organization::class, $parameters['organization'] ?? null);
        $diagnosis = $this->model(Diagnosis::class, $parameters['diagnosis'] ?? null);

        return match (true) {
            $diagnosis !== null => $this->diagnosisPage($routeName, $diagnosis),
            $routeName === 'admin.materials.show' => $this->materialPage($this->model(Material::class, $parameters['material'] ?? null)),
            $organization !== null => $this->organizationPage($routeName, $organization, $this->model(Process::class, $parameters['process'] ?? null)),
            $routeName === 'admin.main-diagnoses.create' && filled($query['szervezet'] ?? null) => $this->startPage($this->model(Organization::class, $query['szervezet']))
                ?? $this->listPage($routeName),
            default => $this->listPage($routeName, $parameters),
        };
    }

    private function organizationPage(string $routeName, Organization $organization, ?Process $process): AdminLocation
    {
        $trail = [$this->organizations()];

        if (! in_array($routeName, self::ORGANIZATION_TABS, true)) {
            $tab = $process !== null ? 'processes' : 'members';
            $trail[] = ['label' => $organization->name, 'url' => route('admin.organizations.edit', $organization)];
            $trail[] = ['label' => __('admin.organizations.tabs.'.$tab), 'url' => route('admin.organizations.'.$tab, $organization)];
        }

        return new AdminLocation(
            trail: $trail,
            organization: $organization,
            home: in_array($routeName, self::ORGANIZATION_TABS, true) ? route('admin.organizations.edit', $organization) : null,
            section: 'admin.organizations.index',
        );
    }

    /**
     * A1 opened from an organization's Diagnózisok tab.
     */
    private function startPage(?Organization $organization): ?AdminLocation
    {
        return $organization === null ? null : new AdminLocation(
            trail: $this->diagnosesOf($organization),
            organization: $organization,
            section: 'admin.organizations.index',
        );
    }

    /**
     * The tabs of a main diagnosis and the page of any other diagnosis sit
     * under the diagnosis' list; its other pages (Adatok, Képességek,
     * Előnézet) under the diagnosis itself.
     */
    private function diagnosisPage(string $routeName, Diagnosis $diagnosis): AdminLocation
    {
        $isMain = $diagnosis->main()->exists();
        $isOwnPage = $routeName === 'admin.diagnoses.show' || ($isMain && in_array($routeName, self::MAIN_DIAGNOSIS_TABS, true));

        return new AdminLocation(
            trail: $isOwnPage ? $this->above($diagnosis) : [...$this->above($diagnosis), $this->diagnosisCrumb($diagnosis)],
            organization: $diagnosis->organization,
            home: $isOwnPage && $isMain ? route('admin.diagnoses.show', $diagnosis) : null,
            section: 'admin.organizations.index',
        );
    }

    /**
     * An uploaded material sits under what it was uploaded to.
     */
    private function materialPage(?Material $material): AdminLocation
    {
        $process = $material?->occasion?->process;

        return match (true) {
            $material?->parentDiagnosis !== null => new AdminLocation(
                trail: [...$this->above($material->parentDiagnosis), $this->diagnosisCrumb($material->parentDiagnosis)],
                organization: $material->parentDiagnosis->organization,
                section: 'admin.organizations.index',
            ),
            $process !== null => new AdminLocation(
                trail: [
                    $this->organizations(),
                    ['label' => $process->organization->name, 'url' => route('admin.organizations.edit', $process->organization)],
                    ['label' => __('admin.organizations.tabs.processes'), 'url' => route('admin.organizations.processes', $process->organization)],
                    ['label' => $process->name, 'url' => route('admin.organizations.process-occasions', [$process->organization, $process])],
                ],
                organization: $process->organization,
                section: 'admin.organizations.index',
            ),
            $material?->organization !== null => new AdminLocation(
                trail: [$this->organizations(), ['label' => $material->organization->name, 'url' => route('admin.organizations.edit', $material->organization)]],
                organization: $material->organization,
                section: 'admin.organizations.index',
            ),
            // Nothing to hang under: the Beérkező it arrived to.
            default => new AdminLocation(
                trail: [['label' => __('admin.nav.inbox'), 'url' => route('admin.inbox.index')]],
                section: 'admin.inbox.index',
            ),
        };
    }

    /**
     * A page under its menu item: under the item's list, or nothing above the list itself.
     *
     * @param  array<string, mixed>  $parameters
     */
    private function listPage(string $routeName, array $parameters = []): AdminLocation
    {
        $item = $this->navigation->itemFor($routeName, auth('admin')->user());

        if ($item === null) {
            return new AdminLocation;
        }

        [$parameter, $homeRoute] = self::LIBRARY_HOMES[$routeName] ?? [null, null];

        return new AdminLocation(
            trail: $item['route'] === $routeName ? [] : [['label' => $item['label'], 'url' => route($item['route'])]],
            home: $homeRoute !== null && isset($parameters[$parameter]) ? route($homeRoute, $parameters[$parameter]) : null,
            section: $item['route'],
        );
    }

    /**
     * The pages above a diagnosis' own page.
     *
     * @return list<array{label: string, url: string}>
     */
    private function above(Diagnosis $diagnosis): array
    {
        $parent = $diagnosis->parent;

        if ($parent === null) {
            return $this->diagnosesOf($diagnosis->organization);
        }

        $trail = [...$this->above($parent), $this->diagnosisCrumb($parent)];

        if ($parent->main()->exists()) {
            $trail[] = ['label' => __('admin.main_diagnoses.tabs.subdiagnoses'), 'url' => route('admin.main-diagnoses.subdiagnoses', $parent)];
        }

        return $trail;
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    private function diagnosesOf(Organization $organization): array
    {
        return [
            $this->organizations(),
            ['label' => $organization->name, 'url' => route('admin.organizations.edit', $organization)],
            ['label' => __('admin.organizations.tabs.diagnoses'), 'url' => route('admin.organizations.diagnoses', $organization)],
        ];
    }

    /**
     * @return array{label: string, url: string}
     */
    private function diagnosisCrumb(Diagnosis $diagnosis): array
    {
        return ['label' => $diagnosis->name, 'url' => route('admin.diagnoses.show', $diagnosis)];
    }

    /**
     * @return array{label: string, url: string}
     */
    private function organizations(): array
    {
        return ['label' => __('admin.nav.organizations'), 'url' => route('admin.organizations.index')];
    }

    /**
     * The route parameter as a model: bound already on a page request, a key otherwise.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $class
     * @return TModel|null
     */
    private function model(string $class, mixed $value): ?Model
    {
        if ($value === null || $value instanceof $class) {
            return $value;
        }

        return $class::query()->where((new $class)->getRouteKeyName(), $value)->first();
    }
}
