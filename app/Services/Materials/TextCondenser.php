<?php

namespace App\Services\Materials;

use App\Ai\AiContext;
use App\Ai\Tasks\CondenseTextTask;
use App\Services\Ai\AiGateway;
use RuntimeException;

/**
 * A text too long for its field, condensed by the AI while the user waits:
 * a spoken answer or an uploaded document (Leírom · Elmondom · Feltöltöm).
 */
class TextCondenser
{
    public function __construct(private AiGateway $ai) {}

    /**
     * Never longer than the room; the AI aims a little lower, the rest is cut.
     */
    public function condense(string $text, int $maxCharacters, AiContext $context = new AiContext): string
    {
        $input = mb_substr(trim($text), 0, (int) config('megis.materials.text_max_characters'));

        $result = $this->ai->run(new CondenseTextTask($input, max(1, (int) floor($maxCharacters * 0.9))), $context);
        $condensed = trim((string) ($result->structured['text'] ?? ''));

        if ($condensed === '') {
            throw new RuntimeException('The condensed text is empty.');
        }

        return mb_substr($condensed, 0, $maxCharacters);
    }
}
