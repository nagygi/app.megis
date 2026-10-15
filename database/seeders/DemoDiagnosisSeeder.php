<?php

namespace Database\Seeders;

use App\Enums\ConsentKind;
use App\Enums\DiagnosisStep;
use App\Enums\MainDiagnosisSubject;
use App\Enums\MaterialStatus;
use App\Enums\ParticipantRole;
use App\Enums\ProcessRhythm;
use App\Enums\ScoreResultState;
use App\Enums\StepMark;
use App\Models\Consent;
use App\Models\MainDiagnosis;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use App\Models\Value;
use App\Services\Diagnoses\DiagnosisCalculator;
use App\Services\MainDiagnoses\ExperienceService;
use App\Services\MainDiagnoses\MainDiagnosisService;
use App\Services\Organizations\OrganizationValueSetService;
use App\Services\Processes\OccasionService;
use App\Services\Processes\ProcessService;
use App\Services\Values\PersonalValueSetService;
use App\Services\Values\ValueService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use RuntimeException;

/**
 * The demo organization of the staging acceptance with the mégis (plan M7):
 * a Teljes diagnosis of a team, one leader and four peers, two measured
 * processes with situations, scores and quotes. It ends running with the
 * threshold met, so the reading can start in the admin. No AI call, no
 * factory: it runs on a server installed without dev packages.
 *
 *     php artisan db:seed --class=DemoDiagnosisSeeder
 */
class DemoDiagnosisSeeder extends Seeder
{
    public const SLUG = 'demo-kft';

    private const PEOPLE = [
        ['Anna', 'Kovács'],
        ['Bence', 'Szabó'],
        ['Csilla', 'Tóth'],
        ['Dávid', 'Nagy'],
        ['Eszter', 'Horváth'],
    ];

    /**
     * What happened on the occasions, with the quote the engine would have found.
     *
     * @var list<array{0: string, 1: string}>
     */
    private const SITUATIONS = [
        ['A retrón mindenki elmondta, hol akadt el a sprint, és a csúszást is kimondtuk.', 'A csúszást is kimondtuk, mielőtt kérdezték volna.'],
        ['A retrón ketten végig hallgattak, a döntést a vezető hozta meg egyedül.', 'A döntést végül egyedül hoztam meg.'],
        ['A heti tervezésen a hétfői ügyfélhívás miatt átírtuk a sorrendet, közösen.', 'Közösen írtuk át a sorrendet, nem kellett szólni.'],
        ['A heti tervezésen a régi feladatok maradtak elöl, senki nem kérdezett rá.', 'Senki nem kérdezett rá, miért maradt elöl.'],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('The demo diagnosis is for local and staging only.');
        }

        if (Organization::query()->where('slug', self::SLUG)->exists()) {
            $this->command?->warn('A demó szervezet már megvan ('.self::SLUG.').');

            return;
        }

        $admin = User::query()->where('role', 'superadmin')->orderBy('id')->first()
            ?? User::query()->where('role', 'admin')->orderBy('id')->first()
            ?? throw new RuntimeException('Create an admin first: php artisan megis:admin:create');

        // The recalculation is called directly at the end. Its unique-job lock
        // is taken on the cache store at dispatch, and a duplicate lock insert
        // aborts the transaction on pgsql, so the seed runs on the array cache
        // and queues nothing.
        $cacheDriver = config('cache.default');
        Cache::setDefaultDriver('array');
        app()->forgetInstance('cache.store');
        Queue::fake();

        try {
            $main = DB::transaction(fn (): MainDiagnosis => $this->seed($admin));
        } finally {
            Cache::setDefaultDriver($cacheDriver);
            app()->forgetInstance('cache.store');
        }

