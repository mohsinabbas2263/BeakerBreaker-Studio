<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR-10.4: the administrator turns an experiment off, or back on, for one class,
 * with immediate effect. The data dictionary has nowhere to keep this (CLAUDE.md
 * deviation 9). A row means "off for this class"; no row means on, so a newly
 * added experiment reaches every class it is licensed for without anyone acting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_disabled_experiments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('experiment_id')->constrained('experiments')->cascadeOnDelete();
            $table->foreignId('disabled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disabled_at')->useCurrent();
            $table->unique(['class_id', 'experiment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_disabled_experiments');
    }
};
