<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('district');
            $table->date('licence_start');
            $table->date('licence_end');
            $table->unsignedInteger('student_cap')->default(0);
            $table->timestamps();
        });

        // FR-01.6: no row is ever created here by a visitor. Creation is an
        // administrator action only, through the Filament panel or a seeder.
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->enum('role', ['student', 'teacher', 'administrator']);
            $table->string('display_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');                       // SR-01: hash only
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->string('avatar_key')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();                            // FR-01.5: 30-day recovery
            $table->index(['school_id', 'role']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();       // FR-01.3: expires in 60 min
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('schools');
    }
};