        $this->command?->info('Demó diagnózis: '.$main->diagnosis->name.' · fut, a küszöb teljesült. Az olvasat az adminban indítható.');
    }

    private function seed(User $admin): MainDiagnosis
    {
        auth('admin')->setUser($admin);

        $organization = Organization::create([
            'type' => 'organization',
            'name' => 'Demó Kft.',
            'slug' => self::SLUG,
            'product_id' => Product::query()->where('key', Product::FULL)->value('id'),
            'contact_admin_id' => $admin->id,
        ]);
        $team = Team::create(['organization_id' => $organization->id, 'name' => 'Termékcsapat']);

        $people = collect(self::PEOPLE)->map(function (array $name, int $index) use ($organization, $team): User {
            $user = User::firstOrCreate(
                ['email' => 'demo-'.mb_strtolower(str_replace(['á', 'é'], ['a', 'e'], $name[0])).'@example.com'],
                ['first_name' => $name[0], 'last_name' => $name[1]],
            );
            // As if registered: the admin can open their side with "Belépés a nevében" (14 · 14.6).
            $user->forceFill(['activated_at' => $user->activated_at ?? now()])->save();
            Membership::create(['organization_id' => $organization->id, 'user_id' => $user->id, 'status' => 'active', 'is_organization_leader' => $index === 0]);
            $team->users()->attach($user->id, ['is_leader' => $index === 0]);

            return $user;
        });
        $leader = $people->first();
        $peers = $people->slice(1)->values();

        // The organization's values and the brand: the Értéktár's own when it has them.
        $valueSets = app(OrganizationValueSetService::class);
        $values = Value::query()->availableTo($organization)->where('has_endpoints', true)->orderBy('name')->limit(4)->get();

        if ($values->count() < 3) {
            $values = collect(['Nyíltság', 'Felelősségvállalás', 'Együttműködés'])->map(fn (string $name) => app(ValueService::class)->create([
                'name' => 'Demó · '.$name,
                'meanings' => ['Kimondjuk, ami fontos.'],
                'high_examples' => ['A rossz hír aznap kimegy.'],
                'low_examples' => ['A rossz hír megvárja, amíg biztos.'],
            ]));
        }

        $valueSets->save($valueSets->forOrganization($organization), $values->map(fn (Value $value) => $valueSets->itemFromValue($value->load('latestVersion')))->all(), [
            'purpose' => 'Egyszerűbbé tesszük az ügyfeleink napi munkáját.',
            'value_proposition' => 'Gyorsan szállítunk, és kimondjuk, ha valami nem megy.',
            'impact' => 'Kevesebb kézi munka az ügyfélnél, több idő a fontosra.',
        ]);

        $service = app(MainDiagnosisService::class);
        $main = $service->create([
            'organization_id' => $organization->id,
            'subject' => MainDiagnosisSubject::Team->value,
            'team_id' => $team->id,
            'leader_id' => $leader->id,
            'starts_on' => today()->toDateString(),
            'publishes_on' => today()->addWeeks(4)->toDateString(),
            'show_peer_quotes' => true,
        ], $admin);
        $service->invite($main, $admin);

        // The leader's steps: own values, the experience of the values, the processes.
        $own = app(PersonalValueSetService::class);
        $own->save($own->forUser($leader), [['value_id' => null, 'name' => 'Kíváncsiság', 'meaning' => 'Előbb kérdezek, aztán döntök.', 'source' => 'new']], recordVersion: true);
        $version = $valueSets->forOrganization($organization)->latestVersion;
        $experience = app(ExperienceService::class);
        $rows = collect($experience->rows($version->snapshot));
        $experience->submit($main, $leader, $version, $rows->mapWithKeys(fn (array $row) => [$row['key'] => '5'])->all());

        $processes = app(ProcessService::class);
        $retro = $processes->addManual($organization, 'Sprint-retró', ProcessRhythm::tryFrom('biweekly'));
        $planning = $processes->addManual($organization, 'Heti tervezés', ProcessRhythm::tryFrom('weekly'));
        $processes->setAccepted($retro, true, $main);
        $processes->setAccepted($planning, true, $main);
        // "Kész" on V6·f: what the diagnosis measures is recorded.
        $processes->syncDiagnosisProcesses($main);
        $main->stepMarks()->create(['user_id' => $leader->id, 'step' => DiagnosisStep::Processes, 'mark' => StepMark::Viewed]);

        $service->start($main->refresh(), $leader);
        $main->refresh();

        // The peers: consent on T1, then their experience, one lower than the leader's.
        foreach ($peers as $index => $peer) {
            foreach ([ConsentKind::Answers, ConsentKind::Quotes] as $kind) {
                Consent::create(['user_id' => $peer->id, 'main_diagnosis_id' => $main->id, 'kind' => $kind, 'text_version' => __('diagnosis.consent.version'), 'text' => __('diagnosis.consent.version'), 'given_at' => now()]);
            }

            $main->participantFor($peer, ParticipantRole::Peer)?->update(['opened_at' => now(), 'started_at' => now()]);
            $experience->submit($main, $peer, $version, $rows->mapWithKeys(fn (array $row, int $position) => [$row['key'] => (string) max(1, 4 - (($index + $position) % 3))])->all());
        }

        // Four situations on the two processes, each with scores and a quote.
        $occasions = app(OccasionService::class);
        $valueIds = collect($version->snapshot['values'])->pluck('value_id')->all();

        foreach (self::SITUATIONS as $index => [$text, $quote]) {
            $author = $people[$index % $people->count()];
            $material = $occasions->situationText($main, $author, $text);
            $occasions->place($material, $index < 2 ? $retro : $planning, today()->subDays(10 - 2 * $index)->toDateString(), $author, false);
            $material->refresh()->update(['status' => MaterialStatus::Processed]);
            $detection = $material->detections()->create(['quote' => $quote, 'free_name' => 'Nyíltság', 'strength' => 3, 'speaker' => $author->first_name]);

            foreach ($valueIds as $position => $valueId) {
                $material->scores()->create([
                    'value_id' => $valueId,
                    'state' => ScoreResultState::Scored,
                    'score' => [5, 2, 4, 3][($index + $position) % 4],
                    'detection_ids' => $position === 0 ? [$detection->id] : [],
                ]);
            }

            $occasions->experience($material, $author, $rows->mapWithKeys(fn (array $row) => [$row['key'] => (string) (3 + $index % 2)])->all(), 'partial', null, false);
        }

        app(DiagnosisCalculator::class)->recalculate($main->refresh());

        return $main->refresh();
    }
}
