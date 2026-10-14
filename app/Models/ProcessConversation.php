<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The conversation instead of the calendar (10): four answers and the chips
 * extracted after each one. It can be continued later (10 · 10.3).
 */
#[Fillable(['organization_id', 'user_id', 'main_diagnosis_id', 'answers', 'extracts', 'extracted_answers', 'completed_at'])]
class ProcessConversation extends Model
{
    public const QUESTIONS = [1, 2, 3, 4];

    // The fourth answer names the breakpoint candidate (10 · 10.1).
    public const BREAKPOINT_QUESTION = 4;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'extracts' => 'array',
            'extracted_answers' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answer(int $question): string
    {
        return (string) ($this->answers[$question] ?? '');
    }

    /**
     * @return list<array{name: string, rhythm: ?string, headcount: ?int, unclear: bool, breakpoint: bool}>
     */
    public function chips(int $question): array
    {
        return $this->extracts[$question] ?? [];
    }

    public function isExtracting(int $question): bool
    {
        return filled($this->answer($question)) && ($this->extracted_answers[$question] ?? null) !== $this->answer($question);
    }
}
