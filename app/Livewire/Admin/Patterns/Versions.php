<?php

namespace App\Livewire\Admin\Patterns;

use App\Enums\CapabilityLevel;
use App\Enums\LevelMarker;
use App\Enums\PatternOrigin;
use App\Enums\PatternProduct;
use App\Enums\PatternSubtype;
use App\Enums\PatternType;
use App\Enums\PatternValidity;
use App\Livewire\Admin\Concerns\ShowsVersionHistory;
use App\Models\Pattern;
use App\Models\Version;
use App\Services\Versioning\SnapshotComparison;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Version history of a pattern (NM-19): every load that changed it.
 */
class Versions extends Component
{
    use ShowsVersionHistory;

    public Pattern $pattern;

    public function mount(Pattern $pattern): void
    {
        Gate::authorize('view', $pattern);

        $this->pattern = $pattern;
    }

    public function render(): View
    {
        return view('livewire.admin.patterns.versions', $this->versionHistory())
            ->layout('layouts::admin')
            ->title(__('admin.versions.title', ['name' => $this->pattern->code.' · '.$this->pattern->name]));
    }

    /**
     * @return array<string, string>
     */
    public static function fields(): array
    {
        $text = SnapshotComparison::TEXT;
        $list = SnapshotComparison::LIST;

        return [
            'name' => $text,
            'type' => $text,
            'subtype' => $text,
            'level' => $text,
            'level_marker' => $text,
            'externally_visible' => $text,
            'products' => $list,
            'validity' => $text,
            'origin' => $text,
            'capabilities' => $list,
            'preconditions' => $list,
            'precondition_note' => $text,
            'where_visible' => $list,
            ...collect(Pattern::contentFields())->mapWithKeys(fn (string $field) => [$field => $text])->all(),
        ];
    }

    protected function versioned(): Model
    {
        return $this->pattern;
    }

    /**
     * The snapshot with labels instead of keys.
     *
     * @return array<string, mixed>
     */
    protected function displayable(Version $version): array
    {
        $snapshot = $version->snapshot;

        foreach (['type' => PatternType::class, 'subtype' => PatternSubtype::class, 'level' => CapabilityLevel::class, 'level_marker' => LevelMarker::class, 'validity' => PatternValidity::class, 'origin' => PatternOrigin::class] as $field => $enum) {
            $snapshot[$field] = $enum::tryFrom((string) ($snapshot[$field] ?? ''))?->label() ?? '';
        }

        $snapshot['products'] = collect($snapshot['products'] ?? [])->map(fn (string $product) => PatternProduct::tryFrom($product)?->label() ?? $product)->all();
        $snapshot['externally_visible'] = match ($snapshot['externally_visible'] ?? null) {
            true => __('admin.capabilities.tempting_yes'),
            false => __('admin.capabilities.tempting_no'),
            default => '',
        };

        return $snapshot;
    }
}
