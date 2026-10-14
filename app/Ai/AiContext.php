<?php

namespace App\Ai;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;

/**
 * Whom a call is made for, and which result it belongs to (16 · 16.3, 16.4).
 */
final readonly class AiContext
{
    public function __construct(
        public ?Organization $organization = null,
        public bool $publicMirror = false,
        public ?Model $subject = null,
    ) {}

    public static function forOrganization(Organization $organization, ?Model $subject = null): self
    {
        return new self(organization: $organization, subject: $subject);
    }

    /**
     * The anonymous Értéktükör has no organization; its calls are listed apart.
     */
    public static function publicMirror(?Model $subject = null): self
    {
        return new self(publicMirror: true, subject: $subject);
    }
}
