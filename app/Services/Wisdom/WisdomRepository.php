<?php

namespace App\Services\Wisdom;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * The Bölcsességtár's topics (32 · 32.4): markdown files per language in
 * resources/content/{locale}/bolcsessegtar, changed with a deploy. A topic
 * has a front matter (title, sentence, chapter, symbol), an intro and items:
 * "## {symbol} | {name}", the first paragraph the sentence, the rest the description.
 */
class WisdomRepository
{
    /**
     * The order of the topics (32 · 32.1); the areas come from Beállítások → Területek.
     */
    public const TOPICS = ['folyamat', 'kuldetesfa', 'teruletek', 'szazalek-es-pontok'];

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return array_map(fn (string $slug) => $this->find($slug), self::TOPICS);
    }

    /**
     * @return array{slug: string, title: string, sentence: string, chapter: ?string, symbol: ?string, intro: string, items: list<array{symbol: string, name: string, sentence: string, description: string}>}|null
     */
    public function find(string $slug): ?array
    {
        if (! in_array($slug, self::TOPICS, true)) {
            return null;
        }

        $path = resource_path('content/'.app()->getLocale().'/bolcsessegtar/'.$slug.'.md');

        if (! File::exists($path)) {
            $path = resource_path('content/hu/bolcsessegtar/'.$slug.'.md');
        }

        return $this->parse($slug, File::get($path));
    }

    /**
     * @return array<string, mixed>
     */
    private function parse(string $slug, string $content): array
    {
        $content = str_replace("\r\n", "\n", $content);
        $meta = [];

        if (preg_match('/\A---\n(.*?)\n---\n/s', $content, $match)) {
            foreach (explode("\n", $match[1]) as $line) {
                [$key, $value] = array_pad(explode(':', $line, 2), 2, '');
                $meta[trim($key)] = trim($value);
            }

            $content = substr($content, strlen($match[0]));
        }

        $parts = preg_split('/^## /m', $content);
        $intro = trim((string) array_shift($parts));

        $items = array_map(function (string $part): array {
            [$heading, $body] = array_pad(explode("\n", $part, 2), 2, '');
            [$symbol, $name] = array_pad(array_map('trim', explode('|', $heading, 2)), 2, '');
            $paragraphs = preg_split("/\n\s*\n/", trim($body), 2);

            return [
                'symbol' => $symbol,
                'name' => $name,
                'sentence' => trim($paragraphs[0] ?? ''),
                'description' => trim($paragraphs[1] ?? ''),
            ];
        }, $parts);

        return [
            'slug' => $slug,
            'title' => $meta['title'] ?? Str::headline($slug),
            'sentence' => $meta['sentence'] ?? '',
            'chapter' => filled($meta['chapter'] ?? null) ? $meta['chapter'] : null,
            // The topic's symbol on BT1 (prototype: spiral, fruit, mycelium, growth rings).
            'symbol' => filled($meta['symbol'] ?? null) ? $meta['symbol'] : null,
            'intro' => $intro,
            'items' => array_values($items),
        ];
    }
}
