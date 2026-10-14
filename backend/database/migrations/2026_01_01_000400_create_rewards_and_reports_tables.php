<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FR-06.3: insert only, and one badge per student at most once.
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('reward_type', ['points', 'badge']);
            $table->string('badge_code')->nullable();
            $table->unsignedSmallInteger('points_awarded')->default(0);
            $table->string('source_type')->nullable();           // experiment | safety_tier | streak
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamp('awarded_at')->useCurrent();
            $table->unique(['user_id', 'badge_code']);           // FR-06.3 once only
            $table->index(['user_id', 'reward_type']);
        });

        Schema::create('readiness_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requesting_user_id')->constrained('users')->cascadeOnDelete();
            // FR-08.1: the six dimensions, each 0 to 100.
            $table->unsignedTinyInteger('procedural_accuracy');
            $table->unsignedTinyInteger('safety_compliance');
            $table->unsignedTinyInteger('reagent_management');
            $table->unsignedTinyInteger('time_efficiency');
            $table->unsignedTinyInteger('conceptual_understanding');
            $table->unsignedTinyInteger('error_recovery');
            $table->enum('classification', ['ready', 'developing', 'not_ready']);  // FR-08.3
            $table->text('narrative')->nullable();
            $table->boolean('ai_generated')->default(false);     // false = rule fallback (NFR-06)
            $table->timestamp('generated_at')->useCurrent();     // FR-08.5
            $table->index('subject_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('readiness_reports');
        Schema::dropIfExists('rewards');
    }
};
