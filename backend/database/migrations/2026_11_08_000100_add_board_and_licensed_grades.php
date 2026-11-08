<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Licensing by class and board (decided 8 Nov 2026, see CLAUDE.md deviation 5).
 *
 * A school or college is licensed for some grades (9 to 12) and follows one board.
 * Its students see the experiments of their class's exam pair (9 and 10, or 11 and 12),
 * only for licensed grades, and only of the school's board or of both boards.
 */
return new class extends Migration
{
    public const BOARDS = ['punjab', 'federal'];

    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->enum('board', self::BOARDS)->default('punjab')->after('district');
            $table->json('licensed_grades')->nullable()->after('board');
        });

        Schema::table('experiments', function (Blueprint $table) {
            // 'both' when the experiment appears in both boards' books.
            $table->enum('board', [...self::BOARDS, 'both'])->default('both')->after('grade_level');
            $table->index(['grade_level', 'board']);
        });

        // Schools that already exist keep what they had: Class 9 and 10.
        DB::table('schools')->update(['licensed_grades' => json_encode([9, 10])]);
    }

    public function down(): void
    {
        Schema::table('experiments', function (Blueprint $table) {
            $table->dropIndex(['grade_level', 'board']);
            $table->dropColumn('board');
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['board', 'licensed_grades']);
        });
    }
};
