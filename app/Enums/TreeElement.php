<?php

namespace App\Enums;

/**
 * The four elements of the brand base (08 · 8.2), the fixed rows of a
 * diagnosis (04 · 4.4).
 */
enum TreeElement: string
{
    case Root = 'root';
    case Trunk = 'trunk';
    case Crown = 'crown';
    case Shadow = 'shadow';

    public function label(): string
    {
        return __('enums.tree_element.'.$this->value);
    }

    /**
     * The living-systems symbol of the element (_ds symbols).
     */
    public function symbol(): string
    {
        return match ($this) {
            self::Root => 'root',
            self::Trunk => 'trunk',
            self::Crown => 'fruit',
            self::Shadow => 'shade',
        };
    }

    /**
     * The organization value set field of the element; the root is the list of values (08 · 8.2).
     */
    public function valueSetField(): ?string
    {
        return match ($this) {
            self::Root => null,
            self::Trunk => 'purpose',
            self::Crown => 'value_proposition',
            self::Shadow => 'impact',
        };
    }
}
