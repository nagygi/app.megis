<?php

use App\Enums\ConsentKind;
use App\Enums\MainDiagnosisStage;
use App\Enums\MaterialStatus;
use App\Models\Consent;
use App\Models\EvidenceTrace;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Value;
use Database\Seeders\DemoDiagnosisSeeder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Mail::fake();
    Queue::fake();
    startingProduct(Product::FULL);
});

it('seeds a running Teljes diagnosis with a leader, four peers and scored situations (M7)', function () {
    $admin = actingAsAdmin();

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
        ->and(EvidenceTrace::query()->whereIn('diagnosis_row_id', $main->diagnosis->rows()->pluck('id'))->exists())->toBeTrue()
        // The admin opens the leader's side on their behalf (14 · 14.6).
        ->and(Gate::forUser($admin)->allows('impersonate', $main->leader))->toBeTrue();

    // The peers' consents carry the text they agreed to, and the demo's own
    // values stay out of other organizations' Értéktár.
    $peer = $main->peers()->firstOrFail()->user;
    expect(Consent::query()->where('user_id', $peer->id)->where('kind', ConsentKind::Answers)->sole()->text)
        ->toBe(__('diagnosis.consent.answers', ['leader' => $main->leader->name]))
        ->and(Value::query()->where('name', 'like', 'Demó · %')->pluck('origin_organization_id')->unique()->all())->toBe([$organization->id]);

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
