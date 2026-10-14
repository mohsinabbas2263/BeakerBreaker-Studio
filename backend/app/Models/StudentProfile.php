<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'class_id', 'total_points', 'rank_level', 'current_streak_days', 'last_active_on',
    ];

    protected function casts(): array
    {
        return [
            'total_points' => 'integer',
            'rank_level' => 'integer',
            'current_streak_days' => 'integer',
            'last_active_on' => 'date',
        ];
    }

    /** FR-06.4: the eight rank levels, by cumulative points. */
    public const RANKS = [
        1 => ['name' => 'Lab Novice', 'from' => 0],
        2 => ['name' => 'Junior Chemist', 'from' => 250],
        3 => ['name' => 'Apprentice Chemist', 'from' => 600],
        4 => ['name' => 'Lab Technician', 'from' => 1200],
        5 => ['name' => 'Senior Chemist', 'from' => 2000],
        6 => ['name' => 'Research Chemist', 'from' => 3200],
        7 => ['name' => 'Lab Master', 'from' => 5000],
        8 => ['name' => 'Beaker Breaker', 'from' => 8000],
    ];

    public static function rankFor(int $points): int
    {
        $rank = 1;
        foreach (self::RANKS as $level => $band) {
            if ($points >= $band['from']) {
                $rank = $level;
            }
        }

        return $rank;
    }

    public function rankName(): string
    {
        return self::RANKS[$this->rank_level]['name'] ?? self::RANKS[1]['name'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}
