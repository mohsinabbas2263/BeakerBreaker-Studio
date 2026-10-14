<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('experiment_id')->constrained('experiments')->cascadeOnDelete();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->timestamp('due_at');                         // FR-09.4
            $table->timestamps();
            $table->index(['class_id', 'due_at']);
        });

        Schema::create('task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // FR-09.5: assigned -> in_progress -> completed, with overdue
            // reachable from assigned and in_progress when due_at passes.
            $table->enum('state', ['assigned', 'in_progress', 'completed', 'overdue'])
                  ->default('assigned');
            $table->timestamp('state_changed_at')->useCurrent();
            $table->timestamps();
            $table->unique(['task_id', 'user_id']);
            $table->index(['user_id', 'state']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('notification_type');                 // FR-09.7
            $table->string('subject');
            $table->text('body');
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('read_at')->nullable();
            $table->index(['user_id', 'read_at']);
        });

        Schema::create('fcm_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_token');                      // FR-09.6
            $table->enum('platform', ['android', 'ios', 'web']);
            $table->timestamp('registered_at')->useCurrent();
            $table->unique('device_token');
        });

        // FR-10.8: insert only, retained 7 years. No updated_at, no delete path.
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action_type');
            $table->string('target_reference')->nullable();
            $table->json('context')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->index(['actor_user_id', 'occurred_at']);
            $table->index('action_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('fcm_tokens');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('task_assignments');
        Schema::dropIfExists('tasks');
    }
};
