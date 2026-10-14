<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExperimentSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'experiment_id', 'presentation_mode',
        'started_at', 'completed_at', 'steps_completed', 'completed_step_ids',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'steps_completed' => 'integer',
            'completed_step_ids' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class, 'experiment_id');
    }

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }
}
