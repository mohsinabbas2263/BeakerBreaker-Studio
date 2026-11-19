<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR-07.3: three daily missions per student, replaced at midnight Pakistan time.
 * A twenty-second table (CLAUDE.md, deviations): the data dictionary has nowhere to
 * keep which missions a student was given on a day and when each was completed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('mission_date');                        // the day in Pakistan time
            $table->string('code', 40);
            $table->unsignedSmallInteger('target');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['user_id', 'mission_date', 'code']);
            $table->index(['user_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_missions');
    }
};
