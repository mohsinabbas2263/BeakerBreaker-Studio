<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * docs/experiment-engine.md, agreed by Mohsin (PR #3, merged 8 Nov 2026).
 *
 * Game-style steps: each step has a kind, a config holding its items and answers,
 * and the avatar's lines. Every move is recorded in step_actions, insert only,
 * because the readiness report (FR-08.1) is computed from it.
 *
 * step_actions is a twenty-first table: a deliberate deviation from Chapter 4,
 * recorded in CLAUDE.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experiment_steps', function (Blueprint $table) {
            // One of the nine kinds in ExperimentStep::KINDS. Existing steps stay
            // 'confirm', which is today's press-the-button behaviour.
            $table->string('kind', 16)->default('confirm')->after('step_order');
            // Items, zones, ranges and the answers. The answers never leave the server.
            $table->json('config')->nullable()->after('kind');
            // The avatar's lines: intro, success, one per expected mistake.
            $table->json('messages')->nullable()->after('config');
        });

        Schema::table('experiment_sessions', function (Blueprint $table) {
            // Progress inside the current step, so a refresh resumes mid-step (NFR-05).
            $table->json('step_state')->nullable()->after('completed_step_ids');
        });

        Schema::create('step_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('experiment_sessions')->cascadeOnDelete();
            $table->foreignId('step_id')->constrained('experiment_steps')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('kind', 16);
            $table->json('action');                                  // what the student did
            $table->enum('outcome', ['correct', 'partial', 'mistake', 'revealed']);
            $table->enum('category', ['procedure', 'safety', 'reagent', 'concept'])->nullable();
            $table->string('mistake_code', 40)->nullable();          // e.g. sandals, overshoot
            $table->boolean('hinted')->default(false);
            // FR-10.8 style: insert only, so there is no updated_at.
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
            $table->index(['session_id', 'step_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('step_actions');

        Schema::table('experiment_sessions', function (Blueprint $table) {
            $table->dropColumn('step_state');
        });

        Schema::table('experiment_steps', function (Blueprint $table) {
            $table->dropColumn(['kind', 'config', 'messages']);
        });
    }
};
