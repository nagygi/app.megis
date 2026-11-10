<?php

namespace App\Models;

use App\Enums\ExternalClientItemKind;
use Database\Factories\ExternalClientItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One thing measured at a client (35 · 35.3). A value is held by the stable
 * key of the organization's value set item, so it survives a new version of
 * the set; it has no question of its own, the client's value question asks
 * for all values. A process has its own question.
 */
#[Fillable(['external_client_id', 'kind', 'value_key', 'process_id', 'question', 'position'])]
class ExternalClientItem extends Model
{
    /** @use HasFactory<ExternalClientItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ExternalClientItemKind::class,
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ExternalClient, $this>
     */
    public function externalClient(): BelongsTo
    {
        return $this->belongsTo(ExternalClient::class);
    }

    /**
     * @return BelongsTo<Process, $this>
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }
}
