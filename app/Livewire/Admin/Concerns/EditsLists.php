<?php

namespace App\Livewire\Admin\Concerns;

/**
 * The list editor of the NFR (3): any number of items, one word or one or two
 * sentences each, ordered by drag and drop or the up and down buttons.
 * The first item matters most (03 · 3.2).
 */
trait EditsLists
{
    /**
     * Names of the array properties edited as lists.
     *
     * @return list<string>
     */
    abstract protected function editableLists(): array;

    public function addListItem(string $list): void
    {
        $this->ensureEditableList($list);

        $this->{$list}[] = '';
    }

    public function removeListItem(string $list, int $index): void
    {
        $this->ensureEditableList($list);

        $items = $this->{$list};
        unset($items[$index]);
        $this->{$list} = array_values($items);
        $this->resetValidation($list);
    }

    /**
     * Called by wire:sort with the moved item's key and its new position.
     */
    public function sortListItem(string $list, int|string $index, int $position): void
    {
        $this->ensureEditableList($list);

        $items = array_values($this->{$list});
        $index = (int) $index;

        if (! array_key_exists($index, $items)) {
            return;
        }

        $moved = array_splice($items, $index, 1);
        array_splice($items, max(0, min($position, count($items))), 0, $moved);
        $this->{$list} = $items;
        $this->resetValidation($list);
    }

    /**
     * Keyboard alternative to dragging (WCAG 2.2 · 2.5.7).
     */
    public function moveListItem(string $list, int $index, int $offset): void
    {
        $this->sortListItem($list, $index, $index + $offset);
    }

    /**
     * Trims the items and drops the empty ones, so validation messages point at
     * the same rows the user sees.
     */
    protected function cleanLists(): void
    {
        foreach ($this->editableLists() as $list) {
            $this->{$list} = array_values(array_filter(
                array_map(fn (mixed $item): string => trim((string) $item), $this->{$list}),
                fn (string $item): bool => $item !== '',
            ));
        }
    }

    /**
     * @return array<string, list<string>>
     */
    protected function listRules(string $list, bool $required = false, int $maxLength = 255): array
    {
        return [
            $list => $required ? ['array', 'min:1'] : ['array'],
            $list.'.*' => ['required', 'string', 'min:2', 'max:'.$maxLength, 'distinct'],
        ];
    }

    private function ensureEditableList(string $list): void
    {
        abort_unless(in_array($list, $this->editableLists(), true), 404);
    }
}
