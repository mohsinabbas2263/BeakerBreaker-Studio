<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_tier_questions', function (Blueprint $table) {
            $table->id();
            // FR-02.1: 1 Basic Lab Rules, 2 Chemical Handling,
            //          3 Fire and Heat Safety, 4 First Aid and Emergency.
            $table->unsignedTinyInteger('tier_number');
            $table->text('prompt');
            $table->json('options');
            $table->unsignedTinyInteger('correct_option');       // FR-02.5 server only
            $table->text('explanation')->nullable();
            $table->timestamps();
            $table->index('tier_number');
        });

        // Insert only by intent: an attempt is a historical fact (FR-02.6).
        Schema::create('safety_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('tier_number');
            $table->unsignedTinyInteger('score');                // FR-02.5
            $table->boolean('passed');                           // score >= 80
            $table->json('question_ids');                        // FR-02.4 randomised set
            $table->json('submitted_answers');
            $table->timestamp('attempted_at')->useCurrent();     // FR-02.6
            $table->index(['user_id', 'tier_number']);
        });

        // FR-02.8: insert only. No updated_at column, no update or delete path.
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('certificate_reference')->unique();   // FR-02.7
            $table->unsignedTinyInteger('tier1_score');
            $table->unsignedTinyInteger('tier2_score');
            $table->unsignedTinyInteger('tier3_score');
            $table->unsignedTinyInteger('tier4_score');
            $table->date('issued_on');                           // FR-02.7
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('safety_attempts');
        Schema::dropIfExists('safety_tier_questions');
    }
};
