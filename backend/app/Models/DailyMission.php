<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** FR-07.3: one of a student's three missions for one day (Pakistan time). */
class DailyMission extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'mission_date', 'code', 'target', 'completed_at'];

    protected function casts(): array
    {
        return ['mission_date' => 'date', 'completed_at' => 'datetime', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
