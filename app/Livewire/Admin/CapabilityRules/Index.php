<?php

namespace App\Livewire\Admin\CapabilityRules;

use App\Enums\CapabilityRuleArea;
use App\Enums\CapabilityRuleStatus;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Models\CapabilityRule;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Képességtár → Szabálykönyv: the rules of the mégis, loaded with the tár,
 * read only. The measurement cites their codes.
 */
class Index extends Component
{
    use WithDataTable;

    public function mount(): void
    {
        Gate::authorize('viewAny', CapabilityRule::class);
    }

    public function render(): View
    {
        $query = CapabilityRule::query();

        if ($term = $this->searchTerm()) {
            $query->where(fn ($rules) => $rules->whereLike('code', $term)->orWhereLike('text', $term));
        }

        foreach (['area' => CapabilityRuleArea::class, 'status' => CapabilityRuleStatus::class] as $column => $enum) {
            if (($value = $enum::tryFrom((string) $this->filter($column))) !== null) {
                $query->where($column, $value);
            }
        }

        $options = fn (string $enum): array => collect($enum::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all();

        return view('livewire.admin.capability-rules.index', [
            'rules' => $this->applySorting($query)->paginate(100),
            'areas' => $options(CapabilityRuleArea::class),
            'statuses' => $options(CapabilityRuleStatus::class),
        ])->layout('layouts::admin')->title(__('admin.capability_rules.title'));
    }

    protected function sortableColumns(): array
    {
        return [
            'code' => 'code',
            'area' => 'area',
            'status' => 'status',
        ];
    }

    protected function defaultSort(): array
    {
        return ['code', 'asc'];
    }
}
