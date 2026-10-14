<?php

use App\Enums\MainDiagnosisStage;
use App\Enums\MaterialStatus;
use App\Models\EvidenceTrace;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\Organization;
use App\Models\Product;
use Database\Seeders\DemoDiagnosisSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Mail::fake();
    Queue::fake();
    startingProduct(Product::FULL);
});

it('seeds a running Teljes diagnosis with a leader, four peers and scored situations (M7)', function () {
    actingAsAdmin();

    $this->seed(DemoDiagnosisSeeder::class);

    $organization = Organization::query()->where('slug', DemoDiagnosisSeeder::SLUG)->sole();
    $main = MainDiagnosis::query()->whereHas('diagnosis', fn ($query) => $query->whereBelongsTo($organization))->sole();

    expect($main->stage)->toBe(MainDiagnosisStage::Running)
        ->and($main->product_key)->toBe(Product::FULL)
        ->and($main->peers()->whereNotNull('started_at')->count())->toBe(4)
        ->and($main->processes()->count())->toBe(2)
        ->and($main->calculation['threshold_met'])->toBeTrue()
        ->and($main->calculation['peers_answered'])->toBe(4)
        ->and(Material::query()->where('parent_diagnosis_id', $main->diagnosis_id)->where('status', MaterialStatus::Processed)->count())->toBe(4)
        ->and(EvidenceTrace::query()->whereIn('diagnosis_row_id', $main->diagnosis->rows()->pluck('id'))->exists())->toBeTrue();

    // A second run leaves it alone.
    $this->seed(DemoDiagnosisSeeder::class);
    expect(Organization::query()->where('slug', DemoDiagnosisSeeder::SLUG)->count())->toBe(1);
});

it('does not seed the demo in production', function () {
    actingAsAdmin();
    $this->app['env'] = 'production';

    expect(fn () => (new DemoDiagnosisSeeder)->run())->toThrow(RuntimeException::class);
    expect(Organization::query()->where('slug', DemoDiagnosisSeeder::SLUG)->exists())->toBeFalse();
});
