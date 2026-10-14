<?php

namespace App\Enums;

/**
 * A diagnosis row is a value or an element of the brand base (04 · 4.4).
 */
enum DiagnosisRowKind: string
{
    case Value = 'value';
    case TreeElement = 'tree_element';
}
