<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experiments', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('topic');                            // FR-03.2 filter
            $table->enum('difficulty', ['easy', 'medium', 'hard']);
            $table->unsignedTinyInteger('grade_level');
            $table->foreignId('prerequisite_experiment_id')->nullable()
                  ->constrained('experiments')->nullOnDelete();  // FR-03.3
            $table->text('chemical_equation');                   // FR-03.6
            $table->text('explanatory_note');                    // FR-03.6
            $table->boolean('ar_available')->default(false);     // FR-05.1
            $table->unsignedSmallInteger('base_points');         // FR-06.1
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->string('emoji', 16)->nullable();
            $table->string('card_gradient')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->index(['topic', 'difficulty']);
        });

        Schema::create('experiment_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained('experiments')->cascadeOnDelete();
            $table->unsignedTinyInteger('step_order');           // FR-03.4
            $table->string('title');
            $table->text('instruction_text');
            // NFR-06: this column is why the experiment survives an AI outage.
            // It is NOT NULL on purpose: no step may ship without its own guidance.
            $table->text('preauthored_guidance');
            $table->string('item_emoji', 16)->nullable();
            $table->string('action_label')->nullable();
            $table->timestamps();
            $table->unique(['experiment_id', 'step_order']);
        });

        Schema::create('experiment_quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained('experiments')->cascadeOnDelete();
            $table->text('prompt');
            $table->json('options');
            // Never serialised to any client response. QuizController marks on the server.
            $table->unsignedTinyInteger('correct_option');       // FR-02.5, SR-07
            $table->text('explanation');                         // FR-04.2
            $table->timestamps();
        });

        Schema::create('experiment_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('experiment_id')->constrained('experiments')->cascadeOnDelete();
            $table->enum('presentation_mode', ['standard', 'ar']);   // FR-05.1
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedTinyInteger('steps_completed')->default(0);  // FR-03.5
            $table->json('completed_step_ids')->nullable();              // FR-03.5 resume
            $table->timestamps();
            $table->index(['user_id', 'experiment_id']);
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('experiment_id')->constrained('experiments')->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('experiment_sessions')->nullOnDelete();
            $table->unsignedTinyInteger('score');                // FR-04.4, server computed
            $table->boolean('passed');
            $table->json('submitted_answers');                   // FR-04.4
            $table->timestamp('submitted_at')->useCurrent();     // FR-04.4
            $table->index(['user_id', 'experiment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('experiment_sessions');
        Schema::dropIfExists('experiment_quiz_questions');
        Schema::dropIfExists('experiment_steps');
        Schema::dropIfExists('experiments');
    }
};
