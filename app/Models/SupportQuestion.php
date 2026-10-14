<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Database\Factories\SupportQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A supporting question shown after 5 seconds of silence while recording,
 * in the order set by the admin (06 · 6.7).
 */
#[Fillable(['text', 'is_active', 'position'])]
class SupportQuestion extends Model
{
    /** @use HasFactory<SupportQuestionFactory> */
    use Blameable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * Active questions in their order, as the recorder asks them.
     *
     * @param  Builder<SupportQuestion>  $query
     */
    #[Scope]
    protected function forRecording(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('position')->orderBy('id');
    }
}
