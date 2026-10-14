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
}
