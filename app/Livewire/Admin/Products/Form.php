<?php

namespace App\Livewire\Admin\Products;

use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * New and edited product: name (unique) and description (02 · 2.1).
 */
class Form extends Component
{
    public ?Product $product = null;

    public string $name = '';

    public string $description = '';

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            Gate::authorize('update', $product);
            $this->product = $product;
            $this->name = $product->name;
            $this->description = (string) $product->description;

            return;
        }

        Gate::authorize('create', Product::class);
    }

    public function save(): void
    {
        $this->product === null
            ? Gate::authorize('create', Product::class)
            : Gate::authorize('update', $this->product);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('products', 'name')->ignore($this->product?->id)],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $attributes = [
            'name' => trim($validated['name']),
            'description' => filled($validated['description']) ? $validated['description'] : null,
        ];

        $this->product === null ? Product::create($attributes) : $this->product->update($attributes);

        session()->flash('status', __('admin.common.saved'));
        $this->redirectRoute('admin.products.index', navigate: false);
    }

    public function render(): View
    {
        $title = $this->product ? __('admin.products.edit_title') : __('admin.products.create_title');

        return view('livewire.admin.products.form', ['title' => $title])
            ->layout('layouts::admin')
            ->title($title);
    }
}
