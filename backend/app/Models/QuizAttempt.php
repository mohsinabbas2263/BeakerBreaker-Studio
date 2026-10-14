<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FR-04.4: every attempt is recorded with its score, the answers and the time.
 */
class QuizAttempt extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'experiment_id', 'session_id', 'score', 'passed',
        'submitted_answers', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'passed' => 'boolean',
            'submitted_answers' => 'array',
            'submitted_at' => 'datetime',
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
}
