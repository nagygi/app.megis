<?php

namespace App\Models;

use App\Enums\ExternalClientItemKind;
use Database\Factories\ExternalClientAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A client user's answer to one question in a diagnosis (35 · 35.6). The
 * question asked is kept, so an item rewritten or removed later does not
 * change what was answered. Once sent, the material the measurement reads.
 */
#[Fillable([
    'main_diagnosis_id', 'external_client_id', 'user_id', 'external_client_item_id',
    'kind', 'value_key', 'process_id', 'question', 'answer', 'material_id', 'submitted_at',
])]
class ExternalClientAnswer extends Model
{
    /** @use HasFactory<ExternalClientAnswerFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ExternalClientItemKind::class,
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MainDiagnosis, $this>
     */
    public function mainDiagnosis(): BelongsTo
    {
        return $this->belongsTo(MainDiagnosis::class);
    }

    /**
     * @return BelongsTo<ExternalClient, $this>
     */
    public function externalClient(): BelongsTo
    {
        return $this->belongsTo(ExternalClient::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ExternalClientItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ExternalClientItem::class, 'external_client_item_id');
    }

    /**
     * @return BelongsTo<Process, $this>
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function hasAnswer(): bool
    {
        return trim((string) $this->answer) !== '';
    }
}
