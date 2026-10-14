<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Str;

/**
 * Lower case, accent-free, hyphenated slugs with a numeric suffix when taken.
 */
class UniqueSlug
{
    /**
     * @param  Closure(string): bool  $isAvailable
     */
    public static function make(string $name, string $fallback, Closure $isAvailable): string
    {
        $base = Str::slug($name);
        $base = $base === '' ? $fallback : $base;

        $slug = $base;
        $suffix = 2;

        while (! $isAvailable($slug)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
