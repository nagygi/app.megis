<?php

namespace App\Livewire\Admin\Mirrors;

use App\Enums\MaterialContext;
use App\Enums\MirrorStatus;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Models\MirrorSession;
use App\Services\Mirror\MirrorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Értékmérések › Értéktükrök: every mirror, the public and the own-menu ones,
 * and those whose e-mail is not confirmed yet while they live (24 hours, 06 · 6.1).
 */
class Index extends Component
{
    use WithDataTable;

    public function mount(): void
    {
        Gate::authorize('viewAny', MirrorSession::class);
    }

    public function delete(int $mirrorId, MirrorService $mirrors): void
    {
        $mirror = MirrorSession::query()->findOrFail($mirrorId);
        Gate::authorize('delete', $mirror);

        $mirrors->delete($mirror);
        session()->flash('status', __('admin.common.deleted'));
    }

    public function render(): View
    {
        $query = MirrorSession::query()->with(['user', 'organization', 'material']);

        if ($term = $this->searchTerm()) {
            $query->where(fn (Builder $search) => $search
                ->whereLike('email', $term)
                ->orWhereHas('user', fn (Builder $user) => $user
                    ->whereLike('email', $term)
                    ->orWhereLike('first_name', $term)
                    ->orWhereLike('last_name', $term)));
        }

        match ($this->filter('account')) {
            'claimed' => $query->whereNotNull('claimed_at'),
            'awaiting_confirmation' => $query->whereNull('claimed_at')->whereNotNull('confirmation_sent_at'),
            'awaiting_login' => $query->whereNull('claimed_at')->whereNotNull('email')->whereNull('confirmation_sent_at'),
            default => null,
        };

        match ($this->filter('status')) {
            'processing' => $query->where('status', MirrorStatus::Processing),
            'failed' => $query->where('status', MirrorStatus::Failed),
            'ready' => $query->whereIn('status', array_filter(MirrorStatus::cases(), fn (MirrorStatus $status) => $status->isDone())),
            default => null,
        };

        if (($context = MaterialContext::tryFrom((string) $this->filter('source'))) !== null) {
            $query->whereHas('material', fn (Builder $material) => $material->where('context', $context));
        }

        return view('livewire.admin.mirrors.index', [
            'mirrors' => $this->applySorting($query)->paginate(50),
            'accountOptions' => collect(['awaiting_confirmation', 'awaiting_login', 'claimed'])
                ->mapWithKeys(fn (string $state) => [$state => __('admin.mirrors.account_states.'.$state)])->all(),
            'statusOptions' => [
                'processing' => MirrorStatus::Processing->label(),
                'ready' => MirrorStatus::Ready->label(),
                'failed' => MirrorStatus::Failed->label(),
            ],
            'sourceOptions' => collect([MaterialContext::PublicMirror, MaterialContext::Mirror])
                ->mapWithKeys(fn (MaterialContext $context) => [$context->value => $context->label()])->all(),
        ])->layout('layouts::admin')->title(__('admin.mirrors.title'));
    }

    protected function sortableColumns(): array
    {
        return [
            'created' => 'created_at',
            'expires' => 'expires_at',
        ];
    }

    protected function defaultSort(): array
    {
        return ['created', 'desc'];
    }
}
