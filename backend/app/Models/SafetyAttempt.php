<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafetyAttempt extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'tier_number', 'score', 'passed',
        'question_ids', 'submitted_answers', 'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'tier_number' => 'integer',
            'score' => 'integer',
            'passed' => 'boolean',
            'question_ids' => 'array',
            'submitted_answers' => 'array',
            'attempted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
