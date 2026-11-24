<?php

namespace App\Support;

/**
 * Reads the "components" of a Livewire update request: the actions it
 * called and the page its components live on.
 */
final class LivewireUpdate
{
    /**
     * Livewire actions that change nothing, called on a timer (14 · 14.6).
     */
    private const POLLS = ['pollDictations'];

    /**
     * The called actions as "component::method". Livewire's own calls
     * ($refresh, $set, _startUpload…) and the polls are no actions.
     *
     * @param  array<array-key, mixed>  $components
     * @return list<string>
     */
    public static function actions(array $components): array
    {
        return collect($components)
            ->filter(fn ($component) => is_array($component))
            ->flatMap(function (array $component): array {
                $name = self::snapshot($component)['memo']['name'] ?? 'livewire';

                return collect($component['calls'] ?? [])
                    ->pluck('method')
                    ->filter(fn ($method) => is_string($method)
                        && ! str_starts_with($method, '$')
                        && ! str_starts_with($method, '_')
                        && ! in_array($method, self::POLLS, true))
                    ->map(fn (string $method): string => $name.'::'.$method)
                    ->all();
            })
            ->values()
            ->all();
    }

    /**
     * The path of the page the components were loaded on, without the
     * leading slash, as Livewire keeps it.
     *
     * @param  array<array-key, mixed>  $components
     */
    public static function pagePath(array $components): string
    {
        $component = collect($components)->first(fn ($component) => is_array($component));
        $path = is_array($component) ? (self::snapshot($component)['memo']['path'] ?? '') : '';

        return is_string($path) ? ltrim($path, '/') : '';
    }

    /**
     * @param  array<array-key, mixed>  $component
     * @return array<array-key, mixed>
     */
    private static function snapshot(array $component): array
    {
        $snapshot = is_string($component['snapshot'] ?? null) ? json_decode($component['snapshot'], true) : null;

        return is_array($snapshot) ? $snapshot : [];
    }
}
