<?php

namespace App\Livewire\Admin\SupportQuestions;

use App\Models\SupportQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Diagnózisok → Támogató kérdések (06 · 6.7): the questions shown after a
 * silence while recording, in the order set here; inactive ones are not shown.
 */
class Index extends Component
{
    public ?int $editingId = null;

    public bool $showForm = false;

    public string $text = '';

    public bool $is_active = true;

    public function mount(): void
    {
        Gate::authorize('viewAny', SupportQuestion::class);
    }

    public function create(): void
    {
        Gate::authorize('create', SupportQuestion::class);

        $this->reset(['editingId', 'text']);
        $this->is_active = true;
        $this->showForm = true;
        $this->resetValidation();
    }

    public function edit(int $questionId): void
    {
        $question = SupportQuestion::query()->findOrFail($questionId);
        Gate::authorize('update', $question);

        $this->editingId = $question->id;
        $this->text = $question->text;
        $this->is_active = $question->is_active;
        $this->showForm = true;
        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->reset(['editingId', 'text', 'showForm']);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'text' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ], attributes: ['text' => __('admin.support_questions.text')]);

        if ($this->editingId === null) {
            Gate::authorize('create', SupportQuestion::class);
            SupportQuestion::create([...$validated, 'position' => (int) SupportQuestion::query()->max('position') + 1]);
        } else {
            $question = SupportQuestion::query()->findOrFail($this->editingId);
            Gate::authorize('update', $question);
            $question->update($validated);
        }

        $this->cancel();
        session()->flash('status', __('admin.common.saved'));
    }

    public function toggleActive(int $questionId): void
    {
        $question = SupportQuestion::query()->findOrFail($questionId);
        Gate::authorize('update', $question);

        $question->update(['is_active' => ! $question->is_active]);
    }

    /**
     * Called by wire:sort with the moved question's id and its new position.
     */
    public function sortQuestion(int|string $questionId, int $position): void
    {
        Gate::authorize('create', SupportQuestion::class);

        $ids = SupportQuestion::query()->orderBy('position')->orderBy('id')->pluck('id')->all();
        $index = array_search((int) $questionId, $ids, true);

        if ($index === false) {
            return;
        }

        array_splice($ids, $index, 1);
        array_splice($ids, max(0, min($position, count($ids))), 0, [(int) $questionId]);

        DB::transaction(function () use ($ids): void {
            foreach ($ids as $order => $id) {
                SupportQuestion::query()->whereKey($id)->update(['position' => $order]);
            }
        });
    }

    /**
     * Keyboard alternative to dragging (WCAG 2.2 · 2.5.7).
     */
    public function move(int $questionId, int $offset): void
    {
        $ids = SupportQuestion::query()->orderBy('position')->orderBy('id')->pluck('id')->all();
        $index = array_search($questionId, $ids, true);

        if ($index !== false) {
            $this->sortQuestion($questionId, $index + $offset);
        }
    }

    public function render(): View
    {
        return view('livewire.admin.support-questions.index', [
            'questions' => SupportQuestion::query()->orderBy('position')->orderBy('id')->get(),
        ])->layout('layouts::admin')->title(__('admin.support_questions.title'));
    }
}
