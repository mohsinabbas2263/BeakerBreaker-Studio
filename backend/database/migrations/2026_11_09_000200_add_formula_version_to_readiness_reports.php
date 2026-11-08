<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * FR-08.5: a stored report has to say how its values were computed, because the
 * formulas changed when the experiment engine began recording each step. Reports
 * from before this column were all computed with the interim formulas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('readiness_reports', function (Blueprint $table) {
            $table->string('formula_version', 20)->default('interim-1')->after('ai_generated');
        });
    }

    public function down(): void
    {
        Schema::table('readiness_reports', function (Blueprint $table) {
            $table->dropColumn('formula_version');
        });
    }
};
