<?php

namespace App\Enums;

/**
 * The steps of the filling (17 · 17.3, 23). The numbers follow V1; step 6
 * (channels) and 7 (peers) are not in the MVP. Képességek is there only
 * while the capability measurement runs (NM-12).
 */
enum DiagnosisStep: string
{
    case OwnValues = 'own_values';
    case OrganizationValues = 'organization_values';
    case Brand = 'brand';
    case Experience = 'experience';
    case Capabilities = 'capabilities';
    case Processes = 'processes';
    case Visibility = 'visibility';

    public function label(): string
    {
        return __('enums.diagnosis_step.'.$this->value);
    }

    /**
     * The URL segment of the step page.
     */
    public function slug(): string
    {
        return match ($this) {
            self::OwnValues => 'sajat-ertekek',
            self::OrganizationValues => 'szervezet-ertekei',
            self::Brand => 'markad-alapja',
            self::Experience => 'megeles',
            self::Capabilities => 'kepessegek',
            self::Processes => 'folyamatok',
            self::Visibility => 'ki-lat-mit',
        };
    }

    public static function fromSlug(string $slug): ?self
    {
        foreach (self::cases() as $step) {
            if ($step->slug() === $slug) {
                return $step;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_map(fn (self $step) => $step->slug(), self::cases());
    }

    public function minutes(): int
    {
        return (int) config('diagnosis.minutes.'.$this->value);
    }

    /**
     * The symbol before the step's title, as in the prototype: the root for
     * the values (V2, V3, V5), the tree for the brand base (V4), the soil for
     * the processes (V6), the mycelium for who sees what (V9), the rings for
     * the peer's experience (T4).
     */
    public function symbol(bool $peer = false): string
    {
        return match ($this) {
            self::OwnValues, self::OrganizationValues => 'root',
            self::Brand => 'tree-of-life',
            self::Experience => $peer ? 'growth-rings' : 'root',
            self::Capabilities => 'fruit',
            self::Processes => 'soil',
            self::Visibility => 'mycelium',
        };
    }
}
