<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Képességtár → Csoportok (07 · 7.4): for presenting and filtering, not scored.
        Schema::create('capability_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('name_en')->nullable()->unique();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // The fixed list of preconditions (07 · 7.6), read only.
        Schema::create('preconditions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->text('description');
            $table->timestamps();
        });

        // A capability of the global Képességtár (07 · 7.2). The mégis Sheet is the
        // master (07, question 1): the load fills the fields it has a source for.
        Schema::create('capabilities', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name')->unique();
            $table->string('name_en')->nullable();
            $table->string('slug', 100)->unique();
            $table->foreignId('capability_group_id')->constrained()->restrictOnDelete();
            $table->string('level');
            $table->string('status');
            $table->string('nature')->nullable();
            $table->boolean('tempting')->nullable();
            $table->text('definition');
            $table->text('statement')->nullable();
            $table->jsonb('non_examples');
            $table->jsonb('overlaps');
            $table->jsonb('appearances');
            $table->jsonb('stages');
            $table->jsonb('keywords');
            // The other fields of the Sheet, read only on the card: [{label, value}].
            $table->jsonb('details');
            // Fields the mégis marked "[JAVASLAT]", and the empty states of the Sheet
            // ("nincs még", "nem értelmezhető", "nincs elég külső bizonyíték").
            $table->jsonb('proposed_fields');
            $table->jsonb('field_states');
            $table->text('search_text');
            $table->timestamp('imported_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['archived_at', 'capability_group_id']);
        });

        Schema::create('capability_precondition', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capability_id')->constrained()->cascadeOnDelete();
            $table->foreignId('precondition_id')->constrained()->restrictOnDelete();
            $table->text('note')->nullable();
            $table->unsignedSmallInteger('position')->default(0);

            $table->unique(['capability_id', 'precondition_id']);
        });

        // "Képesség-függőség": another capability this one needs, e.g. ÚJ-01 (07 · 7.2).
        Schema::create('capability_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capability_id')->constrained()->cascadeOnDelete();
            $table->foreignId('depends_on_id')->constrained('capabilities')->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->unsignedSmallInteger('position')->default(0);

            $table->unique(['capability_id', 'depends_on_id']);
        });

        // Loads from the mégis tár (07 · 7.6), the file itself is not kept.
        Schema::create('capability_imports', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->unsignedInteger('created_count');
            $table->unsignedInteger('updated_count');
            $table->unsignedInteger('unchanged_count');
            $table->unsignedInteger('skipped_count');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $now = now();

        // The 12 working groups of the mégis (07 · 7.4); the Hungarian names wait for their review.
        DB::table('capability_groups')->insert(collect([
            'Inner Grounding & Wellbeing' => 'Belső megalapozottság és jóllét',
            'Ethical Agency & Power' => 'Etikus cselekvés és hatalom',
            'Systems & Complexity' => 'Rendszerek és komplexitás',
            'Futures & Adaptability' => 'Jövő és alkalmazkodás',
            'Relational Intelligence' => 'Kapcsolati intelligencia',
            'Teaming & Participation' => 'Csapatmunka és részvétel',
            'Purpose & Strategy' => 'Cél és stratégia',
            'Operating System & Execution' => 'Működési rendszer és végrehajtás',
            'Learning & Innovation' => 'Tanulás és innováció',
            'AI, Data & Technology' => 'MI, adat és technológia',
            'Regenerative Value & Ecology' => 'Regeneratív érték és ökológia',
            'Governance, Risk & Accountability' => 'Irányítás, kockázat, elszámoltathatóság',
        ])->map(fn (string $name, string $nameEn) => [
            'name' => $name,
            'name_en' => $nameEn,
            'created_at' => $now,
            'updated_at' => $now,
        ])->values()->all());

        // E1–E9 (07 · 7.6; kepessegrendszer_osszefoglalo.md · 6.7).
        DB::table('preconditions')->insert(collect([
            'E1' => ['A fájdalom és a döntési jog egy helyen', 'Aki érzi a problémát, dönthet-e a megoldásról.'],
            'E2' => ['Gazda', 'Név szerinti, írásban rögzített gazda, aki a 90. nap után is viszi.'],
            'E3' => ['Védett idő', 'Védett idő a naptárban; a Charta felső határa heti 4–6 óra.'],
            'E4' => ['Kör, nem névsor', 'A vivő kör működő csapat-e.'],
            'E5' => ['Vevő és résztvevő', 'A vevő és a résztvevő ugyanaz-e.'],
            'E6' => ['Mozgástér', 'Mi van a helyi jogkörön kívül, például anyacég vagy bér.'],
            'E7' => ['Szponzor', 'Meddig ér a szponzor keze.'],
            'E8' => ['Belépő mérete', 'Kicsi, árazott belépő.'],
            'E9' => ['Függetlenség', 'Érdekelt-e a mérést végző fél.'],
        ])->map(fn (array $precondition, string $code) => [
            'code' => $code,
            'name' => $precondition[0],
            'description' => $precondition[1],
            'created_at' => $now,
            'updated_at' => $now,
        ])->values()->all());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capability_imports');
        Schema::dropIfExists('capability_dependencies');
        Schema::dropIfExists('capability_precondition');
        Schema::dropIfExists('capabilities');
        Schema::dropIfExists('preconditions');
        Schema::dropIfExists('capability_groups');
    }
};
