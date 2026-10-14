<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedTinyInteger('grade_level');        // 9 or 10 (FR-03.1)
            $table->string('academic_year', 9);
            $table->timestamps();
            $table->index(['school_id', 'grade_level']);
        });

        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            // FR-06.4: these two never decrease. RewardService is the only writer.
            $table->unsignedInteger('total_points')->default(0);
            $table->unsignedTinyInteger('rank_level')->default(1);
            $table->unsignedInteger('current_streak_days')->default(0);   // FR-06.5
            $table->date('last_active_on')->nullable();
            $table->timestamps();
            $table->index('total_points');
        });

        // FR-09.1: a teacher may act only on a class listed here for that teacher.
        Schema::create('class_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->unique(['class_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_teachers');
        Schema::dropIfExists('student_profiles');
        Schema::dropIfExists('classes');
    }
};
