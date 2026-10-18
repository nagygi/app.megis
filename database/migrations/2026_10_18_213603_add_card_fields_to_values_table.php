<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The full value card of the mégis table (03 · 3.2): a stable code the load
        // matches on, English names, the definition and the boundaries.
        Schema::table('values', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->unique()->after('id');
            $table->string('name_en')->nullable()->after('name');
            $table->text('definition')->nullable()->after('value_category_id');
            $table->text('definition_en')->nullable()->after('definition');
            $table->json('false_positives')->default('[]')->after('non_meanings');
            $table->json('overlaps')->default('[]')->after('false_positives');
            $table->json('literature')->default('[]')->after('keywords');
            // strong, medium, weak
            $table->string('evidence')->nullable()->after('literature');
            $table->string('transposability')->nullable()->after('evidence');
            // yes, x_coded, to_verify: whether the value is an item of Schwartz's SVS.
            $table->string('svs_status')->nullable()->after('transposability');
        });

        // Schwartz's higher order value of the category, e.g. "Megőrzés (Conservation)" (03 · 3.4).
        Schema::table('value_categories', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name');
            $table->string('higher_order')->nullable()->after('name_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('values', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn([
                'code', 'name_en', 'definition', 'definition_en', 'false_positives', 'overlaps', 'literature',
                'evidence', 'transposability', 'svs_status',
            ]);
        });

        Schema::table('value_categories', function (Blueprint $table) {
            $table->dropColumn(['name_en', 'higher_order']);
        });
    }
};
